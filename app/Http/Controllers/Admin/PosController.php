<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Operator;
use App\Models\Payments;
use App\Models\Product;
use App\Models\RestaurantTable;
use App\Models\Sale;
use App\Models\Shift;
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

                $total = round((float) $request->total, 2);
                $paymentMethod = $request->input('payment_method', 'cash');
                $breakdown = $this->normalizePaymentBreakdown($paymentMethod, $request);
                $paid = round(array_sum($breakdown), 2);
                $cashPaid = round((float) ($breakdown['cash'] ?? 0), 2);
                $change = round(min(max($paid - $total, 0), max($cashPaid, 0)), 2);

                if ($paid < $total) {
                    throw new \Exception('Pagamento insuficiente');
                }

                $sale = Sale::create($this->salePayload([
                    'invoice_number' => $this->nextInvoiceNumber(),
                    'operator_id' => $operator?->id,
                    'shift_id' => $shift?->id,
                    'user_id' => auth()->id(),
                    'subtotal' => $total,
                    'discount' => 0,
                    'tax' => 0,
                    'total' => $total,
                    'paid' => $paid,
                    'change' => $change,
                    'payment_method' => $paymentMethod === 'multi' ? 'mixed' : $paymentMethod,
                    'payment_status' => 'paid',
                    'status' => 'paid',
                ]));

                foreach ($request->items as $item) {
                    $product = Product::lockForUpdate()->findOrFail($item['id']);
                    $qty = (int) max(1, (float) ($item['qty'] ?? 1));
                    $price = round((float) ($item['price'] ?? $product->selling_price), 2);

                    $sale->items()->create([
                        'product_id' => $product->id,
                        'quantity' => $qty,
                        'unit_price' => $price,
                        'subtotal' => $qty * $price,
                    ]);

                    if (Schema::hasColumn('products', 'stock_quantity')) {
                        $product->decrement('stock_quantity', $qty);
                    }
                }

                foreach ($breakdown as $method => $amount) {
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

                return $sale;
            });

            return response()->json([
                'success' => true,
                'sale_id' => $sale->id,
                'invoice' => $sale->invoice_number,
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

    private function nextInvoiceNumber(): string
    {
        $nextId = (Sale::lockForUpdate()->max('id') ?? 0) + 1;

        return 'INV-' . date('Y') . '-' . str_pad($nextId, 6, '0', STR_PAD_LEFT);
    }

    private function normalizePaymentBreakdown(string $paymentMethod, Request $request): array
    {
        if ($paymentMethod === 'multi') {
            $breakdown = $request->input('payment_breakdown', []);

            return [
                'cash' => round((float) ($breakdown['cash'] ?? 0), 2),
                'card' => round((float) ($breakdown['card'] ?? 0), 2),
                'transf' => round((float) ($breakdown['transfer'] ?? $breakdown['transf'] ?? 0), 2),
            ];
        }

        return [
            $paymentMethod => round((float) $request->input('amount_paid', $request->total), 2),
        ];
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
