@extends('layouts.admin')

@section('page-title', 'Financeiro')

@section('content')
<style>
.fin-shell { display:grid; gap:12px; max-width:1480px; }
.fin-top { align-items:center; display:grid; gap:12px; grid-template-columns:minmax(0,1fr) auto; }
.fin-title { color:var(--text); font-size:21px; font-weight:900; margin:0; }
.fin-subtitle { color:var(--muted); font-size:12px; margin-top:3px; }
.fin-filter { align-items:end; background:var(--card); border:1px solid var(--border); border-radius:8px; display:flex; gap:8px; padding:8px; }
.fin-kpis { display:grid; gap:8px; grid-template-columns:repeat(5,minmax(0,1fr)); }
.fin-kpi { background:var(--card); border:1px solid var(--border); border-radius:8px; min-height:66px; padding:10px 12px; }
.fin-kpi span { color:var(--muted); display:block; font-size:10px; font-weight:900; letter-spacing:.05em; text-transform:uppercase; }
.fin-kpi strong { color:var(--text); display:block; font-size:17px; margin-top:6px; white-space:nowrap; }
.fin-workbench { display:grid; gap:12px; grid-template-columns:1fr 340px; }
.fin-actions-panel { background:var(--card); border:1px solid var(--border); border-radius:8px; padding:10px; }
.fin-actions-grid { align-items:start; display:grid; gap:8px; grid-template-columns:minmax(560px,1.85fr) minmax(210px,.68fr) minmax(260px,.82fr); }
.fin-details { background:var(--soft-bg); border:1px solid var(--border); border-radius:8px; overflow:hidden; }
.fin-details summary { color:var(--text); cursor:pointer; font-size:12px; font-weight:900; list-style:none; padding:9px 10px; }
.fin-details summary::-webkit-details-marker { display:none; }
.fin-details[open] summary { border-bottom:1px solid var(--border); color:var(--primary); }
.fin-details-body { padding:10px; }
.fin-panel { background:var(--card); border:1px solid var(--border); border-radius:8px; min-width:0; padding:10px; }
.fin-section-head { align-items:center; display:flex; justify-content:space-between; gap:8px; margin-bottom:8px; }
.fin-section-title { color:var(--text); font-size:13px; font-weight:900; margin:0; }
.fin-pill { background:rgba(249,115,22,.10); border:1px solid rgba(249,115,22,.22); border-radius:999px; color:var(--primary); font-size:11px; font-weight:800; padding:3px 8px; }
.fin-form { display:grid; gap:8px; grid-template-columns:repeat(6,minmax(0,1fr)); }
.fin-form.expense { grid-template-columns:repeat(8,minmax(0,1fr)); }
.fin-form.compact { grid-template-columns:1fr; }
.fin-form.compact .fin-span-2 { grid-column:span 1; }
.fin-field { display:flex; flex-direction:column; gap:4px; min-width:0; }
.fin-field label { color:var(--muted); font-size:9px; font-weight:900; letter-spacing:.05em; text-transform:uppercase; }
.fin-field input, .fin-field select, .fin-field textarea { background:var(--input-bg); border:1px solid var(--border); border-radius:7px; color:var(--input-text); font-size:12px; height:32px; min-width:0; padding:6px 8px; width:100%; }
.fin-field textarea { height:48px; resize:vertical; }
.fin-span-2 { grid-column:span 2; }
.fin-span-3 { grid-column:span 3; }
.fin-span-4 { grid-column:span 4; }
.fin-span-6 { grid-column:span 6; }
.fin-btn { align-items:center; border:0; border-radius:7px; cursor:pointer; display:inline-flex; font-size:12px; font-weight:900; height:32px; justify-content:center; padding:0 10px; text-decoration:none; white-space:nowrap; }
.fin-btn-primary { background:var(--primary); color:#111827; }
.fin-btn-ghost { background:var(--soft-bg); border:1px solid var(--border); color:var(--text); }
.fin-main-grid { display:grid; gap:12px; grid-template-columns:minmax(0,1.15fr) minmax(320px,.85fr); }
.fin-table-wrap { border:1px solid var(--border); border-radius:8px; overflow:auto; }
.fin-table { border-collapse:collapse; min-width:680px; width:100%; }
.fin-table.compact { min-width:0; }
.fin-table th { background:var(--soft-bg); color:var(--muted); font-size:10px; letter-spacing:.05em; padding:7px 8px; text-align:left; text-transform:uppercase; white-space:nowrap; }
.fin-table td { border-top:1px solid var(--border); color:var(--text); font-size:12px; padding:7px 8px; vertical-align:top; }
.fin-muted { color:var(--muted); font-size:11px; }
.fin-good { color:#34d399; font-weight:900; }
.fin-bad { color:#fb7185; font-weight:900; }
.fin-warn { color:#fbbf24; font-weight:900; }
.fin-stack { display:grid; gap:12px; }
.fin-pagination { margin-top:8px; }
:root[data-theme="light"] .fin-good { color:#047857; }
:root[data-theme="light"] .fin-bad { color:#be123c; }
:root[data-theme="light"] .fin-warn { color:#b45309; }
@media (max-width:1180px){ .fin-top,.fin-workbench,.fin-main-grid{grid-template-columns:1fr;} .fin-kpis{grid-template-columns:repeat(2,minmax(0,1fr));} .fin-actions-grid{grid-template-columns:1fr;} }
@media (max-width:760px){ .fin-filter{align-items:stretch; flex-direction:column;} .fin-form,.fin-form.compact{grid-template-columns:1fr;} .fin-span-2,.fin-span-3,.fin-span-4,.fin-span-6{grid-column:span 1;} .fin-kpis{grid-template-columns:1fr;} }
</style>

<div class="fin-shell">
    <div class="fin-top">
        <div>
            <h1 class="fin-title">Financeiro</h1>
            <div class="fin-subtitle">Visao diaria de despesas, bancos, reconciliacao e saldos de conta corrente.</div>
        </div>
        <form method="GET" class="fin-filter">
            <div class="fin-field"><label>De</label><input type="date" name="from" value="{{ $from }}"></div>
            <div class="fin-field"><label>Ate</label><input type="date" name="to" value="{{ $to }}"></div>
            <button class="fin-btn fin-btn-primary">Filtrar</button>
        </form>
    </div>

    <div class="fin-kpis">
        <div class="fin-kpi"><span>A receber</span><strong>{{ number_format($receivable, 2, ',', '.') }} Kz</strong></div>
        <div class="fin-kpi"><span>A pagar</span><strong>{{ number_format($payable, 2, ',', '.') }} Kz</strong></div>
        <div class="fin-kpi"><span>Despesas pagas</span><strong>{{ number_format($expenseTotal, 2, ',', '.') }} Kz</strong></div>
        <div class="fin-kpi"><span>Saldo bancos</span><strong>{{ number_format($bankBalance, 2, ',', '.') }} Kz</strong></div>
        <div class="fin-kpi"><span>Por reconciliar</span><strong>{{ $unreconciled }}</strong></div>
    </div>

    <div class="fin-actions-panel">
        <div class="fin-section-head">
            <h2 class="fin-section-title">Acoes rapidas</h2>
            <span class="fin-pill">Lancamentos</span>
        </div>
        <div class="fin-actions-grid">
            <details class="fin-details" open>
                <summary>Nova despesa</summary>
                <div class="fin-details-body">
                    <form method="POST" action="{{ route('admin.finance.expenses.store') }}" class="fin-form expense">
                        @csrf
                        <div class="fin-field fin-span-3"><label>Descricao</label><input name="description" required maxlength="255" value="{{ old('description') }}"></div>
                        <div class="fin-field"><label>Categoria</label><input name="category" required value="{{ old('category', 'Geral') }}"></div>
                        <div class="fin-field"><label>Valor</label><input type="number" name="amount" min="0.01" step="0.01" required value="{{ old('amount') }}"></div>
                        <div class="fin-field"><label>Data</label><input type="date" name="expense_date" required value="{{ old('expense_date', now()->toDateString()) }}"></div>
                        <div class="fin-field"><label>Fornecedor</label><select name="supplier_id"><option value="">Sem fornecedor</option>@foreach($suppliers as $supplier)<option value="{{ $supplier->id }}" @selected(old('supplier_id') == $supplier->id)>{{ $supplier->company_name }}</option>@endforeach</select></div>
                        <div class="fin-field"><label>Pagamento</label><select name="payment_method" required><option value="pending">Pendente</option><option value="cash">Dinheiro</option><option value="card">Cartao</option><option value="transf">Transferencia</option><option value="bank">Banco</option></select></div>
                        <div class="fin-field"><label>Banco</label><select name="bank_account_id"><option value="">Selecionar</option>@foreach($activeBankAccounts as $account)<option value="{{ $account->id }}">{{ $account->name }}</option>@endforeach</select></div>
                        <div class="fin-field"><label>Documento</label><input name="document_number" maxlength="80" value="{{ old('document_number') }}"></div>
                        <div class="fin-field"><label>Vencimento</label><input type="date" name="due_date" value="{{ old('due_date') }}"></div>
                        <div class="fin-field fin-span-4"><label>Notas</label><textarea name="notes">{{ old('notes') }}</textarea></div>
                        <button class="fin-btn fin-btn-primary">Registar</button>
                    </form>
                </div>
            </details>

            <details class="fin-details">
                <summary>Conta bancaria</summary>
                <div class="fin-details-body">
                    <form method="POST" action="{{ route('admin.finance.bank-accounts.store') }}" class="fin-form compact">
                        @csrf
                        <div class="fin-field"><label>Nome</label><input name="name" required></div>
                        <div class="fin-field"><label>Banco</label><input name="bank_name"></div>
                        <div class="fin-field"><label>Numero</label><input name="account_number"></div>
                        <div class="fin-field"><label>Moeda</label><input name="currency" value="AOA"></div>
                        <div class="fin-field"><label>Saldo inicial</label><input type="number" step="0.01" name="opening_balance" value="0"></div>
                        <button class="fin-btn fin-btn-primary">Criar</button>
                    </form>
                </div>
            </details>

            <details class="fin-details">
                <summary>Movimento bancario</summary>
                <div class="fin-details-body">
                    <form method="POST" action="{{ route('admin.finance.bank-transactions.store') }}" class="fin-form compact">
                        @csrf
                        <div class="fin-field fin-span-2"><label>Conta</label><select name="bank_account_id" required>@foreach($activeBankAccounts as $account)<option value="{{ $account->id }}">{{ $account->name }} - {{ number_format($account->current_balance, 2, ',', '.') }}</option>@endforeach</select></div>
                        <div class="fin-field"><label>Tipo</label><select name="type" required><option value="credit">Entrada</option><option value="debit">Saida</option></select></div>
                        <div class="fin-field"><label>Valor</label><input type="number" step="0.01" min="0.01" name="amount" required></div>
                        <div class="fin-field"><label>Data</label><input type="date" name="transaction_date" value="{{ now()->toDateString() }}" required></div>
                        <div class="fin-field"><label>Referencia</label><input name="reference"></div>
                        <div class="fin-field fin-span-2"><label>Descricao</label><input name="description"></div>
                        <button class="fin-btn fin-btn-primary">Lancar</button>
                    </form>
                </div>
            </details>
        </div>
    </div>

    <div class="fin-main-grid">
        <div class="fin-stack">
            <div class="fin-panel">
                <div class="fin-section-head"><h2 class="fin-section-title">Despesas</h2><span class="fin-pill">{{ $expenses->total() }}</span></div>
                <div class="fin-table-wrap"><table class="fin-table"><thead><tr><th>Data</th><th>Descricao</th><th>Categoria</th><th>Fornecedor</th><th>Estado</th><th>Valor</th></tr></thead><tbody>
                    @forelse($expenses as $expense)
                        <tr><td>{{ $expense->expense_date?->format('d/m/Y') }}</td><td>{{ $expense->description }}<div class="fin-muted">{{ $expense->document_number }}</div></td><td>{{ $expense->category }}</td><td>{{ $expense->supplier?->company_name ?? '-' }}</td><td><span class="{{ $expense->status === 'paid' ? 'fin-good' : 'fin-warn' }}">{{ $expense->status === 'paid' ? 'Paga' : 'Pendente' }}</span></td><td>{{ number_format($expense->amount, 2, ',', '.') }} Kz</td></tr>
                    @empty
                        <tr><td colspan="6" class="fin-muted">Sem despesas no periodo.</td></tr>
                    @endforelse
                </tbody></table></div>
                <div class="fin-pagination">{{ $expenses->links() }}</div>
            </div>

            <div class="fin-panel">
                <div class="fin-section-head"><h2 class="fin-section-title">Reconciliacao bancaria</h2><span class="fin-pill">{{ $unreconciled }} pendentes</span></div>
                <div class="fin-table-wrap"><table class="fin-table"><thead><tr><th>Data</th><th>Conta</th><th>Movimento</th><th>Descricao</th><th>Saldo</th><th>Estado</th><th></th></tr></thead><tbody>
                    @forelse($bankTransactions as $tx)
                        <tr><td>{{ $tx->transaction_date?->format('d/m/Y') }}</td><td>{{ $tx->bankAccount?->name }}</td><td><span class="{{ $tx->type === 'credit' ? 'fin-good' : 'fin-bad' }}">{{ $tx->type === 'credit' ? '+' : '-' }}{{ number_format($tx->amount, 2, ',', '.') }} Kz</span></td><td>{{ $tx->description ?: '-' }}<div class="fin-muted">{{ $tx->reference }}</div></td><td>{{ number_format($tx->balance_after, 2, ',', '.') }} Kz</td><td>{{ $tx->reconciled ? 'Reconciliado' : 'Pendente' }}</td><td>@if(!$tx->reconciled)<form method="POST" action="{{ route('admin.finance.bank-transactions.reconcile', $tx) }}">@csrf @method('PATCH')<button class="fin-btn fin-btn-ghost">OK</button></form>@endif</td></tr>
                    @empty
                        <tr><td colspan="7" class="fin-muted">Sem movimentos bancarios no periodo.</td></tr>
                    @endforelse
                </tbody></table></div>
                <div class="fin-pagination">{{ $bankTransactions->links() }}</div>
            </div>
        </div>

        <div class="fin-stack">
            <div class="fin-panel">
                <div class="fin-section-head"><h2 class="fin-section-title">Contas bancarias</h2><span class="fin-pill">{{ $bankAccounts->count() }}</span></div>
                <div class="fin-table-wrap"><table class="fin-table compact"><thead><tr><th>Conta</th><th>Saldo</th></tr></thead><tbody>
                    @forelse($bankAccounts as $account)
                        <tr><td>{{ $account->name }}<div class="fin-muted">{{ $account->bank_name ?: '-' }}</div></td><td>{{ number_format($account->current_balance, 2, ',', '.') }} {{ $account->currency }}</td></tr>
                    @empty
                        <tr><td colspan="2" class="fin-muted">Nenhuma conta bancaria.</td></tr>
                    @endforelse
                </tbody></table></div>
            </div>

            <div class="fin-panel">
                <div class="fin-section-head"><h2 class="fin-section-title">Saldos de conta corrente</h2><span class="fin-pill">Resumo</span></div>
                <div class="fin-table-wrap"><table class="fin-table compact"><thead><tr><th>Entidade</th><th>Saldo</th></tr></thead><tbody>
                    @foreach($customerBalances->take(5) as $row)
                        <tr><td>{{ $row->name }}<div class="fin-muted">Cliente</div></td><td class="fin-good">{{ number_format($row->balance, 2, ',', '.') }} Kz</td></tr>
                    @endforeach
                    @foreach($supplierBalances->take(5) as $row)
                        <tr><td>{{ $row->name }}<div class="fin-muted">Fornecedor</div></td><td class="{{ $row->balance < 0 ? 'fin-bad' : 'fin-warn' }}">{{ number_format($row->balance, 2, ',', '.') }} Kz</td></tr>
                    @endforeach
                    @if($customerBalances->isEmpty() && $supplierBalances->isEmpty())
                        <tr><td colspan="2" class="fin-muted">Sem saldos em aberto.</td></tr>
                    @endif
                </tbody></table></div>
            </div>
        </div>
    </div>
</div>
@endsection