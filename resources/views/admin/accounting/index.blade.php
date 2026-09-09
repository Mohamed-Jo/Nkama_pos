@extends('layouts.admin')

@section('page-title', 'Contabilidade')

@section('content')
<div class="acct-page">
    <div class="acct-head">
        <div>
            <h1>Contabilidade</h1>
            <p>Plano de contas, lancamentos manuais, diario e balancete.</p>
        </div>
        <form method="GET" action="{{ route('admin.accounting.index') }}" class="acct-filter">
            <input type="date" name="from" value="{{ $from }}">
            <input type="date" name="to" value="{{ $to }}">
            <button type="submit">Filtrar</button>
        </form>
    </div>

    @if(session('success'))<div class="acct-alert success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="acct-alert error">{{ $errors->first() }}</div>@endif
    <section class="acct-panel table-panel acct-audit">
        <div class="panel-title"><h2>Documentos sem lancamento</h2></div>
        <table>
            <thead><tr><th>Origem</th><th>Data</th><th>Documento</th><th>Valor</th></tr></thead>
            <tbody>
                @forelse($unpostedDocuments as $document)
                    <tr>
                        <td>{{ $document->label }}</td>
                        <td>{{ $document->date?->format('d/m/Y') }}</td>
                        <td>{{ $document->document }}</td>
                        <td>{{ number_format((float) $document->amount, 2, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="empty">Todos os documentos do periodo tem lancamento contabilistico.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>

    @if($canManageAccounting)
    <div class="acct-grid">
        <section class="acct-panel">
            <h2>Novo lancamento</h2>
            <form method="POST" action="{{ route('admin.accounting.entries.store') }}" class="acct-form">
                @csrf
                <div class="form-row three">
                    <label>Data<input type="date" name="entry_date" value="{{ old('entry_date', now()->toDateString()) }}" required></label>
                    <label>Documento<input name="document_number" value="{{ old('document_number') }}" placeholder="FT/FR/REC..."></label>
                    <label>Descricao<input name="description" value="{{ old('description') }}" required placeholder="Ex: Venda diaria"></label>
                </div>

                <div class="entry-lines">
                    @for($i = 0; $i < 4; $i++)
                        <div class="entry-line">
                            <select name="lines[{{ $i }}][accounting_account_id]">
                                <option value="">Conta</option>
                                @foreach($activeAccounts as $account)
                                    <option value="{{ $account->id }}" @selected((int) old("lines.$i.accounting_account_id") === (int) $account->id)>{{ $account->code }} - {{ $account->name }}</option>
                                @endforeach
                            </select>
                            <input type="number" step="0.01" min="0" name="lines[{{ $i }}][debit]" value="{{ old("lines.$i.debit") }}" placeholder="Debito">
                            <input type="number" step="0.01" min="0" name="lines[{{ $i }}][credit]" value="{{ old("lines.$i.credit") }}" placeholder="Credito">
                            <input name="lines[{{ $i }}][memo]" value="{{ old("lines.$i.memo") }}" placeholder="Memo">
                        </div>
                    @endfor
                </div>

                <button class="acct-primary" type="submit">Registar Lancamento</button>
            </form>
        </section>

        <section class="acct-panel">
            <h2>Nova conta</h2>
            <form method="POST" action="{{ route('admin.accounting.accounts.store') }}" class="acct-form compact">
                @csrf
                <label>Codigo<input name="code" value="{{ old('code') }}" required placeholder="Ex: 623"></label>
                <label>Nome<input name="name" value="{{ old('name') }}" required placeholder="Ex: Servicos externos"></label>
                <label>Classe
                    <select name="type" required>
                        @foreach(['asset' => 'Ativo', 'liability' => 'Passivo', 'equity' => 'Capital', 'income' => 'Rendimento', 'expense' => 'Gasto'] as $type => $label)
                            <option value="{{ $type }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label>Conta mae
                    <select name="parent_id">
                        <option value="">Sem conta mae</option>
                        @foreach($accounts as $account)
                            <option value="{{ $account->id }}">{{ $account->code }} - {{ $account->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="acct-check"><input type="checkbox" name="active" value="1" checked> Conta ativa</label>
                <button class="acct-secondary" type="submit">Criar Conta</button>
            </form>
        </section>
    </div>
    @endif

    <div class="acct-grid bottom">
        <section class="acct-panel table-panel">
            <div class="panel-title"><h2>Diario contabilistico</h2></div>
            <table>
                <thead><tr><th>Data</th><th>Origem</th><th>Documento</th><th>Descricao</th><th>Movimento</th><th>Operador</th></tr></thead>
                <tbody>
                    @forelse($entries as $entry)
                        <tr>
                            <td>{{ $entry->entry_date?->format('d/m/Y') }}</td>
                            <td>{{ $sourceLabels[$entry->source_type] ?? 'Manual' }}</td>
                            <td>{{ $entry->document_number ?: '-' }}</td>
                            <td>{{ $entry->description }}</td>
                            <td>
                                @foreach($entry->lines as $line)
                                    <div class="line-mini">
                                        <span>{{ $line->account?->code }} - {{ $line->account?->name }}</span>
                                        <strong>{{ number_format((float) $line->debit, 2, ',', '.') }} / {{ number_format((float) $line->credit, 2, ',', '.') }}</strong>
                                    </div>
                                @endforeach
                            </td>
                            <td>{{ $entry->poster?->name ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="empty">Sem lancamentos no periodo.</td></tr>
                    @endforelse
                </tbody>
            </table>
            <div class="pager">{{ $entries->links() }}</div>
        </section>

        <section class="acct-panel table-panel">
            <div class="panel-title"><h2>Balancete</h2></div>
            <table>
                <thead><tr><th>Conta</th><th>Debito</th><th>Credito</th><th>Saldo</th></tr></thead>
                <tbody>
                    @foreach($trialBalance as $row)
                        <tr>
                            <td>{{ $row->code }} - {{ $row->name }}</td>
                            <td>{{ number_format((float) $row->debit, 2, ',', '.') }}</td>
                            <td>{{ number_format((float) $row->credit, 2, ',', '.') }}</td>
                            <td class="{{ $row->balance >= 0 ? 'pos' : 'neg' }}">{{ number_format((float) $row->balance, 2, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot><tr><th>Total</th><th>{{ number_format($totalDebit, 2, ',', '.') }}</th><th>{{ number_format($totalCredit, 2, ',', '.') }}</th><th>{{ number_format($totalDebit - $totalCredit, 2, ',', '.') }}</th></tr></tfoot>
            </table>
        </section>
    </div>
</div>

<style>
    .acct-page { color: var(--text); margin: 0 auto; max-width: 1380px; padding: 20px; }
    .acct-head { align-items: end; display: flex; gap: 14px; justify-content: space-between; margin-bottom: 16px; }
    .acct-head h1 { font-size: 28px; font-weight: 800; margin: 0; }
    .acct-head p { color: var(--muted); font-size: 13px; margin: 6px 0 0; }
    .acct-filter { align-items: end; display: grid; gap: 8px; grid-template-columns: 145px 145px auto; }
    .acct-grid { display: grid; gap: 16px; grid-template-columns: minmax(0, 1.5fr) minmax(280px, .8fr); margin-bottom: 16px; }
    .acct-grid.bottom { grid-template-columns: minmax(0, 1.25fr) minmax(360px, .85fr); }
    .acct-audit { margin-bottom: 16px; }
    .acct-panel { background: var(--card); border: 1px solid var(--border); border-radius: 8px; padding: 16px; }
    .acct-panel h2 { font-size: 16px; font-weight: 800; margin: 0 0 12px; }
    .acct-form { display: grid; gap: 12px; }
    .acct-form.compact { gap: 10px; }
    .form-row.three { display: grid; gap: 10px; grid-template-columns: 150px 160px minmax(0, 1fr); }
    label { color: var(--muted); font-size: 11px; font-weight: 800; text-transform: uppercase; }
    input, select { background: var(--input-bg); border: 1px solid var(--border); border-radius: 8px; box-sizing: border-box; color: var(--input-text); font-size: 13px; margin-top: 5px; min-height: 38px; padding: 8px 10px; width: 100%; }
    .entry-lines { display: grid; gap: 8px; }
    .entry-line { display: grid; gap: 8px; grid-template-columns: minmax(220px, 1fr) 120px 120px minmax(150px, .7fr); }
    .acct-check { align-items: center; display: flex; gap: 8px; text-transform: none; }
    .acct-check input { width: auto; }
    .acct-primary, .acct-secondary, .acct-filter button { border: 0; border-radius: 8px; cursor: pointer; font-size: 13px; font-weight: 900; min-height: 38px; padding: 9px 13px; }
    .acct-primary, .acct-filter button { background: var(--primary); color: #111827; }
    .acct-secondary { background: var(--soft-bg); border: 1px solid var(--border); color: var(--text); }
    .acct-alert { border-radius: 8px; font-size: 13px; margin-bottom: 12px; padding: 11px 13px; }
    .acct-alert.success { background: rgba(22, 163, 74, .12); border: 1px solid rgba(22, 163, 74, .35); color: #86efac; }
    .acct-alert.error { background: rgba(220, 38, 38, .12); border: 1px solid rgba(220, 38, 38, .35); color: #fecaca; }
    .table-panel { overflow: hidden; padding: 0; }
    .panel-title { padding: 16px 16px 0; }
    table { border-collapse: collapse; width: 100%; }
    th { background: var(--soft-bg); color: var(--muted); font-size: 11px; padding: 10px; text-align: left; text-transform: uppercase; }
    td { border-top: 1px solid var(--border); color: var(--text); font-size: 13px; padding: 10px; vertical-align: top; }
    tfoot th { border-top: 1px solid var(--border); color: var(--text); }
    .line-mini { align-items: center; display: flex; gap: 10px; justify-content: space-between; margin-bottom: 4px; }
    .line-mini span { color: var(--muted); }
    .line-mini strong { font-family: ui-monospace, SFMono-Regular, Consolas, monospace; white-space: nowrap; }
    .pos { color: #22c55e; font-weight: 800; }
    .neg { color: #f97316; font-weight: 800; }
    .empty { color: var(--muted); padding: 26px; text-align: center; }
    .pager { padding: 12px; }
    @media (max-width: 1100px) { .acct-grid, .acct-grid.bottom, .form-row.three, .entry-line, .acct-filter { grid-template-columns: 1fr; } .acct-head { align-items: stretch; flex-direction: column; } }
</style>
@endsection