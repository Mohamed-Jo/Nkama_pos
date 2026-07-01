<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CurrentAccountEntry;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Services\ModuleSettings;
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
            'draft' => Purchase::where('status', 'draft')->count(),
            'received' => Purchase::where('status', 'received')->count(),
            'value' => (float) Purchase::sum('total'),
        ];

        return view('admin.purchases.index', compact('purchases', 'totals'));
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
            'payment_type' => ['required', Rule::in(['direct', 'credit'])],
            'notes' => ['nullable', 'string', 'max:1000'],
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
                'status' => 'draft',
                'payment_type' => $validated['payment_type'],
                'payment_status' => $validated['payment_type'] === 'credit' ? 'unpaid' : 'paid',
                'notes' => $validated['notes'] ?? null,
                'subtotal' => 0,
                'tax' => 0,
                'total' => 0,
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
            ]);

            if ($validated['payment_type'] === 'credit' && $total > 0) {
                $entry = CurrentAccountEntry::create([
                    'entity_type' => 'supplier',
                    'entity_id' => $validated['supplier_id'],
                    'entry_date' => $validated['purchase_date'],
                    'movement_type' => 'credit',
                    'debit' => 0,
                    'credit' => round($total, 2),
                    'document_type' => 'purchase',
                    'document_id' => $purchase->id,
                    'description' => 'Compra a credito #' . $purchase->id . ($purchase->document_number ? ' - ' . $purchase->document_number : ''),
                    'operator_id' => session('operator_id'),
                ]);

                $purchase->update(['current_account_entry_id' => $entry->id]);
            }

            return $purchase;
        });

        return redirect()
            ->route('admin.purchases.show', $purchase)
            ->with('success', 'Compra registada. Receba o stock quando a mercadoria entrar.');
    }

    public function show(Purchase $purchase)
    {
        $purchase->load('supplier', 'operator', 'items.product', 'currentAccountEntry');

        return view('admin.purchases.show', compact('purchase'));
    }

    public function receive(Purchase $purchase)
    {
        if ($purchase->status === 'received') {
            return back()->with('error', 'Esta compra ja foi recebida no stock.');
        }

        DB::transaction(function () use ($purchase) {
            $purchase->load('items.product');

            foreach ($purchase->items as $item) {
                if (!$item->product) {
                    continue;
                }

                $product = Product::lockForUpdate()->find($item->product_id);

                if (!$product) {
                    continue;
                }

                $stockBefore = (float) $product->stock_quantity;
                $product->increment('stock_quantity', (int) $item->quantity);
                $product->update(['purchase_price' => $item->unit_cost]);
                $stockAfter = (float) $product->fresh()->stock_quantity;

                StockMovement::create([
                    'product_id' => $product->id,
                    'type' => 'IN',
                    'quantity' => $item->quantity,
                    'stock_before' => $stockBefore,
                    'stock_after' => $stockAfter,
                    'notes' => 'Compra #' . $purchase->id,
                    'operator_id' => session('operator_id'),
                ]);
            }

            $purchase->update([
                'status' => 'received',
                'received_at' => now(),
            ]);
        });

        return redirect()
            ->route('admin.purchases.show', $purchase)
            ->with('success', 'Stock recebido com sucesso.');
    }
}
