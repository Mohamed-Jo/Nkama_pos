@extends('layouts.admin')

@section('page-title', 'Formas de Pagamento')

@section('content')
<div class="pm-page">
    <div class="pm-head">
        <div>
            <h1>Formas de Pagamento</h1>
            <p>Configure os meios usados no POS, vendas, despesas e conta corrente.</p>
        </div>
    </div>

    @if(session('success'))<div class="pm-alert success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="pm-alert error">{{ $errors->first() }}</div>@endif

    <section class="pm-panel">
        <h2>Nova forma</h2>
        <form method="POST" action="{{ route('admin.payment-methods.store') }}" class="pm-form create">
            @csrf
            <label>Codigo<input name="code" value="{{ old('code') }}" required placeholder="ex: tpa_bai"></label>
            <label>Nome<input name="name" value="{{ old('name') }}" required placeholder="ex: TPA BAI"></label>
            <label>Tipo<select name="type" required>@foreach($types as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select></label>
            <label>Conta bancaria<select name="bank_account_id"><option value="">Nenhuma</option>@foreach($bankAccounts as $account)<option value="{{ $account->id }}">{{ $account->name }}</option>@endforeach</select></label>
            <label>Ordem<input type="number" name="sort_order" value="{{ old('sort_order', 100) }}" min="0"></label>
            <div class="pm-checks">
                <label><input type="checkbox" name="active" value="1" checked> Ativa</label>
                <label><input type="checkbox" name="show_in_pos" value="1" checked> POS</label>
                <label><input type="checkbox" name="show_in_sales" value="1" checked> Vendas</label>
                <label><input type="checkbox" name="show_in_expenses" value="1"> Despesas</label>
                <label><input type="checkbox" name="show_in_current_account" value="1"> Conta corrente</label>
                <label><input type="checkbox" name="requires_bank_account" value="1"> Exige banco</label>
            </div>
            <button class="pm-btn primary">Criar</button>
        </form>
    </section>

    <section class="pm-panel table-panel">
        <h2>Metodos cadastrados</h2>
        <div class="pm-table-wrap">
            <table>
                <thead><tr><th>Codigo</th><th>Nome</th><th>Tipo</th><th>Contextos</th><th>Banco</th><th>Estado</th><th></th></tr></thead>
                <tbody>
                @foreach($methods as $method)
                    <tr>
                        <form method="POST" action="{{ route('admin.payment-methods.update', $method) }}">
                            @csrf @method('PUT')
                            <td><input name="code" value="{{ $method->code }}" required></td>
                            <td><input name="name" value="{{ $method->name }}" required></td>
                            <td><select name="type">@foreach($types as $key => $label)<option value="{{ $key }}" @selected($method->type === $key)>{{ $label }}</option>@endforeach</select></td>
                            <td class="context-cell">
                                <label><input type="checkbox" name="show_in_pos" value="1" @checked($method->show_in_pos)> POS</label>
                                <label><input type="checkbox" name="show_in_sales" value="1" @checked($method->show_in_sales)> Vendas</label>
                                <label><input type="checkbox" name="show_in_expenses" value="1" @checked($method->show_in_expenses)> Despesas</label>
                                <label><input type="checkbox" name="show_in_current_account" value="1" @checked($method->show_in_current_account)> Conta corrente</label>
                                <label><input type="checkbox" name="requires_bank_account" value="1" @checked($method->requires_bank_account)> Exige banco</label>
                            </td>
                            <td><select name="bank_account_id"><option value="">-</option>@foreach($bankAccounts as $account)<option value="{{ $account->id }}" @selected($method->bank_account_id === $account->id)>{{ $account->name }}</option>@endforeach</select><input type="hidden" name="sort_order" value="{{ $method->sort_order }}"></td>
                            <td><label class="status-check"><input type="checkbox" name="active" value="1" @checked($method->active)> Ativa</label></td>
                            <td><button class="pm-btn">Guardar</button></td>
                        </form>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </section>
</div>
<style>
.pm-page{color:var(--text);display:grid;gap:14px;max-width:1320px;margin:0 auto;padding:18px}.pm-head h1{font-size:26px;font-weight:900;margin:0}.pm-head p{color:var(--muted);font-size:13px;margin:4px 0 0}.pm-panel{background:var(--card);border:1px solid var(--border);border-radius:8px;padding:14px}.pm-panel h2{font-size:15px;font-weight:900;margin:0 0 12px}.pm-form.create{align-items:end;display:grid;gap:10px;grid-template-columns:150px 1fr 190px 210px 90px auto}.pm-form label,.context-cell label,.status-check{color:var(--muted);font-size:11px;font-weight:800;text-transform:uppercase}.pm-form input,.pm-form select,td input,td select{background:var(--input-bg);border:1px solid var(--border);border-radius:8px;color:var(--input-text);font-size:12px;min-height:34px;padding:7px 9px;width:100%;box-sizing:border-box}.pm-checks{display:flex;flex-wrap:wrap;gap:8px;min-width:260px}.pm-checks label,.context-cell label,.status-check{align-items:center;display:inline-flex;gap:5px;text-transform:none}.pm-checks input,.context-cell input,.status-check input{width:auto;min-height:auto}.pm-btn{background:var(--soft-bg);border:1px solid var(--border);border-radius:8px;color:var(--text);cursor:pointer;font-size:12px;font-weight:900;min-height:34px;padding:7px 10px}.pm-btn.primary{background:var(--primary);border-color:var(--primary);color:#111827}.pm-alert{border-radius:8px;font-size:13px;padding:10px 12px}.pm-alert.success{background:rgba(22,163,74,.12);border:1px solid rgba(22,163,74,.35);color:#86efac}.pm-alert.error{background:rgba(220,38,38,.12);border:1px solid rgba(220,38,38,.35);color:#fecaca}.table-panel{padding:0}.table-panel h2{padding:14px 14px 0}.pm-table-wrap{overflow:auto}table{border-collapse:collapse;min-width:1080px;width:100%}th{background:var(--soft-bg);color:var(--muted);font-size:10px;padding:9px;text-align:left;text-transform:uppercase}td{border-top:1px solid var(--border);padding:8px;vertical-align:top}.context-cell{display:grid;gap:5px;min-width:150px}@media(max-width:1100px){.pm-form.create{grid-template-columns:1fr}.pm-checks{min-width:0}}
</style>
@endsection