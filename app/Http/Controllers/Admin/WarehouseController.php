<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductWarehouseStock;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\StockMovement;
use App\Models\ProductStockBatch;
use App\Models\Warehouse;
use App\Services\AuditLogger;
use App\Services\ModuleSettings;
use App\Services\StockWarehouseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class WarehouseController extends Controller
{
    public function index(Request $request, StockWarehouseService $warehouseService): View
    {
        $this->ensureDefaultWarehouse();

        $warehouses = Warehouse::withCount('productStocks')
            ->withSum('productStocks as total_quantity', 'quantity')
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();

        $stocks = ProductWarehouseStock::with(['product.category', 'warehouse'])
            ->when($request->filled('warehouse_id'), fn ($query) => $query->where('warehouse_id', $request->integer('warehouse_id')))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();
                $query->whereHas('product', function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('barcode', 'like', "%{$search}%");
                });
            })
            ->orderBy('warehouse_id')
            ->orderBy(Product::select('name')->whereColumn('products.id', 'product_warehouse_stocks.product_id'))
            ->paginate(25)
            ->withQueryString();

        return view('admin.warehouses.index', [
            'warehouses' => $warehouses,
            'stocks' => $stocks,
            'products' => Product::where('track_stock', true)->orderBy('name')->get(['id', 'name', 'barcode', 'stock_quantity', 'unit']),
            'enabled' => ModuleSettings::enabled('stock_warehouses'),
            'defaults' => $warehouseService->defaults(),
            'operations' => StockWarehouseService::OPERATIONS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:40', Rule::unique('warehouses', 'code')->where('company_id', session('company_id'))],
            'location' => ['nullable', 'string', 'max:120'],
        ]);

        Warehouse::create([
            'name' => $validated['name'],
            'code' => $validated['code'] ?? null,
            'location' => $validated['location'] ?? null,
            'active' => true,
            'is_default' => false,
        ]);

        return back()->with('success', 'Armazem criado com sucesso.');
    }

    public function update(Request $request, Warehouse $warehouse): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:40', Rule::unique('warehouses', 'code')->where('company_id', session('company_id'))->ignore($warehouse->id)],
            'location' => ['nullable', 'string', 'max:120'],
            'active' => ['sometimes', 'boolean'],
        ]);

        $warehouse->update([
            'name' => $validated['name'],
            'code' => $validated['code'] ?? null,
            'location' => $validated['location'] ?? null,
            'active' => $request->has('active'),
        ]);

        return back()->with('success', 'Armazem atualizado.');
    }

    public function transfer(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'from_warehouse_id' => ['required', Rule::exists('warehouses', 'id')->where('company_id', session('company_id')), 'different:to_warehouse_id'],
            'to_warehouse_id' => ['required', Rule::exists('warehouses', 'id')->where('company_id', session('company_id'))],
            'product_id' => ['required', Rule::exists('products', 'id')->where('company_id', session('company_id'))],
            'quantity' => ['required', 'integer', 'min:1'],
            'lot_number' => ['nullable', 'string', 'max:80'],
            'expires_at' => ['nullable', 'date'],
            'serial_number' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            DB::transaction(function () use ($validated) {
                $product = Product::findOrFail($validated['product_id']);
                if (! $product->track_stock) {
                    throw new \RuntimeException('Este produto nao controla stock.');
                }

                $fromStock = $this->stockRow((int) $product->id, (int) $validated['from_warehouse_id']);
                if ((int) $fromStock->quantity < (int) $validated['quantity']) {
                    throw new \RuntimeException('Stock insuficiente no armazem de origem.');
                }

                $transfer = StockTransfer::create([
                    'reference' => $this->nextReference(),
                    'from_warehouse_id' => $validated['from_warehouse_id'],
                    'to_warehouse_id' => $validated['to_warehouse_id'],
                    'operator_id' => session('operator_id'),
                    'status' => 'pending',
                    'notes' => $validated['notes'] ?? null,
                ]);

                AuditLogger::log('stock_transfer_requested', 'StockTransfer', $transfer->id, [
                    'reference' => $transfer->reference,
                    'from_warehouse_id' => $validated['from_warehouse_id'],
                    'to_warehouse_id' => $validated['to_warehouse_id'],
                    'product_id' => $product->id,
                    'quantity' => (int) $validated['quantity'],
                ], 'warning');

                StockTransferItem::create([
                    'stock_transfer_id' => $transfer->id,
                    'product_id' => $product->id,
                    'lot_number' => $validated['lot_number'] ?? null,
                    'expires_at' => $validated['expires_at'] ?? null,
                    'serial_number' => $validated['serial_number'] ?? null,
                    'quantity' => (int) $validated['quantity'],
                    'from_stock_before' => (int) $fromStock->quantity,
                    'from_stock_after' => (int) $fromStock->quantity,
                    'to_stock_before' => 0,
                    'to_stock_after' => 0,
                ]);
            });
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Pedido de transferencia registado. Aguarda aprovacao.');
    }

    public function approveTransfer(StockTransfer $transfer): RedirectResponse
    {
        if ($transfer->status !== 'pending') {
            return back()->with('error', 'Esta transferencia ja foi analisada.');
        }

        if ((int) $transfer->operator_id === (int) session('operator_id')) {
            return back()->with('error', 'A transferencia deve ser aprovada por outro operador.');
        }

        try {
            DB::transaction(function () use ($transfer) {
                $transfer = StockTransfer::with('items.product')->lockForUpdate()->findOrFail($transfer->id);

                foreach ($transfer->items as $item) {
                    $product = Product::lockForUpdate()->findOrFail($item->product_id);
                    $fromStock = $this->stockRow((int) $product->id, (int) $transfer->from_warehouse_id);
                    $toStock = $this->stockRow((int) $product->id, (int) $transfer->to_warehouse_id);
                    $quantity = (int) $item->quantity;

                    if ((int) $fromStock->quantity < $quantity) {
                        throw new \RuntimeException('Stock insuficiente no armazem de origem para ' . $product->name . '.');
                    }

                    $fromBefore = (int) $fromStock->quantity;
                    $toBefore = (int) $toStock->quantity;
                    $fromAfter = $fromBefore - $quantity;
                    $toAfter = $toBefore + $quantity;

                    $fromStock->update(['quantity' => $fromAfter]);
                    $toStock->update(['quantity' => $toAfter]);
                    app(StockWarehouseService::class)->syncProductTotal($product);

                    if ($item->lot_number || $item->serial_number) {
                        $this->moveBatch($product, $quantity, $transfer, $item);
                    }

                    $item->update([
                        'from_stock_before' => $fromBefore,
                        'from_stock_after' => $fromAfter,
                        'to_stock_before' => $toBefore,
                        'to_stock_after' => $toAfter,
                    ]);

                    foreach ([['OUT', $transfer->from_warehouse_id, $fromBefore, $fromAfter], ['IN', $transfer->to_warehouse_id, $toBefore, $toAfter]] as [$type, $warehouseId, $before, $after]) {
                        StockMovement::create([
                            'product_id' => $product->id,
                            'warehouse_id' => $warehouseId,
                            'lot_number' => $item->lot_number,
                            'expires_at' => $item->expires_at,
                            'serial_number' => $item->serial_number,
                            'type' => $type,
                            'reason' => 'Transferencia entre armazens aprovada',
                            'quantity' => $quantity,
                            'stock_before' => $before,
                            'stock_after' => $after,
                            'notes' => $transfer->notes ?? ('Transferencia ' . $transfer->reference),
                            'reference_type' => 'stock_transfer',
                            'reference_id' => $transfer->id,
                            'operator_id' => session('operator_id'),
                        ]);
                    }
                }

                $transfer->update([
                    'status' => 'completed',
                    'approved_by' => session('operator_id'),
                    'approved_at' => now(),
                ]);

                AuditLogger::log('stock_transfer_approved', 'StockTransfer', $transfer->id, [
                    'reference' => $transfer->reference,
                    'approved_by' => session('operator_id'),
                ], 'warning');
            });
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Transferencia aprovada e stock movimentado.');
    }

    public function rejectTransfer(Request $request, StockTransfer $transfer): RedirectResponse
    {
        if ($transfer->status !== 'pending') {
            return back()->with('error', 'Esta transferencia ja foi analisada.');
        }

        $validated = $request->validate(['rejection_reason' => ['nullable', 'string', 'max:500']]);
        $transfer->update([
            'status' => 'rejected',
            'rejected_by' => session('operator_id'),
            'rejected_at' => now(),
            'rejection_reason' => $validated['rejection_reason'] ?? null,
        ]);

        AuditLogger::log('stock_transfer_rejected', 'StockTransfer', $transfer->id, [
            'reference' => $transfer->reference,
            'reason' => $validated['rejection_reason'] ?? null,
        ], 'warning');

        return back()->with('success', 'Transferencia rejeitada.');
    }
    public function transfers(): View
    {
        $transfers = StockTransfer::with(['fromWarehouse', 'toWarehouse', 'operator', 'approver', 'rejecter', 'items.product'])
            ->latest()
            ->paginate(20);

        return view('admin.warehouses.transfers', compact('transfers'));
    }


    public function updateDefaults(Request $request, StockWarehouseService $warehouseService): RedirectResponse
    {
        $validated = $request->validate([
            'defaults' => ['required', 'array'],
            'defaults.*' => ['required', Rule::exists('warehouses', 'id')->where('company_id', session('company_id'))],
        ]);

        $warehouseService->updateDefaults($validated['defaults']);

        return back()->with('success', 'Armazens padrao atualizados.');
    }

    private function moveBatch(Product $product, int $quantity, StockTransfer $transfer, StockTransferItem $item): void
    {
        $source = ProductStockBatch::where('product_id', $product->id)
            ->where('warehouse_id', $transfer->from_warehouse_id)
            ->when($item->serial_number, fn ($query) => $query->where('serial_number', $item->serial_number), fn ($query) => $query->where('lot_number', $item->lot_number))
            ->lockForUpdate()
            ->first();

        if (! $source || (int) $source->quantity < $quantity) {
            throw new \RuntimeException('Stock insuficiente no lote/serie informado.');
        }

        $target = ProductStockBatch::firstOrCreate(
            [
                'product_id' => $product->id,
                'warehouse_id' => $transfer->to_warehouse_id,
                'serial_number' => $item->serial_number ?: null,
                'lot_number' => $item->lot_number ?: null,
            ],
            [
                'expires_at' => $item->expires_at,
                'quantity' => 0,
                'operator_id' => session('operator_id'),
            ]
        );

        $source->decrement('quantity', $quantity);
        $target->increment('quantity', $quantity);
    }
    private function stockRow(int $productId, int $warehouseId): ProductWarehouseStock
    {
        return ProductWarehouseStock::firstOrCreate(
            ['product_id' => $productId, 'warehouse_id' => $warehouseId],
            ['quantity' => 0, 'minimum_stock' => 0]
        );
    }

    private function ensureDefaultWarehouse(): Warehouse
    {
        $warehouse = Warehouse::where('is_default', true)->first();

        if ($warehouse) {
            return $warehouse;
        }

        return Warehouse::create([
            'name' => 'Armazem Geral',
            'code' => 'GERAL',
            'location' => 'Principal',
            'is_default' => true,
            'active' => true,
        ]);
    }

    private function nextReference(): string
    {
        return 'TRF-' . now()->format('Ymd-His') . '-' . str_pad((string) random_int(1, 999), 3, '0', STR_PAD_LEFT);
    }
}
