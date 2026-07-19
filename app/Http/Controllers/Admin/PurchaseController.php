<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CurrentAccountEntry;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Services\ModuleSettings;
use App\Services\StockWarehouseService;
use App\Services\OperatorPermissions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PurchaseController extends Controller
{
    public function index()
    {
        $purchases = Purchase::with('supplier', 'operator')
            ->latest()
            ->paginate(15);

        $totals = [
            'count' => Purchase::count(),
            'open' => Purchase::whereIn('status', [
                Purchase::STATUS_DRAFT,
                Purchase::STATUS_ORDERED,
                Purchase::STATUS_PARTIAL,
            ])->where('approval_status', '<>', Purchase::APPROVAL_REJECTED)->count(),
            'pending_approval' => Purchase::where('approval_status', Purchase::APPROVAL_PENDING)->count(),
            'approved' => Purchase::where('approval_status', Purchase::APPROVAL_APPROVED)->count(),
            'partial' => Purchase::where('status', Purchase::STATUS_PARTIAL)->count(),
            'received' => Purchase::where('status', Purchase::STATUS_RECEIVED)->count(),
            'value' => (float) Purchase::sum('total'),
        ];

        $canCreatePurchase = OperatorPermissions::allows(session('operator_role'), 'purchases.create');

        return view('admin.purchases.index', compact('purchases', 'totals', 'canCreatePurchase'));
    }

    public function create()
    {
        return view('admin.purchases.create', [
            'suppliers' => Supplier::where('status', true)->orderBy('company_name')->get(),
            'products' => Product::where('status', true)->orderBy('name')->get(),
            'currentAccountEnabled' => ModuleSettings::enabled('current_account'),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'document_number' => ['nullable', 'string', 'max:80'],
            'purchase_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:purchase_date'],
            'payment_type' => ['required', Rule::in(['direct', 'credit'])],
            'notes' => ['nullable', 'string', 'max:1000'],
            'warehouse_id' => ['nullable', 'exists:warehouses,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
            'items.*.tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        if ($validated['payment_type'] === 'credit' && !ModuleSettings::enabled('current_account')) {
            return back()
                ->withInput()
                ->withErrors(['payment_type' => 'Ative o modulo de conta corrente para registar compras a credito.']);
        }

        $purchase = DB::transaction(function () use ($validated) {
            $purchase = Purchase::create([
                'supplier_id' => $validated['supplier_id'],
                'operator_id' => session('operator_id'),
                'document_number' => $validated['document_number'] ?? null,
                'purchase_date' => $validated['purchase_date'],
                'due_date' => $validated['payment_type'] === 'credit'
                    ? ($validated['due_date'] ?? $validated['purchase_date'])
                    : $validated['purchase_date'],
                'status' => Purchase::STATUS_DRAFT,
                'approval_status' => Purchase::APPROVAL_PENDING,
                'payment_type' => $validated['payment_type'],
                'payment_status' => 'unpaid',
                'notes' => $validated['notes'] ?? null,
                'subtotal' => 0,
                'tax' => 0,
                'total' => 0,
                'paid_amount' => 0,
            ]);

            $subtotal = 0.0;
            $tax = 0.0;
            $total = 0.0;

            foreach ($validated['items'] as $item) {
                $quantity = (int) $item['quantity'];
                $unitCost = round((float) $item['unit_cost'], 2);
                $taxRate = round((float) ($item['tax_rate'] ?? 0), 2);
                $lineSubtotal = round($quantity * $unitCost, 2);
                $lineTax = round($lineSubtotal * $taxRate / 100, 2);
                $lineTotal = round($lineSubtotal + $lineTax, 2);

                $purchase->items()->create([
                    'product_id' => $item['product_id'],
                    'quantity' => $quantity,
                    'received_quantity' => 0,
                    'unit_cost' => $unitCost,
                    'tax_rate' => $taxRate,
                    'subtotal' => $lineSubtotal,
                    'tax' => $lineTax,
                    'total' => $lineTotal,
                ]);

                $subtotal += $lineSubtotal;
                $tax += $lineTax;
                $total += $lineTotal;
            }

            $purchase->update([
                'subtotal' => round($subtotal, 2),
                'tax' => round($tax, 2),
                'total' => round($total, 2),
                'paid_amount' => 0,
            ]);

            return $purchase;
        });

        return redirect()
            ->route('admin.purchases.show', $purchase)
            ->with('success', 'Compra registada. Aprove a compra antes de enviar o pedido ou receber stock.');
    }

    public function show(Purchase $purchase)
    {
        $purchase->load('supplier', 'operator', 'approver', 'rejecter', 'items.product', 'currentAccountEntry');

        $operatorRole = session('operator_role');
        $canCreatePurchase = OperatorPermissions::allows($operatorRole, 'purchases.create');
        $canApprovePurchase = OperatorPermissions::allows($operatorRole, 'purchases.approve');
        $canReceivePurchase = OperatorPermissions::allows($operatorRole, 'purchases.receive');

        return view('admin.purchases.show', [
            'purchase' => $purchase,
            'canCreatePurchase' => $canCreatePurchase,
            'canApprovePurchase' => $canApprovePurchase,
            'canReceivePurchase' => $canReceivePurchase,
            'warehouses' => app(StockWarehouseService::class)->warehouses(),
            'warehouseDefaults' => app(StockWarehouseService::class)->defaults(),
        ]);
    }

    public function approve(Request $request, Purchase $purchase)
    {
        if ($purchase->isClosedForReceiving()) {
            return back()->with('error', 'Esta compra ja esta fechada.');
        }

        $operatorId = (int) session('operator_id');
        if ($purchase->operator_id && $operatorId && (int) $purchase->operator_id === $operatorId) {
            return back()->with('error', 'A compra deve ser aprovada por outro operador.');
        }

        DB::transaction(function () use ($purchase) {
            $purchase = Purchase::lockForUpdate()->findOrFail($purchase->id);

            $updates = [
                'approval_status' => Purchase::APPROVAL_APPROVED,
                'approved_by' => session('operator_id'),
                'approved_at' => now(),
                'rejected_by' => null,
                'rejected_at' => null,
                'rejection_reason' => null,
            ];

            if ($purchase->payment_type === 'direct') {
                $updates['paid_amount'] = $purchase->total;
                $updates['payment_status'] = 'paid';
            }

            $purchase->update($updates);

            if ($purchase->payment_type === 'credit' && (float) $purchase->total > 0 && !$purchase->current_account_entry_id) {
                $entry = CurrentAccountEntry::create([
                    'entity_type' => 'supplier',
                    'entity_id' => $purchase->supplier_id,
                    'entry_date' => optional($purchase->purchase_date)->toDateString() ?: now()->toDateString(),
                    'movement_type' => 'credit',
                    'debit' => 0,
                    'credit' => round((float) $purchase->total, 2),
                    'document_type' => 'purchase',
                    'document_id' => $purchase->id,
                    'description' => 'Compra a credito #' . $purchase->id . ($purchase->document_number ? ' - ' . $purchase->document_number : ''),
                    'operator_id' => session('operator_id'),
                ]);

                $purchase->update(['current_account_entry_id' => $entry->id]);
            }
        });

        return back()->with('success', 'Compra aprovada com sucesso.');
    }

    public function reject(Request $request, Purchase $purchase)
    {
        if ($purchase->isApproved()) {
            return back()->with('error', 'Nao pode rejeitar uma compra ja aprovada. Use um fluxo de anulacao quando necessario.');
        }

        $operatorId = (int) session('operator_id');
        if ($purchase->operator_id && $operatorId && (int) $purchase->operator_id === $operatorId) {
            return back()->with('error', 'A compra deve ser rejeitada por outro operador.');
        }

        if ($purchase->items()->where('received_quantity', '>', 0)->exists()) {
            return back()->with('error', 'Nao pode rejeitar uma compra que ja tem stock recebido.');
        }

        $validated = $request->validate([
            'rejection_reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $purchase->update([
            'approval_status' => Purchase::APPROVAL_REJECTED,
            'approved_by' => null,
            'approved_at' => null,
            'rejected_by' => session('operator_id'),
            'rejected_at' => now(),
            'rejection_reason' => $validated['rejection_reason'] ?? null,
            'status' => Purchase::STATUS_DRAFT,
        ]);

        return back()->with('success', 'Compra rejeitada.');
    }

    public function updateStatus(Request $request, Purchase $purchase)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in([
                Purchase::STATUS_ORDERED,
            ])],
        ]);

        $purchase->load('items');

        if (!$purchase->isApproved()) {
            return back()->with('error', 'A compra precisa estar aprovada antes de enviar o pedido.');
        }

        if ($validated['status'] === Purchase::STATUS_ORDERED && $purchase->items->sum('received_quantity') > 0) {
            return back()->with('error', 'Nao pode voltar uma compra parcial para pedido enviado.');
        }

        if ($purchase->isClosedForReceiving()) {
            return back()->with('error', 'Esta compra ja esta fechada.');
        }

        $purchase->update(['status' => $validated['status']]);

        return back()->with('success', 'Estado da compra atualizado.');
    }

    public function receive(Request $request, Purchase $purchase)
    {
        if (!$purchase->isApproved()) {
            return back()->with('error', 'A compra precisa estar aprovada antes de receber stock.');
        }

        if ($purchase->isClosedForReceiving()) {
            return back()->with('error', 'Esta compra ja nao aceita recebimento de stock.');
        }

        $validated = $request->validate([
            'received' => ['nullable', 'array'],
            'received.*' => ['nullable', 'integer', 'min:0'],
            'warehouse_id' => ['nullable', 'exists:warehouses,id'],
        ]);

        $receivedInput = collect($validated['received'] ?? [])
            ->mapWithKeys(fn ($value, $key) => [(int) $key => (int) $value])
            ->all();

        $receivedAny = false;

        try {
            DB::transaction(function () use ($purchase, $receivedInput, &$receivedAny, $request) {
                $purchase->load('items.product');

                foreach ($purchase->items as $item) {
                    $remaining = max((int) $item->quantity - (int) $item->received_quantity, 0);
                    $quantityToReceive = array_key_exists((int) $item->id, $receivedInput)
                        ? (int) $receivedInput[(int) $item->id]
                        : $remaining;

                    if ($quantityToReceive <= 0) {
                        continue;
                    }

                    if ($quantityToReceive > $remaining) {
                        throw new \RuntimeException('Quantidade recebida maior que a quantidade pendente em ' . ($item->product->name ?? 'um item') . '.');
                    }

                    if (!$item->product) {
                        continue;
                    }

                    $product = Product::lockForUpdate()->find($item->product_id);

                    if (!$product) {
                        continue;
                    }

                    $warehouseId = $request->integer('warehouse_id') ?: null;
                    [$stockBefore, $stockAfter] = app(StockWarehouseService::class)->increase($product, (int) $quantityToReceive, 'purchases', $warehouseId);
                    $movementWarehouseId = app(StockWarehouseService::class)->warehouseIdFor('purchases', $warehouseId);
                    $product->update(['purchase_price' => $item->unit_cost]);

                    if ($product->track_stock ?? true) {
                        StockMovement::create([
                            'product_id' => $product->id,
                            'warehouse_id' => $movementWarehouseId,
                            'type' => 'IN',
                            'reason' => 'Compra recebida',
                            'quantity' => $quantityToReceive,
                            'stock_before' => $stockBefore,
                            'stock_after' => $stockAfter,
                            'notes' => 'Recebimento compra #' . $purchase->id,
                            'reference_type' => 'purchase',
                            'reference_id' => $purchase->id,
                            'operator_id' => session('operator_id'),
                        ]);
                    }

                    $item->increment('received_quantity', $quantityToReceive);
                    $receivedAny = true;
                }

                if (!$receivedAny) {
                    throw new \RuntimeException('Informe pelo menos uma quantidade para receber.');
                }

                $purchase->load('items');
                $totalQuantity = (int) $purchase->items->sum('quantity');
                $totalReceived = (int) $purchase->items->sum('received_quantity');
                $fullyReceived = $totalQuantity > 0 && $totalReceived >= $totalQuantity;

                $purchase->update([
                    'status' => $fullyReceived ? Purchase::STATUS_RECEIVED : Purchase::STATUS_PARTIAL,
                    'received_at' => $fullyReceived ? now() : null,
                ]);
            });
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('admin.purchases.show', $purchase)
            ->with('success', 'Recebimento de stock registado com sucesso.');
    }
}
