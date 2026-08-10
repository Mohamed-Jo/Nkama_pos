@extends('layouts.admin')

@section('page-title', 'Gestao Comercial')

@section('content')
<div class="commercial-page">
    <div class="page-header">
        <div>
            <h1>Gestao Comercial</h1>
            <p>Precos especiais, tabelas de cliente e promocoes ativas.</p>
        </div>
        <a class="btn-secondary" href="{{ route('admin.sales.create') }}">Nova venda</a>
    </div>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-error">{{ session('error') }}</div>@endif
    @if($errors->any())<div class="alert alert-error">{{ $errors->first() }}</div>@endif

    <div class="grid-two">
        <section class="panel">
            <h2>Preco especial / tabela</h2>
            <form method="POST" action="{{ route('admin.commercial.prices.store') }}" class="stack-form">
                @csrf
                <input name="name" placeholder="Nome da regra" required>
                <div class="row-2">
                    <select name="customer_id">
                        <option value="">Qualquer cliente da tabela</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                        @endforeach
                    </select>
                    <input name="price_table" placeholder="Tabela, ex: VIP">
                </div>
                <select name="product_id" required>
                    <option value="">Produto</option>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}">{{ $product->name }} - AOA {{ number_format((float) $product->selling_price, 2, ',', '.') }}</option>
                    @endforeach
                </select>
                <div class="row-3">
                    <input type="number" step="0.01" min="0" name="unit_price" placeholder="Preco" required>
                    <input type="date" name="starts_at" title="Inicio">
                    <input type="date" name="ends_at" title="Fim">
                </div>
                <label class="checkline"><input type="checkbox" name="active" value="1" checked> Ativo</label>
                <button type="submit">Guardar preco</button>
            </form>
        </section>

        <section class="panel">
            <h2>Promocao</h2>
            <form method="POST" action="{{ route('admin.commercial.promotions.store') }}" class="stack-form">
                @csrf
                <input name="name" placeholder="Nome da promocao" required>
                <div class="row-2">
                    <select name="product_id"><option value="">Produto especifico</option>@foreach($products as $product)<option value="{{ $product->id }}">{{ $product->name }}</option>@endforeach</select>
                    <select name="category_id"><option value="">Categoria</option>@foreach($categories as $category)<option value="{{ $category->id }}">{{ $category->name }}</option>@endforeach</select>
                </div>
                <div class="row-2">
                    <input type="number" step="0.01" min="0" max="100" name="discount_percent" placeholder="Desconto %">
                    <input type="number" step="0.01" min="0" name="fixed_price" placeholder="Preco fixo">
                </div>
                <div class="row-2">
                    <input type="date" name="starts_at" title="Inicio">
                    <input type="date" name="ends_at" title="Fim">
                </div>
                <label class="checkline"><input type="checkbox" name="active" value="1" checked> Ativa</label>
                <button type="submit">Guardar promocao</button>
            </form>
        </section>
    </div>

    <div class="grid-two">
        <section class="panel table-panel">
            <h2>Precos comerciais</h2>
            <table><thead><tr><th>Regra</th><th>Cliente/Tabela</th><th>Produto</th><th>Preco</th><th></th></tr></thead><tbody>
                @forelse($priceRules as $rule)
                    <tr><td>{{ $rule->name }}<small>{{ $rule->active ? 'Ativa' : 'Inativa' }}</small></td><td>{{ $rule->customer->name ?? ($rule->price_table ?: 'Geral') }}</td><td>{{ $rule->product->name ?? '-' }}</td><td>AOA {{ number_format((float) $rule->unit_price, 2, ',', '.') }}</td><td><form method="POST" action="{{ route('admin.commercial.prices.destroy', $rule) }}">@csrf @method('DELETE')<button class="btn-danger">Remover</button></form></td></tr>
                @empty
                    <tr><td colspan="5" class="empty">Sem precos especiais.</td></tr>
                @endforelse
            </tbody></table>
            <div class="pagination-wrap">{{ $priceRules->links() }}</div>
        </section>

        <section class="panel table-panel">
            <h2>Promocoes</h2>
            <table><thead><tr><th>Promocao</th><th>Alvo</th><th>Beneficio</th><th>Periodo</th><th></th></tr></thead><tbody>
                @forelse($promotions as $promotion)
                    <tr><td>{{ $promotion->name }}<small>{{ $promotion->active ? 'Ativa' : 'Inativa' }}</small></td><td>{{ $promotion->product->name ?? ($promotion->category->name ?? '-') }}</td><td>@if($promotion->fixed_price !== null) AOA {{ number_format((float) $promotion->fixed_price, 2, ',', '.') }} @else {{ number_format((float) $promotion->discount_percent, 2, ',', '.') }}% @endif</td><td>{{ $promotion->starts_at?->format('d/m/Y') ?: '-' }} a {{ $promotion->ends_at?->format('d/m/Y') ?: '-' }}</td><td><form method="POST" action="{{ route('admin.commercial.promotions.destroy', $promotion) }}">@csrf @method('DELETE')<button class="btn-danger">Remover</button></form></td></tr>
                @empty
                    <tr><td colspan="5" class="empty">Sem promocoes.</td></tr>
                @endforelse
            </tbody></table>
            <div class="pagination-wrap">{{ $promotions->links() }}</div>
        </section>
    </div>
