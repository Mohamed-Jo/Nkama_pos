<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CurrentAccountEntry;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseAttachment;
use App\Models\PurchaseReturn;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Services\ModuleSettings;
use App\Services\OperatorPermissions;
use App\Services\StockWarehouseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
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
            'open' => Purchase::whereIn('status', [Purchase::STATUS_DRAFT, Purchase::STATUS_ORDERED, Purchase::STATUS_PARTIAL])
                ->where('approval_status', '<>', Purchase::APPROVAL_REJECTED)
                ->count(),
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
            'document_type' => ['nullable', Rule::in([Purchase::TYPE_QUOTATION, Purchase::TYPE_ORDER, Purchase::TYPE_PURCHASE])],
            'document_number' => ['nullable', 'string', 'max:80'],
            'quotation_reference' => ['nullable', 'string', 'max:80'],
            'order_number' => ['nullable', 'string', 'max:80'],
            'supplier_invoice_number' => ['nullable', 'string', 'max:80'],
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
            'expenses' => ['nullable', 'array'],
            'expenses.*.description' => ['nullable', 'string', 'max:255'],
            'expenses.*.category' => ['nullable', 'string', 'max:80'],
            'expenses.*.amount' => ['nullable', 'numeric', 'min:0'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'mimes:pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx', 'max:5120'],
        ]);

        if ($validated['payment_type'] === 'credit' && !ModuleSettings::enabled('current_account')) {
            return back()->withInput()->withErrors(['payment_type' => 'Ative o modulo de conta corrente para registar compras a credito.']);
        }

        $purchase = DB::transaction(function () use ($request, $validated) {
            $purchase = Purchase::create([
                'supplier_id' => $validated['supplier_id'],
                'operator_id' => session('operator_id'),
                'document_type' => $validated['document_type'] ?? Purchase::TYPE_PURCHASE,
                'document_number' => $validated['document_number'] ?? null,
                'quotation_reference' => $validated['quotation_reference'] ?? null,
                'order_number' => $validated['order_number'] ?? null,
                'supplier_invoice_number' => $validated['supplier_invoice_number'] ?? null,
                'purchase_date' => $validated['purchase_date'],
                'due_date' => $validated['payment_type'] === 'credit' ? ($validated['due_date'] ?? $validated['purchase_date']) : $validated['purchase_date'],
                'status' => Purchase::STATUS_DRAFT,
                'approval_status' => Purchase::APPROVAL_PENDING,
                'payment_type' => $validated['payment_type'],
                'payment_status' => 'unpaid',
                'notes' => $validated['notes'] ?? null,
                'subtotal' => 0,
                'tax' => 0,
                'expenses_total' => 0,
                'total' => 0,
                'paid_amount' => 0,
            ]);

            $subtotal = 0.0;
            $tax = 0.0;

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
                    'returned_quantity' => 0,
                    'unit_cost' => $unitCost,
                    'tax_rate' => $taxRate,
                    'subtotal' => $lineSubtotal,
                    'tax' => $lineTax,
                    'total' => $lineTotal,
                ]);

                $subtotal += $lineSubtotal;
                $tax += $lineTax;
            }

            $expensesTotal = 0.0;
            foreach (($validated['expenses'] ?? []) as $expense) {
                $amount = round((float) ($expense['amount'] ?? 0), 2);
                $description = trim((string) ($expense['description'] ?? ''));
                if ($amount <= 0 || $description === '') {
                    continue;
                }

                $purchase->expenses()->create([
                    'description' => $description,
                    'category' => $expense['category'] ?: 'Geral',
                    'amount' => $amount,
                    'affects_cost' => true,
                    'operator_id' => session('operator_id'),
                ]);
                $expensesTotal += $amount;
            }

            $purchase->update([
                'subtotal' => round($subtotal, 2),
                'tax' => round($tax, 2),
                'expenses_total' => round($expensesTotal, 2),
                'total' => round($subtotal + $tax + $expensesTotal, 2),
            ]);

            foreach ($request->file('attachments', []) as $file) {
                $path = $file->store('purchase_attachments', 'public');
                $purchase->attachments()->create([
                    'label' => 'Anexo da compra',
                    'original_name' => $file->getClientOriginalName(),
                    'path' => $path,
                    'mime_type' => $file->getClientMimeType(),
                    'size' => $file->getSize(),
                    'operator_id' => session('operator_id'),
                ]);
            }

            return $purchase;
        });

        return redirect()->route('admin.purchases.show', $purchase)->with('success', 'Documento de compra registado. Aprove antes de enviar pedido ou receber stock.');
    }

    public function show(Purchase $purchase)
    {
        $purchase->load('supplier', 'operator', 'approver', 'rejecter', 'items.product', 'currentAccountEntry', 'expenses.operator', 'attachments.operator', 'returns.items.product', 'returns.operator');

        $operatorRole = session('operator_role');

        return view('admin.purchases.show', [
            'purchase' => $purchase,
            'canCreatePurchase' => OperatorPermissions::allows($operatorRole, 'purchases.create'),
            'canApprovePurchase' => OperatorPermissions::allows($operatorRole, 'purchases.approve'),
            'canReceivePurchase' => OperatorPermissions::allows($operatorRole, 'purchases.receive'),
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

        $validated = $request->validate(['rejection_reason' => ['nullable', 'string', 'max:1000']]);

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
        $validated = $request->validate(['status' => ['required', Rule::in([Purchase::STATUS_ORDERED])]]);
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

        $purchase->update(['status' => $validated['status'], 'document_type' => Purchase::TYPE_ORDER]);

        return back()->with('success', 'Estado da compra atualizado.');
    }

    public function storeExpense(Request $request, Purchase $purchase)
    {
        if ($purchase->isApproved()) {
            return back()->with('error', 'Adicione despesas antes da aprovacao para manter a conta corrente correta.');
        }

        $validated = $request->validate([
            'description' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:80'],
            'amount' => ['required', 'numeric', 'min:0.01'],
        ]);

        DB::transaction(function () use ($purchase, $validated) {
            $purchase->expenses()->create([
                'description' => $validated['description'],
                'category' => $validated['category'] ?: 'Geral',
                'amount' => round((float) $validated['amount'], 2),
                'affects_cost' => true,
                'operator_id' => session('operator_id'),
            ]);

            $purchase->update([
                'expenses_total' => round((float) $purchase->expenses()->sum('amount'), 2),
                'total' => round((float) $purchase->subtotal + (float) $purchase->tax + (float) $purchase->expenses()->sum('amount'), 2),
            ]);
        });

        return back()->with('success', 'Despesa de compra adicionada.');
    }

    public function storeAttachment(Request $request, Purchase $purchase)
    {
        $validated = $request->validate([
            'label' => ['nullable', 'string', 'max:120'],
            'attachment' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx', 'max:5120'],
        ]);

        $file = $validated['attachment'];
        $path = $file->store('purchase_attachments', 'public');

        $purchase->attachments()->create([
            'label' => $validated['label'] ?: 'Anexo da compra',
            'original_name' => $file->getClientOriginalName(),
            'path' => $path,
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize(),
            'operator_id' => session('operator_id'),
        ]);

        return back()->with('success', 'Anexo guardado.');
    }

    public function downloadAttachment(PurchaseAttachment $attachment)
    {
        return Storage::disk('public')->download($attachment->path, $attachment->original_name);
    }

    public function returnToSupplier(Request $request, Purchase $purchase)
    {
        if (!$purchase->isApproved()) {
            return back()->with('error', 'A compra precisa estar aprovada antes de registar devolucao.');
        }

        $validated = $request->validate([
            'return_date' => ['required', 'date'],
            'document_number' => ['nullable', 'string', 'max:80'],
            'reason' => ['nullable', 'string', 'max:1000'],
            'returned' => ['required', 'array'],
            'returned.*' => ['nullable', 'integer', 'min:0'],
            'warehouse_id' => ['nullable', 'exists:warehouses,id'],
        ]);

        try {
            DB::transaction(function () use ($purchase, $validated, $request) {
                $purchase->load('items.product');
                $return = PurchaseReturn::create([
                    'purchase_id' => $purchase->id,
                    'supplier_id' => $purchase->supplier_id,
                    'operator_id' => session('operator_id'),
                    'return_date' => $validated['return_date'],
                    'document_number' => $validated['document_number'] ?? null,
                    'reason' => $validated['reason'] ?? null,
                    'total' => 0,
                ]);

                $total = 0.0;
                $returnedAny = false;

                foreach ($purchase->items as $item) {
                    $quantity = (int) ($validated['returned'][$item->id] ?? 0);
                    if ($quantity <= 0) {
                        continue;
                    }

                    if ($quantity > $item->returnable_quantity) {
                        throw new \RuntimeException('Quantidade de devolucao maior que o recebido em ' . ($item->product->name ?? 'um item') . '.');
                    }

                    $lineSubtotal = round($quantity * (float) $item->unit_cost, 2);
                    $lineTax = round($lineSubtotal * (float) $item->tax_rate / 100, 2);
                    $lineTotal = round($lineSubtotal + $lineTax, 2);

                    $return->items()->create([
                        'purchase_item_id' => $item->id,
                        'product_id' => $item->product_id,
                        'quantity' => $quantity,
                        'unit_cost' => $item->unit_cost,
                        'tax_rate' => $item->tax_rate,
                        'total' => $lineTotal,
                    ]);

                    if ($item->product) {
                        $product = Product::lockForUpdate()->find($item->product_id);
                        [$stockBefore, $stockAfter] = app(StockWarehouseService::class)->decrease($product, $quantity, 'purchases', $request->integer('warehouse_id') ?: null);
                        StockMovement::create([
                            'product_id' => $product->id,
                            'warehouse_id' => app(StockWarehouseService::class)->warehouseIdFor('purchases', $request->integer('warehouse_id') ?: null),
                            'type' => 'OUT',
                            'reason' => 'Devolucao ao fornecedor',
                            'quantity' => $quantity,
                            'stock_before' => $stockBefore,
                            'stock_after' => $stockAfter,
                            'notes' => 'Devolucao compra #' . $purchase->id,
                            'reference_type' => 'purchase_return',
                            'reference_id' => $return->id,
                            'operator_id' => session('operator_id'),
                        ]);
                    }

                    $item->increment('returned_quantity', $quantity);
                    $total += $lineTotal;
                    $returnedAny = true;
                }

                if (!$returnedAny) {
                    throw new \RuntimeException('Informe pelo menos uma quantidade para devolver.');
                }

                $return->update(['total' => round($total, 2)]);

                if ($purchase->payment_type === 'credit' && ModuleSettings::enabled('current_account')) {
                    CurrentAccountEntry::create([
                        'entity_type' => 'supplier',
                        'entity_id' => $purchase->supplier_id,
                        'entry_date' => $validated['return_date'],
                        'movement_type' => 'debit',
                        'debit' => round($total, 2),
                        'credit' => 0,
                        'document_type' => 'purchase_return',
                        'document_id' => $return->id,
                        'description' => 'Devolucao ao fornecedor da compra #' . $purchase->id,
                        'operator_id' => session('operator_id'),
                    ]);
                }
            });
        } catch (\Throwable $e) {
            report($e);
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Devolucao ao fornecedor registada.');
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
            'lot_number' => ['nullable', 'array'],
            'lot_number.*' => ['nullable', 'string', 'max:80'],
            'expires_at' => ['nullable', 'array'],
            'expires_at.*' => ['nullable', 'date'],
            'serial_number' => ['nullable', 'array'],
            'serial_number.*' => ['nullable', 'string', 'max:120'],
            'warehouse_id' => ['nullable', 'exists:warehouses,id'],
        ]);

        $receivedInput = collect($validated['received'] ?? [])->mapWithKeys(fn ($value, $key) => [(int) $key => (int) $value])->all();
        $receivedAny = false;

        try {
            DB::transaction(function () use ($purchase, $receivedInput, &$receivedAny, $request) {
                $purchase->load('items.product');

                foreach ($purchase->items as $item) {
                    $remaining = max((int) $item->quantity - (int) $item->received_quantity, 0);
                    $quantityToReceive = array_key_exists((int) $item->id, $receivedInput) ? (int) $receivedInput[(int) $item->id] : $remaining;

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
                    $lotNumber = $validated['lot_number'][$item->id] ?? null;
                    $expiresAt = $validated['expires_at'][$item->id] ?? null;
                    $serialNumber = $validated['serial_number'][$item->id] ?? null;
                    [$stockBefore, $stockAfter] = app(StockWarehouseService::class)->increase($product, (int) $quantityToReceive, 'purchases', $warehouseId);
                    app(StockWarehouseService::class)->increaseBatch($product, (int) $quantityToReceive, 'purchases', $warehouseId, $lotNumber, $expiresAt, $serialNumber);
                    $movementWarehouseId = app(StockWarehouseService::class)->warehouseIdFor('purchases', $warehouseId);
                    $product->update(['purchase_price' => $item->unit_cost]);

                    if ($product->track_stock ?? true) {
                        StockMovement::create([
                            'product_id' => $product->id,
                            'warehouse_id' => $movementWarehouseId,
                            'lot_number' => $lotNumber,
                            'expires_at' => $expiresAt,
                            'serial_number' => $serialNumber,
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

        return redirect()->route('admin.purchases.show', $purchase)->with('success', 'Recebimento de stock registado com sucesso.');
    }
}
