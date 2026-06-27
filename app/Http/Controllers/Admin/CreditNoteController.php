<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CreditNote;
use App\Models\CreditNoteItem;
use App\Models\CurrentAccountEntry;
use App\Models\Operator;
use App\Models\Product;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Services\BusinessSettings;
use App\Services\DocumentNumbering;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class CreditNoteController extends Controller
{
    public function create(Sale $sale): View
    {
        $sale->load('items.product', 'customer', 'creditNotes.items');

        $creditedByItem = CreditNoteItem::whereIn('sale_item_id', $sale->items->pluck('id'))
            ->selectRaw('sale_item_id, COALESCE(SUM(quantity), 0) as credited_qty')
            ->groupBy('sale_item_id')
            ->pluck('credited_qty', 'sale_item_id');

        return view('admin.credit-notes.create', [
            'sale' => $sale,
            'creditedByItem' => $creditedByItem,
        ]);
    }

    public function store(Request $request, Sale $sale): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
            'items' => ['required', 'array'],
            'items.*' => ['nullable', 'numeric', 'min:0'],
        ]);

        try {
            $creditNote = DB::transaction(function () use ($sale, $validated) {
                $operatorId = session('operator_id');
                $operator = $operatorId ? Operator::find($operatorId) : null;

                $sale = Sale::whereKey($sale->id)
                    ->with('items.product')
                    ->lockForUpdate()
                    ->firstOrFail();

                $quantities = collect($validated['items'] ?? [])
                    ->map(fn ($qty) => round((float) $qty, 2))
                    ->filter(fn ($qty) => $qty > 0);

                if ($quantities->isEmpty()) {
                    throw new \RuntimeException('Informe pelo menos um item para emitir a NC.');
                }

                $creditedByItem = CreditNoteItem::whereIn('sale_item_id', $sale->items->pluck('id'))
                    ->selectRaw('sale_item_id, COALESCE(SUM(quantity), 0) as credited_qty')
                    ->groupBy('sale_item_id')
                    ->pluck('credited_qty', 'sale_item_id');

                $itemsToCredit = [];
                $subtotal = 0.0;
                $tax = 0.0;
                $total = 0.0;

                foreach ($sale->items as $item) {
                    $qty = round((float) ($quantities[$item->id] ?? 0), 2);

                    if ($qty <= 0) {
                        continue;
                    }

                    $alreadyCredited = round((float) ($creditedByItem[$item->id] ?? 0), 2);
                    $available = round((float) $item->quantity - $alreadyCredited, 2);

                    if ($qty > $available) {
                        throw new \RuntimeException("Quantidade da NC excede o saldo do item {$item->product?->name}.");
                    }

                    $gross = round($qty * (float) $item->unit_price, 2);
                    $taxRate = round((float) ($item->tax_rate ?? 0), 2);
                    $split = BusinessSettings::splitGrossTotal($gross, $taxRate);

                    $itemsToCredit[] = [
                        'sale_item' => $item,
                        'quantity' => $qty,
                        'unit_price' => round((float) $item->unit_price, 2),
                        'subtotal' => $split['total'],
                        'net_subtotal' => $split['subtotal'],
                        'tax_rate' => $taxRate,
                        'tax_amount' => $split['tax'],
                    ];

                    $subtotal += $split['subtotal'];
                    $tax += $split['tax'];
                    $total += $split['total'];
                }

                if (empty($itemsToCredit)) {
                    throw new \RuntimeException('Nenhum item valido para emitir a NC.');
                }

                $document = DocumentNumbering::next('NC');

                $creditNote = CreditNote::create([
                    'original_sale_id' => $sale->id,
                    'customer_id' => $sale->customer_id,
                    'operator_id' => $operator?->id,
                    'invoice_number' => $document['invoice_number'],
                    'document_type_code' => $document['document_type_code'],
                    'document_series_id' => $document['document_series_id'],
                    'document_number' => $document['document_number'],
                    'subtotal' => round($subtotal, 2),
                    'tax' => round($tax, 2),
                    'total' => round($total, 2),
                    'reason' => $validated['reason'] ?? null,
                ]);

                foreach ($itemsToCredit as $itemData) {
                    $saleItem = $itemData['sale_item'];

                    $creditNote->items()->create([
                        'sale_item_id' => $saleItem->id,
                        'product_id' => $saleItem->product_id,
                        'quantity' => $itemData['quantity'],
                        'unit_price' => $itemData['unit_price'],
                        'subtotal' => $itemData['subtotal'],
                        'net_subtotal' => $itemData['net_subtotal'],
                        'tax_rate' => $itemData['tax_rate'],
                        'tax_amount' => $itemData['tax_amount'],
                    ]);

                    $product = $saleItem->product_id ? Product::lockForUpdate()->find($saleItem->product_id) : null;

                    if ($product && Schema::hasColumn('products', 'stock_quantity')) {
                        $stockBefore = (float) $product->stock_quantity;
                        $product->increment('stock_quantity', $itemData['quantity']);
                        $stockAfter = (float) $product->fresh()->stock_quantity;

                        StockMovement::create([
                            'product_id' => $product->id,
                            'type' => 'IN',
                            'quantity' => $itemData['quantity'],
                            'stock_before' => $stockBefore,
                            'stock_after' => $stockAfter,
                            'notes' => 'NC ' . $creditNote->invoice_number . ' ref. ' . $sale->invoice_number,
                            'operator_id' => $operator?->id,
                        ]);
                    }
                }

                if ($sale->customer_id && strtoupper((string) $sale->document_type_code) === 'FT') {
                    CurrentAccountEntry::create([
                        'entity_type' => 'customer',
                        'entity_id' => $sale->customer_id,
                        'entry_date' => now()->toDateString(),
                        'movement_type' => 'credit',
                        'debit' => 0,
                        'credit' => round($total, 2),
                        'document_type' => 'credit_note',
                        'document_id' => $creditNote->id,
                        'description' => 'NC ' . $creditNote->invoice_number . ' ref. ' . $sale->invoice_number,
                        'operator_id' => $operator?->id,
                    ]);
                }

                return $creditNote;
            });

            return redirect()
                ->route('admin.credit-notes.ticket', $creditNote)
                ->with('success', 'Nota de credito emitida com sucesso.');
        } catch (\Throwable $e) {
            report($e);

            return back()
                ->withInput()
                ->withErrors(['items' => $e->getMessage()]);
        }
    }

    public function ticket(CreditNote $creditNote): View
    {
        $creditNote->load('originalSale', 'customer', 'operator', 'items.product');
        $company = BusinessSettings::company();
        $logoUrl = BusinessSettings::logoUrl($company);

        return view('admin.credit-notes.ticket', compact('creditNote', 'company', 'logoUrl'));
    }
}