</div>

<style>
    .commercial-page { max-width: 1400px; margin: 0 auto; color: #cbd5e1; }
    .page-header, .row-2, .row-3 { display:flex; align-items:center; gap:10px; }
    .page-header { justify-content:space-between; margin-bottom:18px; }
    .page-header h1 { color:#fff; margin:0; font-size:2rem; }
    .page-header p, small { color:#94a3b8; }
    .grid-two { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:16px; margin-bottom:16px; }
    .panel { background:#0f172a; border:1px solid #1e293b; border-radius:8px; padding:16px; }
    .panel h2 { color:#fff; font-size:1rem; margin:0 0 12px; }
    .stack-form { display:grid; gap:10px; }
    input, select { background:#020617; border:1px solid #334155; border-radius:8px; color:#e2e8f0; padding:10px; width:100%; }
    .row-2 > *, .row-3 > * { flex:1; }
    .btn-secondary, .stack-form button, .btn-danger { border:0; border-radius:8px; cursor:pointer; font-weight:800; padding:10px 14px; text-decoration:none; }
    .btn-secondary { background:#1e293b; color:#e2e8f0; }
    .stack-form button { background:#ea580c; color:#fff; }
    .btn-danger { background:#7f1d1d; color:#fecaca; padding:7px 10px; }
    .checkline { align-items:center; display:flex; gap:8px; }
    .checkline input { width:auto; }
    .alert { margin-bottom:14px; padding:12px 14px; border-radius:8px; }
    .alert-success { background:#052e1b; color:#86efac; border:1px solid #166534; }
    .alert-error { background:#450a0a; color:#fecaca; border:1px solid #991b1b; }
    .table-panel { overflow:hidden; padding:0; }
    .table-panel h2 { padding:16px 16px 0; }
    table { border-collapse:collapse; width:100%; }
    th { color:#94a3b8; font-size:.72rem; padding:12px; text-align:left; text-transform:uppercase; border-bottom:1px solid #1e293b; }
    td { border-bottom:1px solid #1e293b; padding:12px; vertical-align:top; }
    td small { display:block; margin-top:3px; }
    .empty { color:#94a3b8; padding:28px; text-align:center; }
    .pagination-wrap { padding:12px; }
    @media(max-width:980px){ .grid-two { grid-template-columns:1fr; } .page-header, .row-2, .row-3 { align-items:stretch; flex-direction:column; } }
</style>
@endsection