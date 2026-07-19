<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductWarehouseStock;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\StockMovement;
use App\Models\Warehouse;
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
            'code' => ['nullable', 'string', 'max:40', 'unique:warehouses,code'],
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
            'code' => ['nullable', 'string', 'max:40', Rule::unique('warehouses', 'code')->ignore($warehouse->id)],
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
            'from_warehouse_id' => ['required', 'exists:warehouses,id', 'different:to_warehouse_id'],
            'to_warehouse_id' => ['required', 'exists:warehouses,id'],
            'product_id' => ['required', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            DB::transaction(function () use ($validated) {
                $product = Product::lockForUpdate()->findOrFail($validated['product_id']);

                if (! $product->track_stock) {
                    throw new \RuntimeException('Este produto nao controla stock.');
                }

                $fromStock = $this->stockRow((int) $product->id, (int) $validated['from_warehouse_id']);
                $toStock = $this->stockRow((int) $product->id, (int) $validated['to_warehouse_id']);
                $quantity = (int) $validated['quantity'];

                if ((int) $fromStock->quantity < $quantity) {
                    throw new \RuntimeException('Stock insuficiente no armazem de origem.');
                }

                $fromBefore = (int) $fromStock->quantity;
                $toBefore = (int) $toStock->quantity;
                $fromAfter = $fromBefore - $quantity;
                $toAfter = $toBefore + $quantity;

                $fromStock->update(['quantity' => $fromAfter]);
                $toStock->update(['quantity' => $toAfter]);

                $transfer = StockTransfer::create([
                    'reference' => $this->nextReference(),
                    'from_warehouse_id' => $validated['from_warehouse_id'],
                    'to_warehouse_id' => $validated['to_warehouse_id'],
                    'operator_id' => session('operator_id'),
                    'status' => 'completed',
                    'notes' => $validated['notes'] ?? null,
                ]);

                StockTransferItem::create([
                    'stock_transfer_id' => $transfer->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'from_stock_before' => $fromBefore,
                    'from_stock_after' => $fromAfter,
                    'to_stock_before' => $toBefore,
                    'to_stock_after' => $toAfter,
                ]);

                StockMovement::create([
                    'product_id' => $product->id,
                    'warehouse_id' => $validated['from_warehouse_id'],
                    'type' => 'OUT',
                    'reason' => 'Transferencia entre armazens',
                    'quantity' => $quantity,
                    'stock_before' => $fromBefore,
                    'stock_after' => $fromAfter,
                    'notes' => $validated['notes'] ?? ('Transferencia ' . $transfer->reference),
                    'reference_type' => 'stock_transfer',
                    'reference_id' => $transfer->id,
                    'operator_id' => session('operator_id'),
                ]);

                StockMovement::create([
                    'product_id' => $product->id,
                    'warehouse_id' => $validated['to_warehouse_id'],
                    'type' => 'IN',
                    'reason' => 'Transferencia entre armazens',
                    'quantity' => $quantity,
                    'stock_before' => $toBefore,
                    'stock_after' => $toAfter,
                    'notes' => $validated['notes'] ?? ('Transferencia ' . $transfer->reference),
                    'reference_type' => 'stock_transfer',
                    'reference_id' => $transfer->id,
                    'operator_id' => session('operator_id'),
                ]);
            });
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Transferencia registada com sucesso.');
    }
    public function transfers(): View
    {
        $transfers = StockTransfer::with(['fromWarehouse', 'toWarehouse', 'operator', 'items.product'])
            ->latest()
            ->paginate(20);

        return view('admin.warehouses.transfers', compact('transfers'));
    }


    public function updateDefaults(Request $request, StockWarehouseService $warehouseService): RedirectResponse
    {
        $validated = $request->validate([
            'defaults' => ['required', 'array'],
            'defaults.*' => ['required', 'exists:warehouses,id'],
        ]);

        $warehouseService->updateDefaults($validated['defaults']);

        return back()->with('success', 'Armazens padrao atualizados.');
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
