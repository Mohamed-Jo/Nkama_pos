<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\CurrentAccountEntry;
use App\Models\Customer;
use App\Models\Operator;
use App\Models\Payments;
use App\Models\Product;
use App\Models\RestaurantTable;
use App\Models\Sale;
use App\Models\Shift;
use App\Services\BusinessSettings;
use App\Services\DocumentNumbering;
use App\Services\ModuleSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PosController extends Controller
{
    public function index()
    {
        $modules = ModuleSettings::all();

        return view('admin.pos.index', [
            'modules' => $modules,
            'products' => Product::with('category')
                ->where('status', true)
                ->where('available_supermarket', true)
                ->orderBy('category_id')
                ->orderBy('name')
                ->get(),
            'restaurantCategories' => Category::where('status', true)
                ->whereHas('products', function ($query) {
                    $query->where('status', true)->where('available_restaurant', true);
                })
                ->with(['products' => function ($query) {
                    $query->where('status', true)
                        ->where('available_restaurant', true)
                        ->orderBy('name');
                }])
                ->orderBy('name')
                ->get(),
            'customers' => Customer::where('status', true)->get(),
            'operatorName' => Operator::find(session('operator_id'))?->name ?? 'Operador',
            'shift' => Shift::where('operator_id', session('operator_id'))->where('status', 'open')->first(),
            'tables' => RestaurantTable::orderByRaw('LENGTH(name), name')->get(),
        ]);
    }

    public function checkout(Request $request)
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'total' => 'required|numeric|min:0',
            'payment_method' => 'nullable|string',
            'amount_paid' => 'nullable|numeric|min:0',
            'payment_breakdown' => 'nullable|array',
            'table_id' => 'nullable|integer',
            'customer_id' => 'nullable|exists:customers,id',
        ]);

        $isRestaurantSale = $request->filled('table_id');

        if ($isRestaurantSale && !ModuleSettings::enabled('restaurant')) {
            return response()->json([
                'success' => false,
                'message' => 'Modulo Restaurante desativado pelo super-user.',
            ], 403);
        }

        if (!$isRestaurantSale && !ModuleSettings::enabled('supermarket')) {
            return response()->json([
                'success' => false,
                'message' => 'Modulo Supermercado desativado pelo super-user.',
            ], 403);
        }

        try {
            $sale = DB::transaction(function () use ($request) {
                $operatorId = session('operator_id');
                $operator = $operatorId ? Operator::find($operatorId) : null;

                if (!$operator) {
                    throw new \Exception('Operador invalido ou sessao expirada');
                }

                $shift = $operatorId
                    ? Shift::where('operator_id', $operatorId)->where('status', 'open')->lockForUpdate()->first()
                    : null;

                if (!$shift) {
                    throw new \Exception('Abra o caixa antes de vender');
                }

                $paymentMethod = $request->input('payment_method', 'cash');
                $calculated = $this->calculateSaleItems($request->items);
                $total = $calculated['total'];
                $breakdown = $this->normalizePaymentBreakdown($paymentMethod, $request, $total);
                $paid = round(array_sum($breakdown), 2);
                $cashPaid = round((float) ($breakdown['cash'] ?? 0), 2);
                $change = round(min(max($paid - $total, 0), max($cashPaid, 0)), 2);
                $outstanding = round(max($total - $paid, 0), 2);
                $customerId = $request->integer('customer_id') ?: null;

                if ($outstanding > 0 && !$customerId) {
                    throw new \Exception('Pagamento insuficiente');
                }

                if ($paymentMethod === 'credit' && !$customerId) {
                    throw new \Exception('Selecione o cliente para vender em conta corrente');
                }

                $paymentStatus = $outstanding > 0
                    ? ($paid > 0 ? 'partial' : 'unpaid')
                    : 'paid';

                $documentType = $outstanding > 0 ? 'FT' : 'FR';
                $document = DocumentNumbering::next($documentType);

                $sale = Sale::create($this->salePayload([
                    'invoice_number' => $document['invoice_number'],
                    'document_type_code' => $document['document_type_code'],
                    'document_series_id' => $document['document_series_id'],
                    'document_number' => $document['document_number'],
                    'customer_id' => $customerId,
                    'operator_id' => $operator?->id,
                    'shift_id' => $shift?->id,
                    'user_id' => auth()->id(),
                    'subtotal' => $calculated['subtotal'],
                    'discount' => 0,
                    'tax' => $calculated['tax'],
                    'tax_rate' => $calculated['tax_rate'],
                    'total' => $calculated['total'],
                    'paid' => $paid,
                    'change' => $change,
                    'payment_method' => $outstanding > 0 && $paid > 0 ? 'mixed_credit' : ($paymentMethod === 'multi' ? 'mixed' : $paymentMethod),
                    'payment_status' => $paymentStatus,
                    'status' => $paymentStatus,
                ]));

                foreach ($calculated['items'] as $item) {
                    $product = $item['product'];
                    $sale->items()->create([
                        'product_id' => $product->id,
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'subtotal' => $item['subtotal'],
                        'net_subtotal' => $item['net_subtotal'],
                        'tax_rate' => $item['tax_rate'],
                        'tax_amount' => $item['tax_amount'],
                    ]);

                    if (Schema::hasColumn('products', 'stock_quantity')) {
                        $product->decrement('stock_quantity', $item['quantity']);
                    }
                }

                $paymentAmounts = $this->netPaymentAmounts($breakdown, $change);

                foreach ($paymentAmounts as $method => $amount) {
                    if ($amount <= 0) {
                        continue;
                    }

                    Payments::create($this->paymentPayload([
                        'sale_id' => $sale->id,
                        'shift_id' => $shift?->id,
                        'operator_id' => $operator?->id,
                        'method' => $method,
                        'payment_method' => $method,
                        'amount' => $amount,
                    ]));
                }

                if ($outstanding > 0) {
                    CurrentAccountEntry::create([
                        'entity_type' => 'customer',
                        'entity_id' => $customerId,
                        'entry_date' => now()->toDateString(),
                        'movement_type' => 'debit',
                        'debit' => $outstanding,
                        'credit' => 0,
                        'document_type' => 'sale',
                        'document_id' => $sale->id,
                        'description' => 'Venda a credito ' . $sale->invoice_number,
                        'operator_id' => $operator?->id,
                    ]);
                }

                return $sale;
            });

            return response()->json([
                'success' => true,
                'sale_id' => $sale->id,
                'invoice' => $sale->invoice_number,
                'payment_method' => $sale->payment_method,
                'payment_status' => $sale->payment_status,
                'outstanding' => round(max((float) $sale->total - (float) $sale->paid, 0), 2),
                'message' => 'Fatura emitida com sucesso',
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function findProductByBarcode(Request $request)
    {
        if (!ModuleSettings::enabled('supermarket')) {
            return response()->json(['success' => false, 'message' => 'Módulo supermercado desativado.'], 403);
        }

        $product = Product::where('barcode', $request->barcode)->where('status', true)->first();

        if (!$product) {
            return response()->json(['success' => false, 'message' => 'Produto nao encontrado.'], 404);
        }

        return response()->json(['success' => true, 'data' => $product]);
    }

    private function normalizePaymentBreakdown(string $paymentMethod, Request $request, float $total): array
    {
        if ($paymentMethod === 'credit') {
            return [
                'credit' => 0,
            ];
        }

        if ($paymentMethod === 'multi') {
            $breakdown = $request->input('payment_breakdown', []);

            return [
                'cash' => round((float) ($breakdown['cash'] ?? 0), 2),
                'card' => round((float) ($breakdown['card'] ?? 0), 2),
                'transf' => round((float) ($breakdown['transfer'] ?? $breakdown['transf'] ?? 0), 2),
            ];
        }

        return [
            $paymentMethod => round((float) $request->input('amount_paid', $total), 2),
        ];
    }

    private function calculateSaleItems(array $items): array
    {
        $calculatedItems = [];
        $grossTotal = 0.0;
        $netTotal = 0.0;
        $taxTotal = 0.0;
        $rates = [];

        foreach ($items as $item) {
            $product = Product::lockForUpdate()->findOrFail($item['id']);
            $qty = (int) max(1, (float) ($item['qty'] ?? 1));

            if (Schema::hasColumn('products', 'stock_quantity') && $product->stock_quantity < $qty) {
                throw new \Exception("Stock insuficiente {$product->name}");
            }

            $price = round((float) $product->selling_price, 2);
            $gross = round($qty * $price, 2);
            $taxRate = round((float) ($product->tax_rate ?? 0), 2);
            $split = BusinessSettings::splitGrossTotal($gross, $taxRate);

            $grossTotal += $split['total'];
            $netTotal += $split['subtotal'];
            $taxTotal += $split['tax'];
            $rates[(string) $taxRate] = true;

            $calculatedItems[] = [
                'product' => $product,
                'quantity' => $qty,
                'unit_price' => $price,
                'subtotal' => $split['total'],
                'net_subtotal' => $split['subtotal'],
                'tax_rate' => $taxRate,
                'tax_amount' => $split['tax'],
            ];
        }

        return [
            'items' => $calculatedItems,
            'subtotal' => round($netTotal, 2),
            'tax' => round($taxTotal, 2),
            'tax_rate' => count($rates) === 1 ? (float) array_key_first($rates) : 0.0,
            'total' => round($grossTotal, 2),
        ];
    }

    private function netPaymentAmounts(array $breakdown, float $change): array
    {
        $amounts = $breakdown;

        if ($change > 0 && isset($amounts['cash'])) {
            $amounts['cash'] = round(max(0, (float) $amounts['cash'] - $change), 2);
        }

        return $amounts;
    }

    private function salePayload(array $values): array
    {
        return collect($values)
            ->filter(fn ($value, $column) => $value !== null && Schema::hasColumn('sales', $column))
            ->all();
    }

    private function paymentPayload(array $values): array
    {
        return collect($values)
            ->filter(fn ($value, $column) => $value !== null && Schema::hasColumn('payments', $column))
            ->all();
    }
}
