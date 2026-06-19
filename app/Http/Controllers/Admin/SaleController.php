<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Operator;
use App\Models\Payments;
use App\Models\Shift;
use App\Models\Sale;
use App\Models\Product;
use App\Models\Payment;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaleController extends Controller
{
    /**
     * STORE VENDA (POS CORE FIXED)
     */

    public function index(Request $request)
    {
        $query = Sale::with('customer');

        // ================= FILTER =================
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('invoice_number', 'like', "%{$request->search}%")
                    ->orWhereHas('customer', function ($c) use ($request) {
                        $c->where('name', 'like', "%{$request->search}%");
                    });
            });
        }

        if ($request->from) {
            $query->whereDate('created_at', '>=', $request->from);
        }

        if ($request->to) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        $sales = $query->latest()->paginate(15)->withQueryString();

        // ================= KPIs =================
        $totalSales = Sale::sum('total');
        $todaySales = Sale::whereDate('created_at', today())->sum('total');

        $monthSales = Sale::whereMonth('created_at', now()->month)->sum('total');

        $lastMonthSales = Sale::whereMonth('created_at', now()->subMonth()->month)->sum('total');


        $growth = $lastMonthSales > 0
            ? (($monthSales - $lastMonthSales) / $lastMonthSales) * 100
            : 100;

        $avgTicket = Sale::avg('total') ?? 0;
        $totalInvoices = Sale::count();

        // ================= PAYMENT =================
        $paymentCash = Payments::where('method', 'cash')->sum('amount');
        $paymentCard = Payments::where('method', 'card')->sum('amount');
        $paymentMulti = Payments::where('method', 'multi')->sum('amount');
        $paymentTransf = Payments::where('method', 'transf')->sum('amount');

        // ================= CHART (SEGURO) =================
        $chart = Sale::selectRaw('DATE(created_at) as date, SUM(total) as total')
            ->whereDate('created_at', '>=', now()->subDays(6))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // separar corretamente (SEM map no Blade)
        $chartLabels = $chart->pluck('date');
        $chartData = $chart->pluck('total');

        // ================= INSIGHTS =================
        $insights = [];

        if ($todaySales < ($avgTicket * 2)) {
            $insights[] = "📉 Performance abaixo do esperado hoje.";
        }

        if ($growth < 0) {
            $insights[] = "⚠️ Queda de faturação mensal.";
        }

        if (empty($insights)) {
            $insights[] = "📊 Sistema financeiro estável.";
        }

        // ================= CHART (SEGURO) =================
        $chart = Sale::selectRaw('DATE(created_at) as date, SUM(total) as total')
            ->whereDate('created_at', '>=', now()->subDays(6))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $chartLabels = $chart->pluck('date');
        $chartData = $chart->pluck('total');

        // ================= 🔥 NOVO: TOP 5 PRODUTOS MAIS VENDIDOS =================
        // Esta query entra na relação de itens, agrupa e soma a quantidade vendida
        $topProducts = StockMovement::select('product_id', DB::raw('SUM(quantity) as total_qty'))
            ->where('type', 'OUT')
            ->where('notes', 'like', 'Venda%')
            ->with('product')
            ->groupBy('product_id')
            ->orderByDesc('total_qty')
            ->take(5)
            ->get();

        // ================= INSIGHTS =================
        $insights = [];

        if ($todaySales < ($avgTicket * 2)) {
            $insights[] = "📉 Performance abaixo do esperado hoje.";
        }

        if ($growth < 0) {
            $insights[] = "⚠️ Queda de faturação mensal.";
        }

        if (empty($insights)) {
            $insights[] = "📊 Sistema financeiro estável.";
        }

        return view('admin.sales.index', compact(
            'sales',
            'totalSales',
            'todaySales',
            'growth',
            'avgTicket',
            'totalInvoices',
            'paymentCash',
            'paymentCard',
            'paymentMulti',
            'paymentTransf',
            'chartLabels',
            'chartData',
            'insights',
            'topProducts' // <-- Passado para a View aqui
        ));
    }

     
    
    public function show($id)
    {
        $sale = Sale::with('operator', 'items.product', 'payments')
            ->findOrFail($id);

        return view('admin.sales.show', compact('sale'));
    }
    public function store(Request $request)
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'payments' => 'required|array',
            'total' => 'required|numeric|min:0'
        ]);

        try {

            // ==============================
            // OPERATOR SESSION
            // ==============================
            $operatorId = session('operator_id');

            if (!$operatorId) {
                return response()->json([
                    'success' => false,
                    'error' => 'Sessão expirada'
                ], 401);
            }

            $operator = Operator::find($operatorId);

            if (!$operator) {
                return response()->json([
                    'success' => false,
                    'error' => 'Operador inválido'
                ], 401);
            }

            // ==============================
            // PAYMENTS SAFE PARSE
            // ==============================
            $payments = $request->input('payments', []);

            $cash = round((float) ($payments['cash'] ?? 0), 2);
            $card = round((float) ($payments['card'] ?? 0), 2);
            $transf = round((float) ($payments['transf'] ?? 0), 2);
            $multi = round((float) ($payments['multi'] ?? 0), 2);

            $total = round((float) $request->total, 2);
            $totalPaid = $cash + $card + $transf + $multi;

            // ==============================
            // VALIDATION
            // ==============================
            if ($totalPaid < $total) {
                return response()->json([
                    'success' => false,
                    'error' => 'Pagamento insuficiente'
                ], 422);
            }

            $change = round(max(0, $totalPaid - $total), 2);

            // ==============================
            // TRANSACTION
            // ==============================
            $sale = DB::transaction(function () use ($request, $operator, $operatorId, $total, $totalPaid, $change, $cash, $card, $transf, $multi) {

                // SHIFT
                $shift = Shift::where('operator_id', $operatorId)
                    ->where('status', 'open')
                    ->lockForUpdate()
                    ->first();

                if (!$shift) {
                    throw new \Exception('Abra o caixa antes de vender');
                }

                // ==============================
                // PAYMENT METHOD DETECTION FIXED
                // ==============================
                $methodsUsed = [];

                foreach ([
                    'cash' => $cash,
                    'card' => $card,
                    'transf' => $transf,
                    'multi' => $multi
                ] as $k => $v) {
                    if (round($v, 2) > 0) {
                        $methodsUsed[] = $k;
                    }
                }

                $paymentMethod = count($methodsUsed) > 1
                    ? 'mixed'
                    : ($methodsUsed[0] ?? 'cash');

                // ==============================
                // INVOICE NUMBER SAFE
                // ==============================
                $lastId = (Sale::lockForUpdate()->max('id') ?? 0) + 1;

                $invoiceNumber = 'INV-' . date('Y') . '-' . str_pad($lastId, 6, '0', STR_PAD_LEFT);

                // ==============================
                // CREATE SALE
                // ==============================
                $sale = Sale::create([
                    'operator_id' => $operator->id,
                    'shift_id' => $shift->id,
                    'invoice_number' => $invoiceNumber,
                    'payment_method' => $paymentMethod,
                    'subtotal' => $total,
                    'total' => $total,
                    'paid' => $totalPaid,
                    'change' => $change,
                    'status' => 'paid'
                ]);

                foreach ($request->items as $item) {

                    $product = Product::lockForUpdate()->findOrFail($item['id']);

                    $qty = round((float) $item['qty'], 2);
                    $price = round((float) $item['price'], 2);

                    if ($qty <= 0) {
                        throw new \Exception("Quantidade inválida {$product->name}");
                    }

                    if ($product->stock_quantity < $qty) {
                        throw new \Exception("Stock insuficiente {$product->name}");
                    }

                    $stockBefore = $product->stock_quantity;

                    $sale->items()->create([
                        'product_id' => $product->id,
                        'quantity' => $qty,
                        'unit_price' => $price,
                        'subtotal' => $qty * $price
                    ]);

                    $product->decrement('stock_quantity', $qty);

                    StockMovement::create([
                        'product_id' => $product->id,
                        'type' => 'OUT',
                        'quantity' => $qty,
                        'stock_before' => $stockBefore,
                        'stock_after' => $product->fresh()->stock_quantity,
                        'notes' => 'Venda ' . $invoiceNumber,
                        'operator_id' => $operator->id
                    ]);
                }

                // ==============================
                // PAYMENTS SAVE
                // ==============================
                foreach ([
                    'cash' => $cash,
                    'card' => $card,
                    'transf' => $transf,
                    'multi' => $multi
                ] as $method => $amount) {

                    if ($amount <= 0)
                        continue;

                    Payments::create([
                        'sale_id' => $sale->id,
                        'shift_id' => $shift->id,
                        'operator_id' => $operator->id,
                        'method' => $method,
                        'amount' => $amount
                    ]);
                }

                // ==============================
                // SHIFT UPDATE (IMPORTANT)
                // ==============================
                // $shift->increment('total', $total);

                return $sale;
            });

            // ==============================
            // RESPONSE
            // ==============================
            return response()->json([
                'success' => true,
                'sale_id' => $sale->id,
                'invoice' => $sale->invoice_number,
                'total' => $sale->total,
                'paid' => $sale->paid,
                'change' => $sale->change,
                'message' => 'Venda concluída muito sucesso'
            ]);

        } catch (\Throwable $e) {

            report($e);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}