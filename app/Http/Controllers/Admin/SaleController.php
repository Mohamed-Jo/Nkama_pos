<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Operator;
use App\Models\CurrentAccountEntry;
use App\Models\Customer;
use App\Models\Payments;
use App\Models\Shift;
use App\Models\Sale;
use App\Models\Product;
use App\Models\Payment;
use App\Models\StockMovement;
use App\Services\AGTSeriesRequestService;
use App\Services\AGTElectronicInvoiceService;
use App\Services\BusinessSettings;
use App\Services\CustomerCardService;
use App\Services\DocumentNumbering;
use App\Services\ModuleSettings;
use App\Services\StockWarehouseService;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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

        $query = (clone $baseSalesQuery)->with(['customer', 'operator', 'agtDocument', 'creditNotes.agtDocument']);

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
    public function create()
    {
        $products = Product::query()
            ->where(function ($query) {
                $query->whereNull('status')->orWhere('status', true);
            })
            ->orderBy('name')
            ->get(['id', 'name', 'barcode', 'selling_price', 'tax_rate', 'stock_quantity', 'unit']);

        $customers = Customer::query()
            ->where(function ($query) {
                $query->whereNull('status')->orWhere('status', true);
            })
            ->orderBy('name')
            ->get(['id', 'name', 'phone', 'email', 'address']);

        $viewTicket = ModuleSettings::enabled('view_ticket');
        $shift = Shift::where('operator_id', session('operator_id'))
            ->where('status', 'open')
            ->latest()
            ->first();
        $canOpenShift = \App\Services\OperatorPermissions::allows(session('operator_role'), 'cash.operate');
        $invoiceSettings = BusinessSettings::invoice();
        $invoiceDueDate = now()->addDays((int) ($invoiceSettings['due_days'] ?? 0))->toDateString();

        return view('admin.sales.create', compact('products', 'customers', 'viewTicket', 'shift', 'canOpenShift', 'invoiceSettings', 'invoiceDueDate'));
    }
    public function show($id)
    {
        $sale = Sale::with('operator', 'items.product', 'payments', 'agtDocument', 'creditNotes.agtDocument', 'customer.card', 'customerCard.balanceTransactions', 'pointTransactions')
            ->findOrFail($id);

        if (session('operator_role') === 'cashier' && (int) $sale->operator_id !== (int) session('operator_id')) {
            abort(403, 'Sem permissão para ver esta venda.');
        }

        $company = BusinessSettings::company();
        $logoUrl = BusinessSettings::logoUrl($company);

        return view('admin.sales.show', compact('sale', 'company', 'logoUrl'));
    }
    public function invoicePdf($id)
    {
        $sale = Sale::with('operator', 'items.product', 'payments', 'agtDocument', 'creditNotes.agtDocument', 'customer.card', 'customerCard.balanceTransactions', 'pointTransactions')
            ->findOrFail($id);

        if (session('operator_role') === 'cashier' && (int) $sale->operator_id !== (int) session('operator_id')) {
            abort(403, 'Sem permissao para ver esta venda.');
        }

        $company = BusinessSettings::company();
        $invoiceSettings = BusinessSettings::invoice();
        $agtQrImage = (bool) ($invoiceSettings['show_agt_qr'] ?? true)
            ? BusinessSettings::agtQrImage($company, $sale->invoice_number, 92)
            : null;

        return Pdf::loadView('admin.sales.invoice-a4', [
            'sale' => $sale,
            'company' => $company,
            'logoUrl' => BusinessSettings::logoDataUri($company),
            'agtQrImage' => $agtQrImage,
        ])->setPaper('a4', 'portrait')
            ->stream('fatura-' . str_replace(['/', ' '], '-', $sale->invoice_number) . '.pdf');
    }
    public function ticket($id)
    {
        $sale = Sale::with('operator', 'items.product', 'payments', 'agtDocument', 'customer.card', 'customerCard.balanceTransactions', 'pointTransactions')
            ->findOrFail($id);

        if (session('operator_role') === 'cashier' && (int) $sale->operator_id !== (int) session('operator_id')) {
            abort(403, 'Sem permissao para ver esta venda.');
        }

        $company = BusinessSettings::company();
        $logoUrl = BusinessSettings::logoUrl($company);
        $printSettings = BusinessSettings::print();
        $invoiceSettings = BusinessSettings::invoice();
        $agtQrImage = (bool) ($invoiceSettings['show_agt_qr'] ?? true)
            ? BusinessSettings::agtQrImage($company, $sale->invoice_number, 88)
            : null;

        return view('admin.sales.ticket', compact('sale', 'company', 'logoUrl', 'printSettings', 'invoiceSettings', 'agtQrImage'));
    }
    public function store(Request $request)
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'payments' => 'required|array',
            'total' => 'required|numeric|min:0',
            'customer_id' => 'nullable|exists:customers,id',
            'customer_card_number' => 'nullable|string|max:80',
            'currency' => 'nullable|string|max:12',
            'exchange_rate' => 'nullable|numeric|min:0.000001|max:999999999',
            'exemption_reason' => 'nullable|string|max:255',
            'commercial_discount' => 'nullable|numeric|min:0|max:100',
            'payment_condition' => 'nullable|string|max:120',
            'due_date' => 'nullable|date',
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

            $invoiceSettings = BusinessSettings::invoice();
            $commercialDiscount = round((float) $request->input('commercial_discount', $invoiceSettings['commercial_discount'] ?? 0), 2);
            $currency = strtoupper(trim((string) $request->input('currency', $invoiceSettings['currency'] ?? 'AOA')));
            $exchangeRate = round(max((float) $request->input('exchange_rate', $invoiceSettings['exchange_rate'] ?? 1), 0.000001), 6);
            $paymentCondition = trim((string) $request->input('payment_condition', $invoiceSettings['payment_condition'] ?? 'Pronto pagamento'));
            $exemptionReason = trim((string) $request->input('exemption_reason', $invoiceSettings['exemption_reason'] ?? ''));
            $dueDate = $request->input('due_date') ?: now()->addDays((int) ($invoiceSettings['due_days'] ?? 0))->toDateString();

            $total = round((float) $request->total, 2);
            $totalPaid = $cash + $card + $transf + $multi;
            $calculated = $this->calculateSaleItems($request->items, $commercialDiscount, 'sales');
            $total = $calculated['total'];
            $outstanding = round(max($total - $totalPaid, 0), 2);
            $customerId = $request->integer('customer_id') ?: null;
            $customerCard = null;

            if (ModuleSettings::enabled('customer_card') && $request->filled('customer_card_number')) {
                $customerCard = app(CustomerCardService::class)->lookup((string) $request->input('customer_card_number'));

                if (!$customerCard || $customerCard->status !== 'active') {
                    return response()->json(['success' => false, 'error' => 'Cartao cliente nao encontrado ou inativo.'], 422);
                }

                $customerId = $customerCard->customer_id;
            }

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
            $sale = DB::transaction(function () use ($calculated, $operator, $operatorId, $totalPaid, $change, $cash, $card, $transf, $multi, $outstanding, $customerId, $customerCard, $currency, $exchangeRate, $paymentCondition, $exemptionReason, $dueDate) {

                // SHIFT
                $shift = Shift::where('operator_id', $operatorId)
                    ->where('status', 'open')
                    ->lockForUpdate()
                    ->first();

                if ($totalPaid > 0 && !$shift) {
                    throw new \Exception('Abra o caixa antes de receber pagamentos');
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
                $salePayload = [
                    'customer_id' => $customerId,
                    'customer_card_id' => $customerCard?->id,
                    'operator_id' => $operator->id,
                    'shift_id' => $shift?->id,
                    'invoice_number' => $invoiceNumber,
                    'document_type_code' => $document['document_type_code'],
                    'document_series_id' => $document['document_series_id'],
                    'document_number' => $document['document_number'],
                    'payment_method' => $paymentMethod,
                    'subtotal' => $calculated['subtotal'],
                    'tax' => $calculated['tax'],
                    'tax_rate' => $calculated['tax_rate'],
                    'discount' => $calculated['discount_amount'],
                    'total' => $calculated['total'],
                    'paid' => $totalPaid,
                    'change' => $change,
                    'payment_status' => $paymentStatus,
                    'status' => $paymentStatus,
                    'currency' => $currency ?: 'AOA',
                    'exchange_rate' => $exchangeRate,
                    'exemption_reason' => $exemptionReason,
                    'commercial_discount' => $calculated['discount_rate'],
                    'payment_condition' => $paymentCondition,
                    'due_date' => $dueDate,
                ];

                $salePayload = array_filter($salePayload, fn ($value, $column) => Schema::hasColumn('sales', $column), ARRAY_FILTER_USE_BOTH);
                $sale = Sale::create($salePayload);
                foreach ($calculated['items'] as $item) {

                    $product = $item['product'];

                    $qty = $item['quantity'];
                    $price = $item['unit_price'];

                    if ($qty <= 0) {
                        throw new \Exception("Quantidade inválida {$product->name}");
                    }

                    if (($product->track_stock ?? true) && $product->stock_quantity < $qty) {
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
                    ]);                    if ($product->track_stock ?? true) {
                        [$stockBefore, $stockAfter] = app(StockWarehouseService::class)->decrease($product, (int) ceil($qty), 'sales');

                        StockMovement::create([
                            'product_id' => $product->id,
                            'type' => 'OUT',
                            'quantity' => $qty,
                            'stock_before' => $stockBefore,
                            'stock_after' => $stockAfter,
                            'reason' => 'Venda',
                            'notes' => 'Venda ' . $invoiceNumber,
                            'reference_type' => 'sale',
                            'reference_id' => $sale->id,
                            'operator_id' => $operator->id,
                        ]);
                    }

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
                        'shift_id' => $shift?->id,
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

                app(CustomerCardService::class)->earnFromSale($sale);


                return $sale;
            });

            app(AGTSeriesRequestService::class)->requestForSale($sale);
            $agtDocument = $this->registerAgtSale($sale);

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
                'message' => 'Venda concluida com sucesso',
                'agt_status' => $agtDocument?->status,
                'agt_status_label' => $agtDocument?->status_label,
                'agt_message' => $agtDocument?->validation_message,
            ]);

        } catch (\Throwable $e) {

            report($e);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function registerAgtSale(Sale $sale): ?\App\Models\AgtDocument
    {
        try {
            $service = app(AGTElectronicInvoiceService::class);

            return $service->send($service->prepareSale($sale));
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }
    private function calculateSaleItems(array $items, float $discountPercent = 0, string $stockOperation = 'sales'): array
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

            if (! app(StockWarehouseService::class)->available($product, (int) ceil($qty), $stockOperation)) {
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

        $discountRate = min(max($discountPercent, 0), 100);
        $discountFactor = max(0, 1 - ($discountRate / 100));
        $discountAmount = round($grossTotal * ($discountRate / 100), 2);

        return [
            'items' => $calculatedItems,
            'subtotal' => round($netTotal * $discountFactor, 2),
            'tax' => round($taxTotal * $discountFactor, 2),
            'tax_rate' => count($rates) === 1 ? (float) array_key_first($rates) : 0.0,
            'total' => round(max($grossTotal - $discountAmount, 0), 2),
            'discount_rate' => round($discountRate, 2),
            'discount_amount' => $discountAmount,
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
