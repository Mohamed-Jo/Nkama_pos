<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CashMovement;
use App\Models\CreditNote;
use App\Models\CurrentAccountEntry;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\Shift;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CurrentAccountController extends Controller
{
    public function index(Request $request): View
    {
        $customers = Customer::where('status', true)->orderBy('name')->get();
        $suppliers = Supplier::where('status', true)->orderBy('company_name')->get();

        $query = CurrentAccountEntry::query()->with('operator')->latest('entry_date')->latest('id');

        if ($request->filled('entity_type')) {
            $query->where('entity_type', $request->entity_type);
        }

        if ($request->filled('entity_type') && $request->filled('entity_id')) {
            $query->where('entity_id', $request->integer('entity_id'));
        }

        $entries = $query->paginate(15)->withQueryString();

        $totalsQuery = CurrentAccountEntry::query();

        if ($request->filled('entity_type')) {
            $totalsQuery->where('entity_type', $request->entity_type);
        }

        if ($request->filled('entity_type') && $request->filled('entity_id')) {
            $totalsQuery->where('entity_id', $request->integer('entity_id'));
        }

        $totals = $totalsQuery
            ->selectRaw('COALESCE(SUM(debit), 0) as debit, COALESCE(SUM(credit), 0) as credit')
            ->first();

        $balances = CurrentAccountEntry::query()
            ->select('entity_type', 'entity_id')
            ->selectRaw('COALESCE(SUM(debit), 0) as debit')
            ->selectRaw('COALESCE(SUM(credit), 0) as credit')
            ->groupBy('entity_type', 'entity_id')
            ->orderBy('entity_type')
            ->get()
            ->map(function ($row) {
                $row->name = $row->entity_type === 'customer'
                    ? Customer::find($row->entity_id)?->name
                    : Supplier::find($row->entity_id)?->company_name;

                $row->balance = (float) $row->debit - (float) $row->credit;

                return $row;
            })
            ->filter(fn ($row) => $row->name)
            ->values();

        return view('admin.current-accounts.index', [
            'customers' => $customers,
            'suppliers' => $suppliers,
            'entries' => $entries,
            'balances' => $balances,
            'totalDebit' => (float) $totals->debit,
            'totalCredit' => (float) $totals->credit,
            'balance' => (float) $totals->debit - (float) $totals->credit,
            'filters' => $request->only(['entity_type', 'entity_id']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'entity_type' => ['required', Rule::in(['customer', 'supplier'])],
            'entity_id' => ['required', 'integer', 'min:1'],
            'movement_type' => ['required', Rule::in(['debit', 'credit'])],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'entry_date' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $exists = $validated['entity_type'] === 'customer'
            ? Customer::whereKey($validated['entity_id'])->exists()
            : Supplier::whereKey($validated['entity_id'])->exists();

        if (! $exists) {
            return back()
                ->withInput()
                ->withErrors(['entity_id' => 'A entidade selecionada não existe.']);
        }

        DB::transaction(function () use ($validated) {
            CurrentAccountEntry::create([
                'entity_type' => $validated['entity_type'],
                'entity_id' => $validated['entity_id'],
                'entry_date' => $validated['entry_date'],
                'movement_type' => $validated['movement_type'],
                'debit' => $validated['movement_type'] === 'debit' ? $validated['amount'] : 0,
                'credit' => $validated['movement_type'] === 'credit' ? $validated['amount'] : 0,
                'description' => $validated['description'] ?? null,
                'operator_id' => session('operator_id'),
            ]);
        });

        return back()->with('success', 'Movimento registado na conta corrente.');
    }

    public function settle(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'operation' => ['required', Rule::in(['customer_receipt', 'supplier_payment'])],
            'entity_id' => ['required', 'integer', 'min:1'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'method' => ['required', Rule::in(['cash', 'card', 'transf'])],
            'entry_date' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $entityType = $validated['operation'] === 'customer_receipt' ? 'customer' : 'supplier';
        $exists = $entityType === 'customer'
            ? Customer::whereKey($validated['entity_id'])->exists()
            : Supplier::whereKey($validated['entity_id'])->exists();

        if (! $exists) {
            return back()
                ->withInput()
                ->withErrors(['entity_id' => 'A entidade selecionada nao existe.']);
        }

        try {
            DB::transaction(function () use ($validated, $entityType) {
                $operatorId = session('operator_id');
                $shift = Shift::where('operator_id', $operatorId)
                    ->where('status', 'open')
                    ->lockForUpdate()
                    ->first();

                if (! $shift) {
                    throw new \RuntimeException('Abra o caixa antes de liquidar conta corrente.');
                }

                $isReceipt = $validated['operation'] === 'customer_receipt';
                $amount = round((float) $validated['amount'], 2);

                $entry = CurrentAccountEntry::create([
                    'entity_type' => $entityType,
                    'entity_id' => $validated['entity_id'],
                    'entry_date' => $validated['entry_date'],
                    'movement_type' => $isReceipt ? 'credit' : 'debit',
                    'debit' => $isReceipt ? 0 : $amount,
                    'credit' => $isReceipt ? $amount : 0,
                    'document_type' => 'current_account_settlement',
                    'description' => $validated['description']
                        ?: ($isReceipt ? 'Recebimento de cliente' : 'Pagamento a fornecedor'),
                    'operator_id' => $operatorId,
                ]);

                CashMovement::create([
                    'shift_id' => $shift->id,
                    'operator_id' => $operatorId,
                    'current_account_entry_id' => $entry->id,
                    'type' => $validated['operation'],
                    'method' => $validated['method'],
                    'amount' => $isReceipt ? $amount : -1 * $amount,
                    'description' => $entry->description,
                ]);

                if ($isReceipt) {
                    $this->allocateCustomerReceipt((int) $validated['entity_id'], $amount);
                }
            });
        } catch (\Throwable $e) {
            report($e);

            return back()
                ->withInput()
                ->withErrors(['amount' => $e->getMessage()]);
        }

        return back()->with('success', 'Conta corrente liquidada com reflexo no caixa.');
    }

    private function allocateCustomerReceipt(int $customerId, float $amount): void
    {
        $remaining = round($amount, 2);

        $sales = Sale::where('customer_id', $customerId)
            ->where('document_type_code', 'FT')
            ->where(function ($query) {
                $query->whereNull('payment_status')
                    ->orWhere('payment_status', '<>', 'paid');
            })
            ->orderBy('created_at')
            ->lockForUpdate()
            ->get();

        foreach ($sales as $sale) {
            if ($remaining <= 0) {
                break;
            }

            $credited = (float) CreditNote::where('original_sale_id', $sale->id)->sum('total');
            $due = round(max((float) $sale->total - $credited - (float) $sale->paid, 0), 2);

            if ($due <= 0) {
                $this->markSalePaymentState($sale, $credited);
                continue;
            }

            $applied = min($remaining, $due);
            $sale->paid = round((float) $sale->paid + $applied, 2);
            $this->markSalePaymentState($sale, $credited);
            $remaining = round($remaining - $applied, 2);
        }
    }

    private function markSalePaymentState(Sale $sale, float $credited): void
    {
        $effectiveTotal = round(max((float) $sale->total - $credited, 0), 2);
        $status = (float) $sale->paid >= $effectiveTotal ? 'paid' : ((float) $sale->paid > 0 ? 'partial' : 'unpaid');

        $payload = [
            'paid' => $sale->paid,
            'payment_status' => $status,
        ];

        if (Schema::hasColumn('sales', 'status')) {
            $payload['status'] = $status;
        }

        $sale->update($payload);
    }
}
