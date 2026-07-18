@extends('layouts.admin')

@section('page-title', 'Catalogo de Produtos')

@section('content')
<div class="products-page">
    <div class="page-header">
        <div>
            <h1>Catalogo</h1>
            <p>Produtos, precos, disponibilidade e estado de stock.</p>
        </div>
        <div class="header-actions">
            <a href="{{ route('admin.stock.index') }}" class="btn-secondary">Painel de stock</a>
            <a href="{{ route('admin.products.create') }}" class="btn-primary">+ Novo Produto</a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form method="GET" class="filter-bar">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Pesquisar nome ou codigo">
        <select name="category_id">
            <option value="">Todas categorias</option>
            @foreach($categories as $category)
                <option value="{{ $category->id }}" @selected(request('category_id') == $category->id)>{{ $category->name }}</option>
            @endforeach
        </select>
        <select name="stock_status">
            <option value="">Todos stocks</option>
            <option value="out" @selected(request('stock_status') === 'out')>Rutura</option>
            <option value="low" @selected(request('stock_status') === 'low')>Stock baixo</option>
            <option value="ok" @selected(request('stock_status') === 'ok')>OK</option>
            <option value="untracked" @selected(request('stock_status') === 'untracked')>Sem controlo</option>
        </select>
        <select name="availability">
            <option value="">Toda disponibilidade</option>
            <option value="supermarket" @selected(request('availability') === 'supermarket')>Supermercado</option>
            <option value="restaurant" @selected(request('availability') === 'restaurant')>Restaurante</option>
            <option value="inactive" @selected(request('availability') === 'inactive')>Inativos</option>
        </select>
        <button type="submit">Filtrar</button>
    </form>

    <div class="table-card">
        <table>
            <thead>
                <tr>
                    <th>Produto</th>
                    <th>Categoria</th>
                    <th>Preco</th>
                    <th>Custo</th>
                    <th>IVA</th>
                    <th>Stock</th>
                    <th>Disponibilidade</th>
                    <th class="text-right">Acoes</th>
                </tr>
            </thead>
            <tbody>
                @forelse($products as $product)
                    <tr>
                        <td>
                            <a class="prod-name" href="{{ route('admin.products.show', $product) }}">{{ $product->name }}</a>
                            <div class="prod-sku">{{ $product->barcode ?? 'SEM-CODIGO' }} @if($product->stock_location) · {{ $product->stock_location }} @endif</div>
                        </td>
                        <td>{{ $product->category->name ?? '-' }}</td>
                        <td class="price">AOA {{ number_format((float) $product->selling_price, 2, ',', '.') }}</td>
                        <td>AOA {{ number_format((float) $product->purchase_price, 2, ',', '.') }}</td>
                        <td>{{ number_format((float) ($product->tax_rate ?? 0), 2, ',', '.') }}%</td>
                        <td>
                            <span class="stock-badge {{ $product->stockStatusClass() }}">{{ $product->stockStatusLabel() }}</span>
                            <strong>{{ number_format((float) $product->stock_quantity, 0, ',', '.') }} {{ $product->unit ?? 'un' }}</strong>
                            <div class="prod-sku">Min. {{ number_format((float) $product->minimum_stock, 0, ',', '.') }}</div>
                        </td>
                        <td>
                            <div class="flags">
                                @if($product->available_supermarket)<span>Supermercado</span>@endif
                                @if($product->available_restaurant)<span>Restaurante</span>@endif
                                @unless($product->status)<span class="danger">Inativo</span>@endunless
                            </div>
                        </td>
                        <td class="actions">
                            <a href="{{ route('admin.products.show', $product) }}" class="btn-icon" title="Ver">Ver</a>
                            <a href="{{ route('admin.products.edit', $product) }}" class="btn-icon" title="Editar">Editar</a>
                            <form action="{{ route('admin.products.destroy', $product) }}" method="POST" onsubmit="return confirm('Eliminar?');">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn-icon btn-del" title="Eliminar">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="empty">Nenhum produto.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="table-pagination">{{ $products->links() }}</div>
    </div>
</div>

<style>
    .products-page { max-width: 1400px; margin: 0 auto; color: #cbd5e1; }
    .page-header, .header-actions, .filter-bar, .actions, .flags { display: flex; align-items: center; gap: 10px; }
    .page-header { justify-content: space-between; margin-bottom: 22px; }
    .page-header h1 { font-size: 2rem; color: #fff; margin: 0; }
    .page-header p, .prod-sku { color: #94a3b8; font-size: .78rem; }
    .btn-primary, .btn-secondary, .filter-bar button, .btn-icon { border: 0; border-radius: 8px; padding: 10px 14px; text-decoration: none; font-weight: 700; cursor: pointer; }
    .btn-primary, .filter-bar button { background: #ea580c; color: #fff; }
    .btn-secondary, .btn-icon { background: #1e293b; color: #e2e8f0; }
    .alert { margin-bottom: 16px; padding: 12px 14px; border-radius: 8px; }
    .alert-success { background: #052e1b; color: #86efac; border: 1px solid #166534; }
    .filter-bar { background: #0f172a; border: 1px solid #1e293b; border-radius: 8px; padding: 12px; margin-bottom: 16px; flex-wrap: wrap; }
    .filter-bar input, .filter-bar select { background: #020617; border: 1px solid #334155; border-radius: 8px; color: #e2e8f0; padding: 9px 10px; }
    .filter-bar input { min-width: 240px; }
    .table-card { background: #0f172a; border: 1px solid #1e293b; border-radius: 8px; overflow: hidden; }
    table { width: 100%; border-collapse: collapse; }
    th { text-align: left; padding: 13px 14px; font-size: .72rem; text-transform: uppercase; color: #94a3b8; border-bottom: 1px solid #1e293b; }
    td { padding: 14px; border-bottom: 1px solid #1e293b; vertical-align: middle; }
    .prod-name { font-weight: 800; color: #fff; text-decoration: none; }
    .price { color: #f97316; font-weight: bold; }
    .stock-badge { display: inline-flex; min-width: 86px; justify-content: center; margin-right: 8px; padding: 4px 8px; border-radius: 999px; font-size: .72rem; font-weight: 800; }
    .stock-ok { background: #064e3b; color: #a7f3d0; }
    .stock-low { background: #78350f; color: #fde68a; }
    .stock-out { background: #7f1d1d; color: #fecaca; }
    .stock-muted { background: #334155; color: #cbd5e1; }
    .flags { flex-wrap: wrap; }
    .flags span { background: #172554; color: #bfdbfe; padding: 4px 8px; border-radius: 999px; font-size: .72rem; font-weight: 700; }
    .flags .danger { background: #7f1d1d; color: #fecaca; }
    .actions { justify-content: flex-end; flex-wrap: wrap; }
    .btn-del:hover { background: #7f1d1d; color: #fecaca; }
    .empty { text-align: center; color: #94a3b8; padding: 36px; }
    .table-pagination { padding: 12px; }
</style>
@endsection