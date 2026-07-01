<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Operator;
use App\Models\CurrentAccountEntry;
use App\Models\Payments;
use App\Models\Shift;
use App\Models\Sale;
use App\Models\Product;
use App\Models\Payment;
use App\Models\StockMovement;
use App\Services\BusinessSettings;
use App\Services\DocumentNumbering;
use App\Services\ModuleSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaleController extends Controller
{
    /**
     * STORE VENDA (POS CORE FIXED)
     */

    public function index(Request $request)
    {
        $operatorId = session('operator_id');
        $isCashier = session('operator_role') === 'cashier';
        $baseSalesQuery = Sale::query();

        if ($isCashier) {
            $baseSalesQuery->where('operator_id', $operatorId);
        }

        $query = (clone $baseSalesQuery)->with('customer', 'operator', 'creditNotes');

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
        $totalSales = (clone $baseSalesQuery)->sum('total');
        $todaySales = (clone $baseSalesQuery)->whereDate('created_at', today())->sum('total');

        $monthSales = (clone $baseSalesQuery)->whereMonth('created_at', now()->month)->sum('total');

        $lastMonthSales = (clone $baseSalesQuery)->whereMonth('created_at', now()->subMonth()->month)->sum('total');


        $growth = $lastMonthSales > 0
            ? (($monthSales - $lastMonthSales) / $lastMonthSales) * 100
            : 100;

        $avgTicket = (clone $baseSalesQuery)->avg('total') ?? 0;
        $totalInvoices = (clone $baseSalesQuery)->count();

        // ================= PAYMENT =================
        $paymentQuery = Payments::query();

        if ($isCashier) {
            $paymentQuery->where('operator_id', $operatorId);
        }

        $paymentCash = (clone $paymentQuery)->where('method', 'cash')->sum('amount');
        $paymentCard = (clone $paymentQuery)->where('method', 'card')->sum('amount');
        $paymentMulti = (clone $paymentQuery)->where('method', 'multi')->sum('amount');
        $paymentTransf = (clone $paymentQuery)->where('method', 'transf')->sum('amount');

        // ================= CHART (SEGURO) =================
        $chart = (clone $baseSalesQuery)->selectRaw('DATE(created_at) as date, SUM(total) as total')
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
        $chart = (clone $baseSalesQuery)->selectRaw('DATE(created_at) as date, SUM(total) as total')
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
            ->when($isCashier, fn ($query) => $query->where('operator_id', $operatorId))
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
        $sale = Sale::with('operator', 'items.product', 'payments', 'creditNotes')
            ->findOrFail($id);

        if (session('operator_role') === 'cashier' && (int) $sale->operator_id !== (int) session('operator_id')) {
            abort(403, 'Sem permissão para ver esta venda.');
        }

        $company = BusinessSettings::company();
        $logoUrl = BusinessSettings::logoUrl($company);

        return view('admin.sales.show', compact('sale', 'company', 'logoUrl'));
    }

    public function ticket($id)
    {
        $sale = Sale::with('operator', 'items.product', 'payments')
            ->findOrFail($id);

        if (session('operator_role') === 'cashier' && (int) $sale->operator_id !== (int) session('operator_id')) {
            abort(403, 'Sem permissao para ver esta venda.');
        }

        $company = BusinessSettings::company();
        $logoUrl = BusinessSettings::logoUrl($company);
        $printSettings = BusinessSettings::print();

        return view('admin.sales.ticket', compact('sale', 'company', 'logoUrl', 'printSettings'));
    }
    public function store(Request $request)
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'payments' => 'required|array',
            'total' => 'required|numeric|min:0',
            'customer_id' => 'nullable|exists:customers,id',
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
            $calculated = $this->calculateSaleItems($request->items);
            $total = $calculated['total'];
            $outstanding = round(max($total - $totalPaid, 0), 2);
            $customerId = $request->integer('customer_id') ?: null;

            // ==============================
            // VALIDATION
            // ==============================
            if ($outstanding > 0 && !ModuleSettings::enabled('current_account')) {
                return response()->json([
                    'success' => false,
                    'error' => 'Modulo Conta Corrente desativado pelo super-user.'
                ], 403);
            }

            if ($outstanding > 0 && !$customerId) {
                return response()->json([
                    'success' => false,
                    'error' => 'Pagamento insuficiente. Se for venda a credito, selecione o cliente.'
                ], 422);
            }

            $change = round(min(max($totalPaid - $total, 0), max($cash, 0)), 2);

            // ==============================
            // TRANSACTION
            // ==============================
            $sale = DB::transaction(function () use ($calculated, $operator, $operatorId, $totalPaid, $change, $cash, $card, $transf, $multi, $outstanding, $customerId) {

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

                if ($outstanding > 0) {
                    $paymentMethod = $totalPaid > 0 ? 'mixed_credit' : 'credit';
                }

                $paymentStatus = $outstanding > 0
                    ? ($totalPaid > 0 ? 'partial' : 'unpaid')
                    : 'paid';

                $documentType = $outstanding > 0 ? 'FT' : 'FR';
                $document = DocumentNumbering::next($documentType);
                $invoiceNumber = $document['invoice_number'];

                // ==============================
                // CREATE SALE
                // ==============================
                $sale = Sale::create([
                    'customer_id' => $customerId,
                    'operator_id' => $operator->id,
                    'shift_id' => $shift->id,
                    'invoice_number' => $invoiceNumber,
                    'document_type_code' => $document['document_type_code'],
                    'document_series_id' => $document['document_series_id'],
                    'document_number' => $document['document_number'],
                    'payment_method' => $paymentMethod,
                    'subtotal' => $calculated['subtotal'],
                    'tax' => $calculated['tax'],
                    'tax_rate' => $calculated['tax_rate'],
                    'total' => $calculated['total'],
                    'paid' => $totalPaid,
                    'change' => $change,
                    'payment_status' => $paymentStatus,
                ]);

                foreach ($calculated['items'] as $item) {

                    $product = $item['product'];

                    $qty = $item['quantity'];
                    $price = $item['unit_price'];

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
                        'subtotal' => $item['subtotal'],
                        'net_subtotal' => $item['net_subtotal'],
                        'tax_rate' => $item['tax_rate'],
                        'tax_amount' => $item['tax_amount'],
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
                $paymentAmounts = $this->netPaymentAmounts([
                    'cash' => $cash,
                    'card' => $card,
                    'transf' => $transf,
                    'multi' => $multi
                ], $change);

                foreach ($paymentAmounts as $method => $amount) {

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
                        'operator_id' => $operator->id,
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
                'payment_method' => $sale->payment_method,
                'document_type_code' => $sale->document_type_code,
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

    private function calculateSaleItems(array $items): array
    {
        $calculatedItems = [];
        $grossTotal = 0.0;
        $netTotal = 0.0;
        $taxTotal = 0.0;
        $rates = [];

        foreach ($items as $item) {
            $product = Product::lockForUpdate()->findOrFail($item['id']);
            $qty = round((float) ($item['qty'] ?? 1), 2);

            if ($qty <= 0) {
                throw new \Exception("Quantidade invalida {$product->name}");
            }

            if ($product->stock_quantity < $qty) {
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
}
