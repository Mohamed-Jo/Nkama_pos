@extends('layouts.admin')

@section('page-title', 'Armazens')

@section('content')
@php
    $warehouses = $warehouses ?? collect();
    $warehouseDefaults = $warehouseDefaults ?? [];
    $operations = $operations ?? [];
@endphp

<div class="warehouse-page">
    <div class="page-header">
        <div>
            <h1>Armazens</h1>
            <p>Stock por local e transferencia interna de artigos.</p>
        </div>
        <div class="header-actions">
            <a href="{{ route('admin.stock.index') }}" class="btn-secondary">Painel de stock</a>
            <a href="{{ route('admin.warehouses.transfers') }}" class="btn-secondary">Historico</a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-error">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-error">Verifique os campos informados.</div>
    @endif


    <section class="panel defaults-panel">
        <h2>Armazens padrao por operacao</h2>
        <form method="POST" action="{{ route('admin.warehouses.defaults') }}" class="defaults-grid">
            @csrf
            @method('PUT')
            @foreach($operations as $key => $label)
                <label>
                    <span>{{ $label }}</span>
                    <select name="defaults[{{ $key }}]" required>
                        @foreach($warehouses->where('active', true) as $warehouse)
                            <option value="{{ $warehouse->id }}" @selected(($defaults[$key] ?? null) == $warehouse->id)>{{ $warehouse->name }}</option>
                        @endforeach
                    </select>
                </label>
            @endforeach
            <button type="submit">Guardar padroes</button>
        </form>
    </section>
    <div class="grid-top">
        <section class="panel">
            <h2>Novo armazem/local</h2>
            <form method="POST" action="{{ route('admin.warehouses.store') }}" class="stack-form">
                @csrf
                <input name="name" placeholder="Nome, ex: Bar, Loja, Armazem Principal" required>
                <input name="code" placeholder="Codigo opcional">
                <input name="location" placeholder="Localizacao opcional">
                <button type="submit">Criar armazem</button>
            </form>
        </section>

        <section class="panel">
            <h2>Transferir artigo</h2>
            <form method="POST" action="{{ route('admin.warehouses.transfer') }}" class="stack-form">
                @csrf
                <div class="row-2">
                    <select name="from_warehouse_id" required>
                        <option value="">Origem</option>
                        @foreach($warehouses->where('active', true) as $warehouse)
                            <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                        @endforeach
                    </select>
                    <select name="to_warehouse_id" required>
                        <option value="">Destino</option>
                        @foreach($warehouses->where('active', true) as $warehouse)
                            <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                        @endforeach
                    </select>
                </div>
                <select name="product_id" required>
                    <option value="">Produto</option>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}">{{ $product->name }} (total: {{ (int) $product->stock_quantity }} {{ $product->unit ?? 'un' }})</option>
                    @endforeach
                </select>
                <div class="row-2">
                    <input type="number" name="quantity" min="1" placeholder="Quantidade" required>
                    <input name="notes" placeholder="Observacao">
                </div>
                <button type="submit">Transferir</button>
            </form>
        </section>
    </div>

    <section class="panel">
        <h2>Armazens ativos</h2>
        <div class="warehouse-grid">
            @foreach($warehouses as $warehouse)
                <form method="POST" action="{{ route('admin.warehouses.update', $warehouse) }}" class="warehouse-card">
                    @csrf
                    @method('PUT')
                    <div class="card-head">
                        <strong>{{ $warehouse->name }}</strong>
                        @if($warehouse->is_default)<span>Padrao</span>@endif
                    </div>
                    <input name="name" value="{{ $warehouse->name }}" required>
                    <input name="code" value="{{ $warehouse->code }}" placeholder="Codigo">
                    <input name="location" value="{{ $warehouse->location }}" placeholder="Localizacao">
                    <label class="checkline"><input type="checkbox" name="active" value="1" @checked($warehouse->active)> Ativo</label>
                    <div class="card-foot">
                        <small>{{ number_format((float) ($warehouse->total_quantity ?? 0), 0, ',', '.') }} unidades em {{ $warehouse->product_stocks_count }} produto(s)</small>
                        <button type="submit">Gravar</button>
                    </div>
                </form>
            @endforeach
        </div>
    </section>

    <form method="GET" class="filter-bar">
        <input name="search" value="{{ request('search') }}" placeholder="Pesquisar produto">
        <select name="warehouse_id">
            <option value="">Todos armazens</option>
            @foreach($warehouses as $warehouse)
                <option value="{{ $warehouse->id }}" @selected(request('warehouse_id') == $warehouse->id)>{{ $warehouse->name }}</option>
            @endforeach
        </select>
        <button type="submit">Filtrar saldos</button>
    </form>

    <section class="panel table-panel">
        <h2>Saldos por armazem</h2>
        <table>
            <thead>
                <tr>
                    <th>Armazem</th>
                    <th>Produto</th>
                    <th>Categoria</th>
                    <th>Quantidade</th>
                    <th>Minimo local</th>
                </tr>
            </thead>
            <tbody>
                @forelse($stocks as $stock)
                    <tr>
                        <td>{{ $stock->warehouse->name ?? '-' }}</td>
                        <td>
                            <strong>{{ $stock->product->name ?? 'Produto removido' }}</strong>
                            <span>{{ $stock->product->barcode ?? 'SEM-CODIGO' }}</span>
                        </td>
                        <td>{{ $stock->product->category->name ?? '-' }}</td>
                        <td>{{ number_format((float) $stock->quantity, 0, ',', '.') }} {{ $stock->product->unit ?? 'un' }}</td>
                        <td>{{ number_format((float) $stock->minimum_stock, 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="empty">Sem saldos por armazem.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="pagination-wrap">{{ $stocks->links() }}</div>
    </section>
</div>

<style>
    .warehouse-page { max-width: 1400px; margin: 0 auto; color: #cbd5e1; }
    .page-header, .header-actions, .filter-bar, .row-2, .card-head, .card-foot { display: flex; align-items: center; gap: 10px; }
    .page-header { justify-content: space-between; margin-bottom: 20px; }
    .page-header h1 { color: #fff; margin: 0; font-size: 2rem; }
    .page-header p { color: #94a3b8; }
    .btn-secondary, .stack-form button, .filter-bar button, .warehouse-card button { background: #1e293b; border: 0; border-radius: 8px; color: #e2e8f0; cursor: pointer; font-weight: 800; padding: 10px 14px; text-decoration: none; }
    .stack-form button, .filter-bar button, .warehouse-card button { background: #ea580c; color: #fff; }
    .alert { margin-bottom: 16px; padding: 12px 14px; border-radius: 8px; }
    .alert-success { background: #052e1b; color: #86efac; border: 1px solid #166534; }
    .alert-error { background: #450a0a; color: #fecaca; border: 1px solid #991b1b; }
    .defaults-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); gap: 12px; }
    .defaults-grid label { display: grid; gap: 6px; color: #94a3b8; font-size: .82rem; font-weight: 700; }
    .defaults-grid button { align-self: end; background: #ea580c; border: 0; border-radius: 8px; color: #fff; cursor: pointer; font-weight: 800; padding: 10px 14px; }
    .defaults-panel { margin-bottom: 16px; }    .grid-top { display: grid; grid-template-columns: 420px 1fr; gap: 16px; margin-bottom: 16px; }
    .panel { background: #0f172a; border: 1px solid #1e293b; border-radius: 8px; padding: 16px; }
    .panel h2 { color: #fff; margin: 0 0 14px; font-size: 1rem; }
    .stack-form { display: grid; gap: 10px; }
    input, select { background: #020617; border: 1px solid #334155; border-radius: 8px; color: #e2e8f0; padding: 10px; width: 100%; }
    .row-2 > * { flex: 1; }
    .warehouse-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 12px; }
    .warehouse-card { background: #020617; border: 1px solid #1e293b; border-radius: 8px; display: grid; gap: 10px; padding: 12px; }
    .card-head { justify-content: space-between; }
    .card-head strong { color: #fff; }
    .card-head span { background: #064e3b; color: #a7f3d0; border-radius: 999px; font-size: .72rem; font-weight: 800; padding: 3px 8px; }
    .checkline { color: #cbd5e1; display: flex; gap: 8px; align-items: center; }
    .checkline input { width: 18px; }
    .card-foot { justify-content: space-between; }
    .card-foot small, td span { color: #94a3b8; font-size: .78rem; }
    .filter-bar { background: #0f172a; border: 1px solid #1e293b; border-radius: 8px; padding: 12px; margin: 16px 0; }
    .filter-bar input { max-width: 320px; }
    .table-panel { padding: 0; overflow: hidden; }
    .table-panel h2 { padding: 16px 16px 0; }
    table { width: 100%; border-collapse: collapse; }
    th { color: #94a3b8; font-size: .72rem; text-align: left; text-transform: uppercase; padding: 12px; border-bottom: 1px solid #1e293b; }
    td { padding: 12px; border-bottom: 1px solid #1e293b; }
    td strong { color: #fff; display: block; }
    .empty { text-align: center; color: #94a3b8; padding: 32px; }
    .pagination-wrap { padding: 12px; }
    @media (max-width: 900px) { .grid-top { grid-template-columns: 1fr; } .page-header, .filter-bar { align-items: flex-start; flex-direction: column; } }
</style>
@endsection
