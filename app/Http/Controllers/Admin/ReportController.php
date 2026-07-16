<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\CashMovement;
use App\Models\CreditNote;
use App\Models\CurrentAccountEntry;
use App\Models\Customer;
use App\Models\CustomerCard;
use App\Models\CustomerCardBalanceTransaction;
use App\Models\Payments;
use App\Models\PointTransaction;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Shift;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Services\BusinessSettings;
use App\Services\ModuleSettings;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(): View
    {
        return view('admin.reports.index', [
            'customers' => Customer::where('status', true)->orderBy('name')->get(),
            'suppliers' => Supplier::where('status', true)->orderBy('company_name')->get(),
            'modules' => ModuleSettings::all(),
            'auditActions' => AuditLog::select('action')->distinct()->orderBy('action')->pluck('action'),
            'auditModels' => AuditLog::select('model')->distinct()->orderBy('model')->pluck('model'),
            'from' => now()->startOfMonth()->toDateString(),
            'to' => now()->toDateString(),
        ]);
    }

    public function salesPdf(Request $request)
    {
        [$from, $to] = $this->period($request);

        $sales = Sale::with('customer', 'operator', 'creditNotes')
            ->whereBetween('created_at', [$from, $to])
            ->orderBy('created_at')
            ->get();

        $creditNotes = CreditNote::with('customer', 'operator', 'originalSale')
            ->whereBetween('created_at', [$from, $to])
            ->orderBy('created_at')
            ->get();

        return $this->pdf('reports.sales', [
            'title' => 'Relatorio de Vendas e Documentos',
            'from' => $from,
            'to' => $to,
            'sales' => $sales,
            'creditNotes' => $creditNotes,
            'totals' => [
                'fr' => (float) $sales->where('document_type_code', 'FR')->sum('total'),
                'ft' => (float) $sales->where('document_type_code', 'FT')->sum('total'),
                'paid' => (float) $sales->sum('paid'),
                'pending' => (float) $sales->sum(fn ($sale) => max((float) $sale->total - (float) $sale->creditNotes->sum('total') - (float) $sale->paid, 0)),
                'nc' => (float) $creditNotes->sum('total'),
                'net' => (float) $sales->sum('total') - (float) $creditNotes->sum('total'),
            ],
        ], 'relatorio-vendas.pdf');
    }

    public function cashPdf(Request $request)
    {
        [$from, $to] = $this->period($request);

        $payments = Payments::with('sale', 'creditNote', 'operator')
            ->whereBetween('created_at', [$from, $to])
            ->orderBy('created_at')
            ->get();

        $cashMovements = CashMovement::with('operator', 'currentAccountEntry')
            ->whereBetween('created_at', [$from, $to])
            ->orderBy('created_at')
            ->get();

        $shifts = Shift::with('operator')
            ->where(function ($query) use ($from, $to) {
                $query->whereBetween('opened_at', [$from, $to])
                    ->orWhereBetween('closed_at', [$from, $to]);
            })
            ->orderBy('opened_at')
            ->get();

        return $this->pdf('reports.cash', [
            'title' => 'Relatorio de Caixa',
            'from' => $from,
            'to' => $to,
            'payments' => $payments,
            'cashMovements' => $cashMovements,
            'shifts' => $shifts,
            'totals' => [
                'cash' => (float) $payments->where('method', 'cash')->sum('amount') + (float) $cashMovements->where('method', 'cash')->sum('amount'),
                'card' => (float) $payments->where('method', 'card')->sum('amount') + (float) $cashMovements->where('method', 'card')->sum('amount'),
                'multi' => (float) $payments->where('method', 'multi')->sum('amount') + (float) $cashMovements->where('method', 'multi')->sum('amount'),
                'transf' => (float) $payments->where('method', 'transf')->sum('amount') + (float) $cashMovements->where('method', 'transf')->sum('amount'),
                'refunds' => abs((float) $payments->where('amount', '<', 0)->sum('amount') + (float) $cashMovements->where('amount', '<', 0)->sum('amount')),
                'net' => (float) $payments->sum('amount') + (float) $cashMovements->sum('amount'),
            ],
        ], 'relatorio-caixa.pdf');
    }

    public function currentAccountsPdf(Request $request)
    {
        [$from, $to] = $this->period($request);

        $query = CurrentAccountEntry::with('operator')
            ->whereBetween('entry_date', [$from->toDateString(), $to->toDateString()])
            ->orderBy('entry_date')
            ->orderBy('id');

        if ($request->filled('entity_type')) {
            $query->where('entity_type', $request->entity_type);
        }

        $entityId = $request->input('entity_type') === 'supplier'
            ? $request->integer('supplier_entity_id')
            : $request->integer('entity_id');

        if ($request->filled('entity_type') && $entityId > 0) {
            $query->where('entity_id', $entityId);
        }

        $entries = $query->get();

        return $this->pdf('reports.current-accounts', [
            'title' => 'Relatorio de Conta Corrente',
            'from' => $from,
            'to' => $to,
            'entries' => $entries,
            'totals' => [
                'debit' => (float) $entries->sum('debit'),
                'credit' => (float) $entries->sum('credit'),
                'balance' => (float) $entries->sum('debit') - (float) $entries->sum('credit'),
            ],
        ], 'relatorio-conta-corrente.pdf');
    }

    public function stockPdf(Request $request)
    {
        $query = Product::with('category')->orderBy('name');

        if ($request->filled('status')) {
            $query->where('status', (bool) $request->integer('status'));
        }

        if ($request->boolean('low_stock')) {
            $query->whereColumn('stock_quantity', '<=', 'minimum_stock');
        }

        $products = $query->get();

        return $this->pdf('reports.stock', [
            'title' => 'Relatorio de Stock',
            'from' => now()->startOfDay(),
            'to' => now()->endOfDay(),
            'products' => $products,
            'totals' => [
                'items' => $products->count(),
                'stock' => (float) $products->sum('stock_quantity'),
                'low' => $products->filter(fn ($product) => (float) $product->stock_quantity <= (float) $product->minimum_stock)->count(),
                'value' => (float) $products->sum(fn ($product) => (float) $product->stock_quantity * (float) $product->selling_price),
            ],
        ], 'relatorio-stock.pdf');
    }

    public function stockMovementsPdf(Request $request)
    {
        [$from, $to] = $this->period($request);

        $query = StockMovement::with('product', 'operator')
            ->whereBetween('created_at', [$from, $to])
            ->orderBy('created_at');

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $movements = $query->get();

        return $this->pdf('reports.stock-movements', [
            'title' => 'Relatorio de Movimentos de Stock',
            'from' => $from,
            'to' => $to,
            'movements' => $movements,
            'totals' => [
                'count' => $movements->count(),
                'in' => (float) $movements->where('type', 'IN')->sum('quantity'),
                'out' => (float) $movements->where('type', 'OUT')->sum('quantity'),
            ],
        ], 'relatorio-movimentos-stock.pdf');
    }

    public function purchasesPdf(Request $request)
    {
        [$from, $to] = $this->period($request);

        $query = Purchase::with('supplier', 'operator')
            ->whereBetween('created_at', [$from, $to])
            ->orderBy('created_at');

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->integer('supplier_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('approval_status')) {
            $query->where('approval_status', $request->approval_status);
        }

        if ($request->filled('payment_type')) {
            $query->where('payment_type', $request->payment_type);
        }

        if ($request->filled('payment_status')) {
            if ($request->payment_status === 'overdue') {
                $query->where('payment_type', 'credit')
                    ->where('approval_status', Purchase::APPROVAL_APPROVED)
                    ->where('payment_status', '<>', 'paid')
                    ->whereDate('due_date', '<', now()->toDateString());
            } else {
                $query->where('payment_status', $request->payment_status);
            }
        }

        $purchases = $query->get();

        return $this->pdf('reports.purchases', [
            'title' => 'Relatorio de Compras',
            'from' => $from,
            'to' => $to,
            'purchases' => $purchases,
            'totals' => [
                'count' => $purchases->count(),
                'open' => $purchases->filter(fn ($purchase) => in_array($purchase->status, [
                    Purchase::STATUS_DRAFT,
                    Purchase::STATUS_ORDERED,
                    Purchase::STATUS_PARTIAL,
                ], true) && $purchase->approval_status !== Purchase::APPROVAL_REJECTED)->count(),
                'pending_approval' => $purchases->where('approval_status', 'pending')->count(),
                'approved' => $purchases->where('approval_status', 'approved')->count(),
                'rejected' => $purchases->where('approval_status', 'rejected')->count(),
                'partial' => $purchases->where('status', 'partial')->count(),
                'received' => $purchases->where('status', 'received')->count(),
                'credit' => (float) $purchases->where('payment_type', 'credit')->sum('total'),
                'direct' => (float) $purchases->where('payment_type', 'direct')->sum('total'),
                'paid' => (float) $purchases->sum('paid_amount'),
                'balance' => (float) $purchases->sum(fn ($purchase) => $purchase->balance),
                'overdue' => $purchases->filter(fn ($purchase) => $purchase->isOverdue())->count(),
                'total' => (float) $purchases->sum('total'),
            ],
        ], 'relatorio-compras.pdf');
    }

    public function customerCardsPdf(Request $request)
    {
        [$from, $to] = $this->period($request);

        $cardsQuery = CustomerCard::with('customer')->orderBy('card_number');

        if ($request->filled('customer_id')) {
            $cardsQuery->where('customer_id', $request->integer('customer_id'));
        }

        if ($request->filled('status')) {
            $cardsQuery->where('status', $request->status);
        }

        $cards = $cardsQuery->get();
        $cardIds = $cards->pluck('id');

        $pointTransactions = PointTransaction::with('card.customer', 'sale')
            ->whereIn('customer_card_id', $cardIds)
            ->whereBetween('created_at', [$from, $to])
            ->orderBy('created_at')
            ->get();

        $balanceTransactions = CustomerCardBalanceTransaction::with('card.customer', 'sale', 'operator')
            ->whereIn('customer_card_id', $cardIds)
            ->whereBetween('created_at', [$from, $to])
            ->orderBy('created_at')
            ->get();

        $paymentsQuery = Payments::with('sale.customer')
            ->where('method', 'customer_card')
            ->whereBetween('created_at', [$from, $to]);

        if ($cardIds->isNotEmpty()) {
            $paymentsQuery->whereHas('sale', fn ($query) => $query->whereIn('customer_card_id', $cardIds));
        } else {
            $paymentsQuery->whereRaw('1 = 0');
        }

        $payments = $paymentsQuery->orderBy('created_at')->get();

        return $this->pdf('reports.customer-cards', [
            'title' => 'Relatorio de Cartao Cliente',
            'from' => $from,
            'to' => $to,
            'cards' => $cards,
            'pointTransactions' => $pointTransactions,
            'balanceTransactions' => $balanceTransactions,
            'payments' => $payments,
            'totals' => [
                'cards' => $cards->count(),
                'active' => $cards->where('status', 'active')->count(),
                'blocked' => $cards->where('status', 'blocked')->count(),
                'points_balance' => (int) $cards->sum('points'),
                'money_balance' => (float) $cards->sum('balance'),
                'points_earned' => (int) $pointTransactions->where('type', 'earn')->sum('points'),
                'points_used' => abs((int) $pointTransactions->whereIn('type', ['redeem', 'adjust'])->where('points', '<', 0)->sum('points')),
                'recharged' => (float) $balanceTransactions->where('type', 'recharge')->sum('amount'),
                'balance_used' => abs((float) $balanceTransactions->where('type', 'purchase')->sum('amount')),
                'paid_by_card' => (float) $payments->sum('amount'),
            ],
        ], 'relatorio-cartao-cliente.pdf');
    }

    public function shiftsPdf(Request $request)
    {
        [$from, $to] = $this->period($request);

        $query = Shift::with('operator')
            ->where(function ($query) use ($from, $to) {
                $query->whereBetween('opened_at', [$from, $to])
                    ->orWhereBetween('closed_at', [$from, $to]);
            })
            ->orderBy('opened_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $shifts = $query->get();

        return $this->pdf('reports.shifts', [
            'title' => 'Relatorio de Fechos de Caixa',
            'from' => $from,
            'to' => $to,
            'shifts' => $shifts,
            'totals' => [
                'count' => $shifts->count(),
                'open' => $shifts->where('status', 'open')->count(),
                'closed' => $shifts->where('status', 'closed')->count(),
                'opening_cash' => (float) $shifts->sum('opening_cash'),
                'closing_cash' => (float) $shifts->sum('closing_cash'),
                'expected_cash' => (float) $shifts->sum('expected_cash'),
                'difference' => (float) $shifts->sum('difference'),
                'sales' => (float) $shifts->sum('total_sales'),
            ],
        ], 'relatorio-fechos-caixa.pdf');
    }

    public function auditPdf(Request $request)
    {
        [$from, $to] = $this->period($request);

        $query = AuditLog::with('user')
            ->whereBetween('created_at', [$from, $to])
            ->latest();

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        if ($request->filled('model')) {
            $query->where('model', $request->model);
        }

        $logs = $query->get();

        return $this->pdf('reports.audit', [
            'title' => 'Relatorio de Auditoria',
            'from' => $from,
            'to' => $to,
            'logs' => $logs,
            'totals' => [
                'count' => $logs->count(),
                'actions' => $logs->pluck('action')->unique()->count(),
                'models' => $logs->pluck('model')->unique()->count(),
            ],
        ], 'relatorio-auditoria.pdf');
    }

    public function dailyPostingsPdf(Request $request)
    {
        [$from, $to] = $this->period($request);
        $data = $this->dailyPostingsData($from, $to);

        return $this->pdf('reports.daily-postings', array_merge($data, [
            'title' => 'Relatorio de Lancamento Diario',
            'from' => $from,
            'to' => $to,
        ]), 'relatorio-lancamento-diario.pdf');
    }

    private function period(Request $request): array
    {
        $from = Carbon::parse($request->input('from', now()->startOfMonth()->toDateString()))->startOfDay();
        $to = Carbon::parse($request->input('to', now()->toDateString()))->endOfDay();

        return [$from, $to];
    }

    private function pdf(string $view, array $data, string $filename)
    {
        $company = BusinessSettings::company();

        return Pdf::loadView('admin.' . $view, array_merge($data, [
            'company' => $company,
            'logoUrl' => BusinessSettings::logoDataUri($company),
            'generatedAt' => now(),
        ]))
            ->setPaper('a4', 'portrait')
            ->stream($filename);
    }

    private function dailyPostingsData(Carbon $from, Carbon $to): array
    {
        $sales = Sale::with('customer', 'operator')
            ->whereBetween('created_at', [$from, $to])
            ->orderBy('created_at')
            ->get();

        $creditNotes = CreditNote::with('customer', 'operator', 'originalSale')
            ->whereBetween('created_at', [$from, $to])
            ->orderBy('created_at')
            ->get();

        $payments = Payments::with('sale', 'creditNote', 'operator')
            ->whereBetween('created_at', [$from, $to])
            ->orderBy('created_at')
            ->get();

        $cashMovements = CashMovement::with('operator', 'currentAccountEntry')
            ->whereBetween('created_at', [$from, $to])
            ->orderBy('created_at')
            ->get();

        $currentAccountEntries = CurrentAccountEntry::with('operator')
            ->whereBetween('entry_date', [$from->toDateString(), $to->toDateString()])
            ->orderBy('entry_date')
            ->orderBy('id')
            ->get();

        $purchases = Purchase::with('supplier', 'operator')
            ->whereBetween('created_at', [$from, $to])
            ->orderBy('created_at')
            ->get();

        $shifts = Shift::with('operator')
            ->where(function ($query) use ($from, $to) {
                $query->whereBetween('opened_at', [$from, $to])
                    ->orWhereBetween('closed_at', [$from, $to]);
            })
            ->orderBy('opened_at')
            ->get();

        return [
            'sales' => $sales,
            'creditNotes' => $creditNotes,
            'payments' => $payments,
            'cashMovements' => $cashMovements,
            'currentAccountEntries' => $currentAccountEntries,
            'purchases' => $purchases,
            'shifts' => $shifts,
            'totals' => [
                'sales' => (float) $sales->sum('total'),
                'credit_notes' => (float) $creditNotes->sum('total'),
                'payments' => (float) $payments->sum('amount'),
                'cash_movements' => (float) $cashMovements->sum('amount'),
                'account_debit' => (float) $currentAccountEntries->sum('debit'),
                'account_credit' => (float) $currentAccountEntries->sum('credit'),
                'purchases' => (float) $purchases->sum('total'),
            ],
        ];
    }
}
