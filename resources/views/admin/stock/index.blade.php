@extends('layouts.admin')

@section('page-title', 'Stock')

@section('content')
@php
    $warehouses = $warehouses ?? collect();
    $warehouseDefaults = $warehouseDefaults ?? [];
    $operations = $operations ?? [];
    $selectedWarehouseId = $selectedWarehouseId ?? ($warehouseDefaults['adjustments'] ?? null);
@endphp

<div class="stock-page">
    <div class="stock-header">
        <div>
            <h1>Stock</h1>
            <p>Controlo de inventario, alertas, ajustes e rotacao.</p>
        </div>
        <div class="stock-actions">
            @if(\App\Services\ModuleSettings::enabled('stock_warehouses'))
                <a href="{{ route('admin.warehouses.index') }}" class="btn-secondary">Armazens</a>
            @endif
            <a href="{{ route('admin.stock.movements') }}" class="btn-secondary">Movimentos</a>
            <a href="{{ route('admin.stock.inventory') }}" class="btn-primary">Inventario fisico</a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-error">{{ session('error') }}</div>
    @endif

    <div class="metric-grid">
        <div class="metric"><span>Produtos</span><strong>{{ number_format($totals['items'], 0, ',', '.') }}</strong></div>
        <div class="metric metric-warn"><span>Stock baixo</span><strong>{{ number_format($totals['low'], 0, ',', '.') }}</strong></div>
        <div class="metric metric-danger"><span>Rutura</span><strong>{{ number_format($totals['out'], 0, ',', '.') }}</strong></div>
        <div class="metric"><span>Valor em stock</span><strong>AOA {{ number_format($totals['stock_value'], 2, ',', '.') }}</strong></div>
        <div class="metric"><span>Custo estimado</span><strong>AOA {{ number_format($totals['cost_value'], 2, ',', '.') }}</strong></div>
        <div class="metric"><span>Entradas hoje</span><strong>{{ number_format($totals['in_today'], 0, ',', '.') }}</strong></div>
        <div class="metric"><span>Saidas hoje</span><strong>{{ number_format($totals['out_today'], 0, ',', '.') }}</strong></div>
    </div>

    <form method="GET" class="filter-bar">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Pesquisar produto ou codigo">
        <select name="category_id">
            <option value="">Todas categorias</option>
            @foreach($categories as $category)
                <option value="{{ $category->id }}" @selected(request('category_id') == $category->id)>{{ $category->name }}</option>
            @endforeach
        </select>
        <select name="status">
            <option value="">Todos estados</option>
            <option value="out" @selected(request('status') === 'out')>Rutura</option>
            <option value="low" @selected(request('status') === 'low')>Stock baixo</option>
            <option value="ok" @selected(request('status') === 'ok')>OK</option>
            <option value="untracked" @selected(request('status') === 'untracked')>Sem controlo</option>
        </select>
        <select name="location">
            <option value="">Todas localizacoes</option>
            @foreach($locations as $location)
                <option value="{{ $location }}" @selected(request('location') === $location)>{{ $location }}</option>
            @endforeach
        </select>
        <button type="submit">Filtrar</button>
    </form>

    <div class="stock-grid">
        <div class="stock-main">
            <table class="stock-table">
                <thead>
                    <tr>
                        <th>Produto</th>
                        <th>Categoria</th>
                        <th>Stock</th>
                        <th>Minimo</th>
                        <th>Custo</th>
                        <th>Margem</th>
                        <th>Ajuste rapido</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                        @php
                            $cost = (float) ($product->purchase_price ?? 0);
                            $price = (float) $product->selling_price;
                            $margin = $price > 0 ? (($price - $cost) / $price) * 100 : 0;
                        @endphp
                        <tr>
                            <td>
                                <a class="product-link" href="{{ route('admin.products.show', $product) }}">{{ $product->name }}</a>
                                <span class="muted">{{ $product->barcode ?? 'SEM-CODIGO' }} @if($product->stock_location) · {{ $product->stock_location }} @endif</span>
                            </td>
                            <td>{{ $product->category->name ?? 'Sem categoria' }}</td>
                            <td>
                                <span class="stock-badge {{ $product->stockStatusClass() }}">{{ $product->stockStatusLabel() }}</span>
                                <strong>{{ number_format((float) ($product->operation_stock_quantity ?? $product->stock_quantity), 0, ',', '.') }} {{ $product->unit ?? 'un' }}</strong>
                                @if(\App\Services\ModuleSettings::enabled('stock_warehouses'))
                                    <span class="muted">Total: {{ number_format((float) $product->stock_quantity, 0, ',', '.') }} {{ $product->unit ?? 'un' }}</span>
                                @endif
                            </td>
                            <td>{{ number_format((float) $product->minimum_stock, 0, ',', '.') }}</td>
                            <td>AOA {{ number_format($cost, 2, ',', '.') }}</td>
                            <td>{{ number_format($margin, 1, ',', '.') }}%</td>
                            <td>
                                <form method="POST" action="{{ route('admin.stock.adjust') }}" class="adjust-form">
                                    @csrf
                                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                                    @if(\App\Services\ModuleSettings::enabled('stock_warehouses'))
                                        <select name="warehouse_id" title="Armazem">
                                            @foreach($warehouses as $warehouse)
                                                <option value="{{ $warehouse->id }}" @selected((int) $selectedWarehouseId === (int) $warehouse->id)>{{ $warehouse->name }}</option>
                                            @endforeach
                                        </select>
                                    @endif
                                    <select name="mode" title="Modo">
                                        <option value="in">Entrada</option>
                                        <option value="out">Saida</option>
                                        <option value="set">Definir</option>
                                    </select>
                                    <input type="number" name="quantity" min="0" placeholder="Qtd." required>
                                    <input type="text" name="reason" placeholder="Motivo" required>
                                    <button type="submit">Gravar</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="empty">Nenhum produto encontrado.</td></tr>
                    @endforelse
                </tbody>
            </table>
            <div class="pagination-wrap">{{ $products->links() }}</div>
        </div>

        <aside class="stock-side">
            <section>
                <h2>Mais vendidos 30 dias</h2>
                @forelse($topSellers as $item)
                    <div class="side-row">
                        <span>{{ $item->product->name ?? 'Produto removido' }}</span>
                        <strong>{{ number_format((float) $item->sold_qty, 0, ',', '.') }}</strong>
                    </div>
                @empty
                    <p class="muted">Sem vendas recentes.</p>
                @endforelse
            </section>

            <section>
                <h2>Produtos parados</h2>
                @forelse($dormantProducts as $product)
                    <div class="side-row">
                        <span>{{ $product->name }}</span>
                                <strong>{{ number_format((float) ($product->operation_stock_quantity ?? $product->stock_quantity), 0, ',', '.') }} {{ $product->unit ?? 'un' }}</strong>
                                @if(\App\Services\ModuleSettings::enabled('stock_warehouses'))
                                    <span class="muted">Total: {{ number_format((float) $product->stock_quantity, 0, ',', '.') }} {{ $product->unit ?? 'un' }}</span>
                                @endif
                    </div>
                @empty
                    <p class="muted">Sem produtos parados com stock.</p>
                @endforelse
            </section>

            <section>
                <h2>Ultimos movimentos</h2>
                @forelse($recentMovements as $movement)
                    <div class="movement-row">
                        <span>{{ $movement->product->name ?? 'Produto removido' }}</span>
                        <strong class="{{ $movement->type === 'IN' ? 'positive' : 'negative' }}">{{ $movement->type }} {{ number_format((float) $movement->quantity, 0, ',', '.') }}</strong>
                        <small>{{ $movement->reason ?? $movement->notes ?? 'Movimento' }}</small>
                    </div>
                @empty
                    <p class="muted">Sem movimentos registados.</p>
                @endforelse
            </section>
        </aside>
    </div>
