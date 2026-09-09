<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccountingAccount;
use App\Models\AccountingJournalEntry;
use Illuminate\Support\Facades\Schema;
use App\Services\AccountingPostingService;
use App\Models\Sale;
use App\Models\PurchaseReturn;
use App\Models\Purchase;
use App\Models\Expense;
use App\Models\CurrentAccountEntry;
use App\Models\CreditNote;
use App\Models\BankTransaction;
use App\Services\AuditLogger;
use App\Services\FiscalYearService;
use App\Services\OperatorPermissions;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AccountingController extends Controller
{
    public function index(Request $request): View
    {
        app(AccountingPostingService::class)->ensureStandardAccounts();

        $from = $request->input('from', now()->startOfMonth()->toDateString());
        $to = $request->input('to', now()->toDateString());

        $accounts = AccountingAccount::orderBy('code')->get();
        $activeAccounts = $accounts->where('active', true)->values();

        $entries = AccountingJournalEntry::with(['lines.account', 'poster'])
            ->whereBetween('entry_date', [$from, $to])
            ->latest('entry_date')
            ->latest('id')
            ->paginate(12)
            ->withQueryString();

        $trialBalance = AccountingAccount::query()
            ->leftJoin('accounting_journal_lines', 'accounting_accounts.id', '=', 'accounting_journal_lines.accounting_account_id')
            ->leftJoin('accounting_journal_entries', 'accounting_journal_entries.id', '=', 'accounting_journal_lines.journal_entry_id')
            ->select('accounting_accounts.id', 'accounting_accounts.code', 'accounting_accounts.name', 'accounting_accounts.type')
            ->selectRaw('COALESCE(SUM(CASE WHEN accounting_journal_entries.entry_date BETWEEN ? AND ? THEN accounting_journal_lines.debit ELSE 0 END), 0) as debit', [$from, $to])
            ->selectRaw('COALESCE(SUM(CASE WHEN accounting_journal_entries.entry_date BETWEEN ? AND ? THEN accounting_journal_lines.credit ELSE 0 END), 0) as credit', [$from, $to])
            ->groupBy('accounting_accounts.id', 'accounting_accounts.code', 'accounting_accounts.name', 'accounting_accounts.type')
            ->orderBy('accounting_accounts.code')
            ->get()
            ->map(function ($row) {
                $row->balance = round((float) $row->debit - (float) $row->credit, 2);

                return $row;
            });

        return view('admin.accounting.index', [
            'from' => $from,
            'to' => $to,
            'accounts' => $accounts,
            'activeAccounts' => $activeAccounts,
            'entries' => $entries,
            'trialBalance' => $trialBalance,
            'totalDebit' => round((float) $trialBalance->sum('debit'), 2),
            'totalCredit' => round((float) $trialBalance->sum('credit'), 2),
            'canManageAccounting' => OperatorPermissions::allows(session('operator_role'), 'accounting.manage'),
            'sourceLabels' => $this->sourceLabels(),
            'unpostedDocuments' => $this->unpostedDocuments($from, $to),
        ]);
    }

    private function unpostedDocuments(string $from, string $to)
    {
        $documents = collect();

        if (Schema::hasTable('sales')) {
            $query = Sale::query()
                ->whereBetween('created_at', [Carbon::parse($from)->startOfDay(), Carbon::parse($to)->endOfDay()]);

            if (Schema::hasColumn('sales', 'is_proforma')) {
                $query->where(function ($q) {
                    $q->whereNull('is_proforma')->orWhere('is_proforma', false);
                });
            }

            $documents = $documents->merge($this->missingSource($query, 'sale', 'created_at', 'invoice_number', 'Venda', 'total'));
        }

        if (Schema::hasTable('credit_notes')) {
            $documents = $documents->merge($this->missingSource(
                CreditNote::query()->whereBetween('created_at', [Carbon::parse($from)->startOfDay(), Carbon::parse($to)->endOfDay()]),
                'credit_note',
                'created_at',
                'invoice_number',
                'Nota de credito',
                'total'
            ));
        }

        if (Schema::hasTable('purchases')) {
            $documents = $documents->merge($this->missingSource(
                Purchase::query()->where('approval_status', Purchase::APPROVAL_APPROVED)->whereBetween('purchase_date', [$from, $to]),
                'purchase',
                'purchase_date',
                'document_number',
                'Compra',
                'total'
            ));
        }

        if (Schema::hasTable('purchase_returns')) {
            $documents = $documents->merge($this->missingSource(
                PurchaseReturn::query()->whereBetween('return_date', [$from, $to]),
                'purchase_return',
                'return_date',
                'document_number',
                'Devolucao fornecedor',
                'total'
            ));
        }

        if (Schema::hasTable('expenses')) {
            $documents = $documents->merge($this->missingSource(
                Expense::query()->whereBetween('expense_date', [$from, $to]),
                'expense',
                'expense_date',
                'document_number',
                'Despesa',
                'amount'
            ));
        }

        if (Schema::hasTable('current_account_entries')) {
            $documents = $documents->merge($this->missingSource(
                CurrentAccountEntry::query()->where('document_type', 'current_account_settlement')->whereBetween('entry_date', [$from, $to]),
                'current_account_entry',
                'entry_date',
                'description',
                'Liquidacao conta corrente',
                DB::raw('debit + credit')
            ));
        }

        if (Schema::hasTable('bank_transactions')) {
            $documents = $documents->merge($this->missingSource(
                BankTransaction::query()->where('method', 'manual')->whereBetween('transaction_date', [$from, $to]),
                'bank_transaction',
                'transaction_date',
                'reference',
                'Movimento bancario manual',
                'amount'
            ));
        }

        return $documents->sortByDesc('date')->take(25)->values();
    }

    private function missingSource($query, string $sourceType, string $dateColumn, string $documentColumn, string $label, string|\Illuminate\Database\Query\Expression $amountColumn)
    {
        $table = $query->getModel()->getTable();

        return $query
            ->whereNotExists(function ($subquery) use ($sourceType, $table) {
                $subquery->selectRaw('1')
                    ->from('accounting_journal_entries')
                    ->where('source_type', $sourceType)
                    ->whereColumn('source_id', $table . '.id');
            })
            ->limit(25)
            ->get()
            ->map(function ($row) use ($sourceType, $dateColumn, $documentColumn, $label, $amountColumn) {
                return (object) [
                    'source_type' => $sourceType,
                    'label' => $label,
                    'date' => Carbon::parse($row->{$dateColumn}),
                    'document' => $row->{$documentColumn} ?: ('#' . $row->id),
                    'amount' => is_string($amountColumn) ? (float) $row->{$amountColumn} : (float) ((float) $row->debit + (float) $row->credit),
                ];
            });
    }

    private function sourceLabels(): array
    {
        return [
            'sale' => 'Venda',
            'credit_note' => 'Nota de credito',
            'purchase' => 'Compra',
            'purchase_return' => 'Devolucao fornecedor',
            'expense' => 'Despesa',
            'current_account_entry' => 'Liquidacao CC',
            'bank_transaction' => 'Banco manual',
        ];
    }

    public function storeAccount(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:30', 'unique:accounting_accounts,code'],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(['asset', 'liability', 'equity', 'income', 'expense'])],
            'parent_id' => ['nullable', 'exists:accounting_accounts,id'],
            'active' => ['nullable', 'boolean'],
        ]);

        $account = AccountingAccount::create([
            'code' => trim($validated['code']),
            'name' => trim($validated['name']),
            'type' => $validated['type'],
            'parent_id' => $validated['parent_id'] ?? null,
            'active' => $request->boolean('active', true),
        ]);

        AuditLogger::log('accounting_account_created', 'AccountingAccount', $account->id, [
            'code' => $account->code,
            'name' => $account->name,
            'type' => $account->type,
        ], 'warning');

        return back()->with('success', 'Conta contabilistica criada.');
    }

    public function storeJournalEntry(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'entry_date' => ['required', 'date'],
            'document_number' => ['nullable', 'string', 'max:80'],
            'description' => ['required', 'string', 'max:255'],
            'lines' => ['required', 'array', 'min:2'],
            'lines.*.accounting_account_id' => ['required', Rule::exists('accounting_accounts', 'id')->where('active', true)],
            'lines.*.debit' => ['nullable', 'numeric', 'min:0'],
            'lines.*.credit' => ['nullable', 'numeric', 'min:0'],
            'lines.*.memo' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            FiscalYearService::assertDateIsOpen($validated['entry_date']);
        } catch (\Throwable $e) {
            return back()->withInput()->withErrors(['entry_date' => $e->getMessage()]);
        }

        $lines = collect($validated['lines'])
            ->map(function (array $line) {
                return [
                    'accounting_account_id' => (int) $line['accounting_account_id'],
                    'debit' => round((float) ($line['debit'] ?? 0), 2),
                    'credit' => round((float) ($line['credit'] ?? 0), 2),
                    'memo' => $line['memo'] ?? null,
                ];
            })
            ->filter(fn ($line) => $line['debit'] > 0 || $line['credit'] > 0)
            ->values();

        if ($lines->count() < 2) {
            return back()->withInput()->withErrors(['lines' => 'Informe pelo menos duas linhas contabilisticas.']);
        }

        if ($lines->contains(fn ($line) => $line['debit'] > 0 && $line['credit'] > 0)) {
            return back()->withInput()->withErrors(['lines' => 'Cada linha deve ter debito ou credito, nao ambos.']);
        }

        $totalDebit = round((float) $lines->sum('debit'), 2);
        $totalCredit = round((float) $lines->sum('credit'), 2);

        if ($totalDebit <= 0 || abs($totalDebit - $totalCredit) > 0.001) {
            return back()->withInput()->withErrors(['lines' => 'O lancamento deve estar balanceado: total de debito igual ao total de credito.']);
        }

        $entry = DB::transaction(function () use ($validated, $lines) {
            $entry = AccountingJournalEntry::create([
                'entry_date' => Carbon::parse($validated['entry_date'])->toDateString(),
                'document_number' => $validated['document_number'] ?? null,
                'description' => trim($validated['description']),
                'status' => 'posted',
                'posted_by' => session('operator_id'),
            ]);

            foreach ($lines as $line) {
                $entry->lines()->create($line);
            }

            return $entry;
        });

        AuditLogger::log('accounting_journal_entry_posted', 'AccountingJournalEntry', $entry->id, [
            'document_number' => $entry->document_number,
            'description' => $entry->description,
            'debit' => $totalDebit,
            'credit' => $totalCredit,
        ], 'warning');

        return back()->with('success', 'Lancamento contabilistico registado.');
    }
}
