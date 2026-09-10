@extends('layouts.admin')

@section('page-title', 'Formas de Pagamento')

@section('content')
<div class="pm-page">
    <div class="pm-head">
        <div>
            <h1>Formas de Pagamento</h1>
            <p>Configure os meios usados no POS, vendas, despesas e conta corrente.</p>
        </div>
        <div class="pm-stats" aria-label="Resumo das formas de pagamento">
            <span><strong>{{ $methods->count() }}</strong> total</span>
            <span><strong>{{ $methods->where('active', true)->count() }}</strong> ativas</span>
            <span><strong>{{ $methods->where('requires_bank_account', true)->count() }}</strong> com banco</span>
        </div>
    </div>

    @if(session('success'))
        <div class="pm-alert success">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="pm-alert error">{{ $errors->first() }}</div>
    @endif

    <section class="pm-panel pm-create-panel">
        <div class="pm-panel-title">
            <h2>Nova forma</h2>
            <span>Use codigos curtos como dinheiro, tpa_bai ou transferencia.</span>
        </div>

        <form method="POST" action="{{ route('admin.payment-methods.store') }}" class="pm-create-form">
            @csrf

            <div class="pm-fields primary-fields">
                <label>Codigo
                    <input name="code" value="{{ old('code') }}" required placeholder="ex: tpa_bai">
                </label>
                <label>Nome
                    <input name="name" value="{{ old('name') }}" required placeholder="ex: TPA BAI">
                </label>
                <label>Tipo
                    <select name="type" required>
                        @foreach($types as $key => $label)
                            <option value="{{ $key }}" @selected(old('type') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label>Conta bancaria
                    <select name="bank_account_id">
                        <option value="">Nenhuma</option>
                        @foreach($bankAccounts as $account)
                            <option value="{{ $account->id }}" @selected((int) old('bank_account_id') === $account->id)>{{ $account->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label>Ordem
                    <input type="number" name="sort_order" value="{{ old('sort_order', 100) }}" min="0">
                </label>
            </div>

            <div class="pm-options-block">
                <div class="pm-option-group">
                    <span>Disponivel em</span>
                    <label><input type="checkbox" name="show_in_pos" value="1" checked> POS</label>
                    <label><input type="checkbox" name="show_in_sales" value="1" checked> Vendas</label>
                    <label><input type="checkbox" name="show_in_expenses" value="1"> Despesas</label>
                    <label><input type="checkbox" name="show_in_current_account" value="1"> Conta corrente</label>
                </div>
                <div class="pm-option-group compact">
                    <span>Estado</span>
                    <label><input type="checkbox" name="active" value="1" checked> Ativa</label>
                    <label><input type="checkbox" name="requires_bank_account" value="1"> Exige banco</label>
                </div>
                <button class="pm-btn primary" type="submit">Criar forma</button>
            </div>
        </form>
    </section>

    <section class="pm-panel pm-list-panel">
        <div class="pm-panel-title">
            <h2>Metodos cadastrados</h2>
            <span>Edite diretamente e guarde cada linha individualmente.</span>
        </div>

        <div class="pm-methods">
            @forelse($methods as $method)
                <form method="POST" action="{{ route('admin.payment-methods.update', $method) }}" class="pm-method-row">
                    @csrf
                    @method('PUT')

                    <div class="pm-method-meta">
                        <span class="pm-code">{{ $method->code }}</span>
                        <strong>{{ $method->name }}</strong>
                        <small>{{ $types[$method->type] ?? $method->type }}</small>
                    </div>

                    <div class="pm-fields row-fields">
                        <label>Codigo
                            <input name="code" value="{{ $method->code }}" required>
                        </label>
                        <label>Nome
                            <input name="name" value="{{ $method->name }}" required>
                        </label>
                        <label>Tipo
                            <select name="type">
                                @foreach($types as $key => $label)
                                    <option value="{{ $key }}" @selected($method->type === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label>Banco
                            <select name="bank_account_id">
                                <option value="">Nenhum</option>
                                @foreach($bankAccounts as $account)
                                    <option value="{{ $account->id }}" @selected($method->bank_account_id === $account->id)>{{ $account->name }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label>Ordem
                            <input type="number" name="sort_order" value="{{ $method->sort_order }}" min="0">
                        </label>
                    </div>

                    <div class="pm-row-options">
                        <div class="pm-row-option-group">
                            <span>Disponivel em</span>
                            <label><input type="checkbox" name="show_in_pos" value="1" @checked($method->show_in_pos)> POS</label>
                            <label><input type="checkbox" name="show_in_sales" value="1" @checked($method->show_in_sales)> Vendas</label>
                            <label><input type="checkbox" name="show_in_expenses" value="1" @checked($method->show_in_expenses)> Despesas</label>
                            <label><input type="checkbox" name="show_in_current_account" value="1" @checked($method->show_in_current_account)> Conta corrente</label>
                        </div>
                        <div class="pm-row-option-group small">
                            <span>Estado</span>
                            <label><input type="checkbox" name="requires_bank_account" value="1" @checked($method->requires_bank_account)> Exige banco</label>
                            <label><input type="checkbox" name="active" value="1" @checked($method->active)> Ativa</label>
                        </div>
                    </div>

                    <div class="pm-row-actions">
                        <span class="pm-state {{ $method->active ? 'on' : 'off' }}">{{ $method->active ? 'Ativa' : 'Inativa' }}</span>
                        <button class="pm-btn" type="submit">Guardar</button>
                    </div>
                </form>
            @empty
                <div class="pm-empty">Nenhuma forma de pagamento cadastrada.</div>
            @endforelse
        </div>
    </section>
</div>

<style>
.pm-page {
    color: var(--text);
    display: grid;
    gap: 14px;
    margin: 0 auto;
    max-width: 1320px;
    padding: 18px;
}

.pm-head {
    align-items: end;
    display: flex;
    gap: 16px;
    justify-content: space-between;
}

.pm-head h1 {
    font-size: 24px;
    font-weight: 900;
    margin: 0;
}

.pm-head p,
.pm-panel-title span,
.pm-method-meta small {
    color: var(--muted);
    font-size: 12px;
    margin: 4px 0 0;
}

.pm-stats {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    justify-content: flex-end;
}

.pm-stats span,
.pm-state,
.pm-code {
    align-items: center;
    background: var(--soft-bg);
    border: 1px solid var(--border);
    border-radius: 8px;
    color: var(--muted);
    display: inline-flex;
    font-size: 11px;
    font-weight: 800;
    min-height: 30px;
    padding: 0 10px;
}

.pm-stats strong,
.pm-method-meta strong {
    color: var(--text);
}

.pm-panel {
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: 8px;
    box-shadow: 0 14px 34px rgba(2, 6, 23, 0.12);
    padding: 14px;
}

.pm-panel-title {
    align-items: baseline;
    display: flex;
    gap: 10px;
    justify-content: space-between;
    margin-bottom: 12px;
}

.pm-panel-title h2 {
    font-size: 15px;
    font-weight: 900;
    margin: 0;
}

.pm-create-form,
.pm-methods {
    display: grid;
    gap: 10px;
}

.pm-fields {
    display: grid;
    gap: 10px;
}

.primary-fields {
    grid-template-columns: 150px minmax(180px, 1fr) 180px minmax(180px, 240px) 88px;
}

.row-fields {
    grid-template-columns: 120px minmax(160px, 1fr) 170px minmax(160px, 220px) 80px;
}

.pm-fields label {
    color: var(--muted);
    display: grid;
    font-size: 10px;
    font-weight: 900;
    gap: 4px;
    text-transform: uppercase;
}

.pm-fields input,
.pm-fields select {
    background: var(--input-bg);
    border: 1px solid var(--border);
    border-radius: 8px;
    box-sizing: border-box;
    color: var(--input-text);
    font-size: 12px;
    min-height: 34px;
    padding: 7px 9px;
    width: 100%;
}

.pm-options-block,
.pm-row-options {
    align-items: center;
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.pm-option-group {
    align-items: center;
    background: var(--soft-bg);
    border: 1px solid var(--border);
    border-radius: 8px;
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    min-height: 38px;
    padding: 6px 10px;
}

.pm-option-group span {
    color: var(--muted);
    font-size: 10px;
    font-weight: 900;
    text-transform: uppercase;
}

.pm-option-group label,
.pm-row-options label {
    align-items: center;
    color: var(--text);
    display: inline-flex;
    font-size: 12px;
    font-weight: 700;
    gap: 6px;
    min-height: 26px;
}

.pm-option-group input,
.pm-row-options input {
    min-height: auto;
    width: auto;
}

.pm-method-row {
    align-items: center;
    border: 1px solid var(--border);
    border-radius: 8px;
    display: grid;
    gap: 12px;
    grid-template-columns: 180px minmax(0, 1fr) minmax(260px, .7fr) auto;
    padding: 12px;
}

.pm-method-row:hover {
    background: var(--soft-bg);
}

.pm-method-meta {
    display: grid;
    gap: 4px;
    min-width: 0;
}

.pm-code {
    color: var(--primary);
    justify-self: start;
    text-transform: lowercase;
}

.pm-row-options {
    gap: 6px 10px;
}

.pm-row-actions {
    align-items: end;
    display: grid;
    gap: 8px;
    justify-items: end;
}

.pm-state.on {
    background: rgba(16, 185, 129, 0.14);
    border-color: rgba(16, 185, 129, 0.28);
    color: #34d399;
}

.pm-state.off {
    background: rgba(239, 68, 68, 0.14);
    border-color: rgba(239, 68, 68, 0.28);
    color: #f87171;
}

.pm-btn {
    background: var(--soft-bg);
    border: 1px solid var(--border);
    border-radius: 8px;
    color: var(--text);
    cursor: pointer;
    font-size: 12px;
    font-weight: 900;
    min-height: 34px;
    padding: 7px 12px;
    white-space: nowrap;
}

.pm-btn.primary {
    background: var(--primary);
    border-color: var(--primary);
    color: #111827;
}

.pm-alert {
    border-radius: 8px;
    font-size: 13px;
    padding: 10px 12px;
}

.pm-alert.success {
    background: rgba(22, 163, 74, .12);
    border: 1px solid rgba(22, 163, 74, .35);
    color: #86efac;
}

.pm-alert.error {
    background: rgba(220, 38, 38, .12);
    border: 1px solid rgba(220, 38, 38, .35);
    color: #fecaca;
}

.pm-empty {
    color: var(--muted);
    padding: 24px;
    text-align: center;
}

:root[data-theme="light"] .pm-panel {
    box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06);
}

:root[data-theme="light"] .pm-state.on {
    background: #ecfdf5;
    border-color: #86efac;
    color: #047857;
}

:root[data-theme="light"] .pm-state.off {
    background: #fff1f2;
    border-color: #fecdd3;
    color: #be123c;
}



/* pm-checkbox-layout */
.pm-row-options {
    align-items: stretch;
    display: grid;
    grid-template-columns: minmax(220px, 1fr) minmax(150px, .55fr);
}

.pm-row-option-group {
    align-content: start;
    background: var(--soft-bg);
    border: 1px solid var(--border);
    border-radius: 8px;
    display: grid;
    gap: 4px 8px;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    min-height: 0;
    padding: 6px 8px;
}

.pm-row-option-group.small {
    grid-template-columns: 1fr;
}

.pm-row-option-group span {
    color: var(--muted);
    font-size: 9px;
    font-weight: 900;
    grid-column: 1 / -1;
    line-height: 1.1;
    text-transform: uppercase;
}

.pm-row-option-group label {
    align-items: center;
    color: var(--text);
    display: inline-flex;
    font-size: 11px;
    font-weight: 700;
    gap: 5px;
    line-height: 1.15;
    min-height: 18px;
    white-space: nowrap;
}

.pm-row-option-group input[type="checkbox"] {
    height: 15px;
    width: 15px;
}
/* pm-compact-density */
.pm-page {
    gap: 10px;
    padding: 12px 18px;
}

.pm-panel {
    padding: 10px 12px;
}

.pm-panel-title {
    margin-bottom: 8px;
}

.pm-create-form,
.pm-methods,
.pm-fields {
    gap: 7px;
}

.pm-fields label {
    font-size: 9px;
    gap: 2px;
}

.pm-fields input,
.pm-fields select {
    font-size: 11px;
    height: 28px;
    min-height: 28px;
    padding: 4px 7px;
}

.pm-options-block,
.pm-row-options {
    gap: 6px;
}

.pm-option-group {
    gap: 6px;
    min-height: 30px;
    padding: 4px 8px;
}

.pm-option-group span {
    font-size: 9px;
}

.pm-option-group label,
.pm-row-options label {
    font-size: 11px;
    gap: 4px;
    min-height: 20px;
}

.pm-method-row {
    gap: 8px;
    padding: 8px 10px;
}

.pm-method-meta {
    gap: 2px;
}

.pm-code,
.pm-state,
.pm-stats span {
    font-size: 10px;
    min-height: 24px;
    padding: 0 8px;
}

.pm-btn {
    font-size: 11px;
    min-height: 28px;
    padding: 4px 9px;
}

.pm-row-actions {
    gap: 6px;
}

.pm-alert {
    padding: 8px 10px;
}

/* pm-row-no-overlap */
.pm-method-row {
    align-items: start;
    grid-template-columns: 170px minmax(0, 1fr) 96px;
    grid-template-areas:
        "meta fields actions"
        "meta options actions";
}

.pm-method-meta {
    grid-area: meta;
}

.pm-method-row > .row-fields {
    grid-area: fields;
}

.pm-method-row > .pm-row-options {
    grid-area: options;
    width: 100%;
}

.pm-method-row > .pm-row-actions {
    grid-area: actions;
}

.pm-row-option-group {
    min-width: 0;
}

.pm-row-option-group label {
    white-space: normal;
}

@media (max-width: 1200px) {
    .pm-method-row {
        grid-template-columns: 1fr;
        grid-template-areas:
            "meta"
            "fields"
            "options"
            "actions";
    }
}

@media (max-width: 760px) {
    .pm-row-options {
        grid-template-columns: 1fr;
    }
}
@media (max-width: 1200px) {
    .pm-method-row {
        grid-template-columns: 1fr;
    }

    .pm-row-actions {
        align-items: center;
        display: flex;
        justify-content: space-between;
    }
}

@media (max-width: 900px) {
    .pm-head,
    .pm-panel-title {
        align-items: flex-start;
        flex-direction: column;
    }

    .pm-stats {
        justify-content: flex-start;
    }

    .primary-fields,
    .row-fields {
        grid-template-columns: 1fr;
    }
}
</style>
@endsection