</div>

<style>
    .stock-page { max-width: 1500px; margin: 0 auto; color: #cbd5e1; }
    .stock-header, .stock-actions, .filter-bar, .adjust-form { display: flex; align-items: center; gap: 12px; }
    .stock-header { justify-content: space-between; margin-bottom: 22px; }
    .stock-header h1 { color: #fff; font-size: 2rem; margin: 0; }
    .stock-header p, .muted { color: #94a3b8; font-size: .82rem; }
    .btn-primary, .btn-secondary, .filter-bar button, .adjust-form button { border: 0; border-radius: 8px; padding: 10px 14px; font-weight: 700; text-decoration: none; cursor: pointer; }
    .btn-primary, .filter-bar button, .adjust-form button { background: #ea580c; color: #fff; }
    .btn-secondary { background: #1e293b; color: #e2e8f0; }
    .alert { margin-bottom: 16px; padding: 12px 14px; border-radius: 8px; }
    .alert-success { background: #052e1b; color: #86efac; border: 1px solid #166534; }
    .alert-error { background: #450a0a; color: #fecaca; border: 1px solid #991b1b; }
    .metric-grid { display: grid; grid-template-columns: repeat(7, minmax(130px, 1fr)); gap: 10px; margin-bottom: 16px; }
    .metric { background: #0f172a; border: 1px solid #1e293b; border-radius: 8px; padding: 14px; }
    .metric span { display: block; color: #94a3b8; font-size: .72rem; text-transform: uppercase; }
    .metric strong { display: block; margin-top: 6px; color: #fff; font-size: 1.05rem; }
    .metric-warn strong { color: #fbbf24; }
    .metric-danger strong { color: #f87171; }
    .filter-bar { background: #0f172a; border: 1px solid #1e293b; border-radius: 8px; padding: 12px; margin-bottom: 16px; flex-wrap: wrap; }
    .filter-bar input, .filter-bar select, .adjust-form input, .adjust-form select { background: #020617; border: 1px solid #334155; border-radius: 8px; color: #e2e8f0; padding: 9px 10px; }
    .filter-bar input { min-width: 240px; }
    .stock-grid { display: grid; grid-template-columns: minmax(0, 1fr) 330px; gap: 16px; align-items: start; }
    .stock-main, .stock-side section { background: #0f172a; border: 1px solid #1e293b; border-radius: 8px; overflow: hidden; }
    .stock-table { width: 100%; border-collapse: collapse; }
    .stock-table th { color: #94a3b8; font-size: .72rem; text-transform: uppercase; text-align: left; padding: 12px; border-bottom: 1px solid #1e293b; }
    .stock-table td { padding: 12px; border-bottom: 1px solid #1e293b; vertical-align: middle; }
    .product-link { color: #fff; font-weight: 700; text-decoration: none; display: block; }
    .stock-badge { display: inline-flex; min-width: 86px; justify-content: center; margin-right: 8px; padding: 4px 8px; border-radius: 999px; font-size: .72rem; font-weight: 800; }
    .stock-ok { background: #064e3b; color: #a7f3d0; }
    .stock-low { background: #78350f; color: #fde68a; }
    .stock-out { background: #7f1d1d; color: #fecaca; }
    .stock-muted { background: #334155; color: #cbd5e1; }
    .adjust-form { flex-wrap: wrap; }
    .adjust-form input[name="quantity"] { width: 78px; }
    .adjust-form input[name="reason"] { width: 130px; }
    .stock-side { display: grid; gap: 16px; }
    .stock-side section { padding: 14px; }
    .stock-side h2 { color: #fff; margin: 0 0 12px; font-size: .95rem; }
    .side-row, .movement-row { display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: 8px; padding: 9px 0; border-top: 1px solid #1e293b; }
    .movement-row small { grid-column: 1 / -1; color: #94a3b8; }
    .positive { color: #86efac; }
    .negative { color: #fca5a5; }
    .empty { text-align: center; color: #94a3b8; padding: 36px; }
    .pagination-wrap { padding: 12px; }
    @media (max-width: 1100px) {
        .metric-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .stock-grid { grid-template-columns: 1fr; }
        .stock-header { align-items: flex-start; flex-direction: column; }
    }
</style>
@endsection
