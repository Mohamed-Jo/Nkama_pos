<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\CashMovement;
use App\Models\CurrentAccountEntry;
use App\Models\Expense;
use App\Models\Shift;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class FinanceController extends Controller
{
    public function index(Request $request): View
    {
        $from = $request->input('from', now()->startOfMonth()->toDateString());
        $to = $request->input('to', now()->toDateString());

        $expenses = Expense::with(['supplier', 'operator', 'bankAccount'])
            ->whereBetween('expense_date', [$from, $to])
            ->latest('expense_date')
            ->latest('id')
            ->paginate(10, ['*'], 'expenses_page')
            ->withQueryString();

        $bankTransactions = BankTransaction::with(['bankAccount', 'operator', 'expense'])
            ->whereBetween('transaction_date', [$from, $to])
            ->latest('transaction_date')
            ->latest('id')
            ->paginate(10, ['*'], 'bank_page')
            ->withQueryString();

        $customerBalances = $this->balances('customer');
        $supplierBalances = $this->balances('supplier');
        $cashMovementsTotal = (float) CashMovement::whereBetween('created_at', [Carbon::parse($from)->startOfDay(), Carbon::parse($to)->endOfDay()])->sum('amount');

        return view('admin.finance.index', [
            'from' => $from,
            'to' => $to,
            'suppliers' => Supplier::where('status', true)->orderBy('company_name')->get(),
            'bankAccounts' => BankAccount::orderByDesc('active')->orderBy('name')->get(),
            'activeBankAccounts' => BankAccount::where('active', true)->orderBy('name')->get(),
            'expenses' => $expenses,
            'bankTransactions' => $bankTransactions,
            'receivable' => $customerBalances->sum('balance'),
            'payable' => $supplierBalances->sum(fn ($row) => abs(min((float) $row->balance, 0))),
            'supplierCreditBalance' => $supplierBalances->sum(fn ($row) => max((float) $row->balance, 0)),
            'expenseTotal' => (float) Expense::whereBetween('expense_date', [$from, $to])->where('status', Expense::STATUS_PAID)->sum('amount'),
            'pendingExpenses' => (float) Expense::where('status', Expense::STATUS_PENDING)->sum('amount'),
            'bankBalance' => (float) BankAccount::where('active', true)->sum('current_balance'),
            'unreconciled' => BankTransaction::where('reconciled', false)->count(),
            'cashMovementsTotal' => $cashMovementsTotal,
            'customerBalances' => $customerBalances,
            'supplierBalances' => $supplierBalances,
        ]);
    }

    public function storeBankAccount(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'bank_name' => ['nullable', 'string', 'max:120'],
            'account_number' => ['nullable', 'string', 'max:80'],
            'iban' => ['nullable', 'string', 'max:80'],
            'currency' => ['nullable', 'string', 'max:12'],
            'opening_balance' => ['nullable', 'numeric'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $opening = round((float) ($validated['opening_balance'] ?? 0), 2);

        BankAccount::create([
            'name' => $validated['name'],
            'bank_name' => $validated['bank_name'] ?? null,
            'account_number' => $validated['account_number'] ?? null,
            'iban' => $validated['iban'] ?? null,
            'currency' => strtoupper($validated['currency'] ?? 'AOA'),
            'opening_balance' => $opening,
            'current_balance' => $opening,
            'notes' => $validated['notes'] ?? null,
            'active' => true,
        ]);

        return back()->with('success', 'Conta bancaria criada.');
    }

    public function storeExpense(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'bank_account_id' => ['nullable', 'exists:bank_accounts,id'],
            'category' => ['required', 'string', 'max:80'],
            'description' => ['required', 'string', 'max:255'],
            'document_number' => ['nullable', 'string', 'max:80'],
            'expense_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date'],
            'payment_method' => ['required', Rule::in(['pending', 'cash', 'card', 'transf', 'bank'])],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            DB::transaction(function () use ($validated) {
                $amount = round((float) $validated['amount'], 2);
                $method = $validated['payment_method'];
                $status = $method === 'pending' ? Expense::STATUS_PENDING : Expense::STATUS_PAID;
                $operatorId = session('operator_id');
                $shift = null;

                if (in_array($method, ['cash', 'card', 'transf'], true)) {
                    $shift = Shift::where('operator_id', $operatorId)->where('status', 'open')->lockForUpdate()->first();
                    if (! $shift) {
                        throw new \RuntimeException('Abra o caixa antes de registar despesa paga por caixa.');
                    }
                }

                if ($method === 'bank' && empty($validated['bank_account_id'])) {
                    throw new \RuntimeException('Selecione a conta bancaria para despesa paga por banco.');
                }

                $expense = Expense::create([
                    'supplier_id' => $validated['supplier_id'] ?? null,
                    'operator_id' => $operatorId,
                    'bank_account_id' => $method === 'bank' ? $validated['bank_account_id'] : null,
                    'shift_id' => $shift?->id,
                    'category' => $validated['category'],
                    'description' => $validated['description'],
                    'document_number' => $validated['document_number'] ?? null,
                    'expense_date' => $validated['expense_date'],
                    'due_date' => $validated['due_date'] ?? null,
                    'payment_method' => $method === 'pending' ? null : $method,
                    'amount' => $amount,
                    'status' => $status,
                    'paid_at' => $status === Expense::STATUS_PAID ? now() : null,
                    'notes' => $validated['notes'] ?? null,
                ]);

                if ($shift) {
                    CashMovement::create([
                        'shift_id' => $shift->id,
                        'operator_id' => $operatorId,
                        'type' => 'expense',
                        'method' => $method,
                        'amount' => -1 * $amount,
                        'description' => $expense->description,
                    ]);
                }

                if ($method === 'bank') {
                    $this->recordBankTransaction(
                        BankAccount::lockForUpdate()->findOrFail((int) $validated['bank_account_id']),
                        'debit',
                        $amount,
                        $validated['expense_date'],
                        $expense->description,
                        $validated['document_number'] ?? null,
                        'expense',
                        $expense->id
                    );
                }
            });
        } catch (\Throwable $e) {
            report($e);

            return back()->withInput()->withErrors(['amount' => $e->getMessage()]);
        }

        return back()->with('success', 'Despesa registada.');
    }

    public function storeBankTransaction(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'bank_account_id' => ['required', 'exists:bank_accounts,id'],
            'type' => ['required', Rule::in(['credit', 'debit'])],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'transaction_date' => ['required', 'date'],
            'reference' => ['nullable', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($validated) {
            $this->recordBankTransaction(
                BankAccount::lockForUpdate()->findOrFail((int) $validated['bank_account_id']),
                $validated['type'],
                round((float) $validated['amount'], 2),
                $validated['transaction_date'],
                $validated['description'] ?? null,
                $validated['reference'] ?? null,
                'manual'
            );
        });

        return back()->with('success', 'Movimento bancario registado.');
    }

    public function reconcile(BankTransaction $bankTransaction): RedirectResponse
    {
        $bankTransaction->update([
            'reconciled' => true,
            'reconciled_at' => now(),
            'reconciled_by' => session('operator_id'),
        ]);

        return back()->with('success', 'Movimento reconciliado.');
    }

    private function recordBankTransaction(BankAccount $account, string $type, float $amount, string $date, ?string $description, ?string $reference, string $method, ?int $expenseId = null): BankTransaction
    {
        $before = round((float) $account->current_balance, 2);
        $after = $type === 'credit' ? $before + $amount : $before - $amount;

        $account->update(['current_balance' => round($after, 2)]);

        return BankTransaction::create([
            'bank_account_id' => $account->id,
            'operator_id' => session('operator_id'),
            'expense_id' => $expenseId,
            'type' => $type,
            'method' => $method,
            'amount' => $amount,
            'balance_before' => $before,
            'balance_after' => round($after, 2),
            'transaction_date' => $date,
            'reference' => $reference,
            'description' => $description,
        ]);
    }

    private function balances(string $entityType)
    {
        return CurrentAccountEntry::query()
            ->select('entity_type', 'entity_id')
            ->selectRaw('COALESCE(SUM(debit), 0) as debit')
            ->selectRaw('COALESCE(SUM(credit), 0) as credit')
            ->where('entity_type', $entityType)
            ->groupBy('entity_type', 'entity_id')
            ->get()
            ->map(function ($row) use ($entityType) {
                $row->name = $entityType === 'customer'
                    ? \App\Models\Customer::find($row->entity_id)?->name
                    : Supplier::find($row->entity_id)?->company_name;
                $row->balance = round((float) $row->debit - (float) $row->credit, 2);

                return $row;
            })
            ->filter(fn ($row) => $row->name && abs((float) $row->balance) > 0.0001)
            ->values();
    }
}