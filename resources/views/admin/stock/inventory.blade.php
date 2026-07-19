@extends('layouts.admin')

@section('page-title', 'Inventario Fisico')

@section('content')
@php
    $warehouses = $warehouses ?? collect();
    $warehouseDefaults = $warehouseDefaults ?? [];
    $operations = $operations ?? [];
    $selectedWarehouseId = $selectedWarehouseId ?? ($warehouseDefaults['inventory'] ?? null);
@endphp

<div class="stock-page">
    <div class="stock-header">
        <div>
            <h1>Inventario Fisico</h1>
            <p>Conte o stock real, compare com o sistema e aplique apenas as diferencas.</p>
        </div>
        <a href="{{ route('admin.stock.index') }}" class="btn-secondary">Voltar ao stock</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form method="GET" class="filter-bar">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Pesquisar produto ou codigo">
        <select name="category_id">
            <option value="">Todas categorias</option>
            @foreach($categories as $category)
                <option value="{{ $category->id }}" @selected(request('category_id') == $category->id)>{{ $category->name }}</option>
            @endforeach
        </select>
        @if(\App\Services\ModuleSettings::enabled('stock_warehouses'))
            <select name="warehouse_id">
                @foreach($warehouses as $warehouse)
                    <option value="{{ $warehouse->id }}" @selected((int) $selectedWarehouseId === (int) $warehouse->id)>{{ $warehouse->name }}</option>
                @endforeach
            </select>
        @endif
        <button type="submit">Filtrar</button>
    </form>

    <form method="POST" action="{{ route('admin.stock.inventory.apply') }}" class="table-card">
        @csrf
        <table>
            <thead>
                <tr>
                    <th>Produto</th>
                    <th>Categoria</th>
                    <th>Sistema</th>
                    <th>Contagem fisica</th>
                    <th>Unidade</th>
                </tr>
            </thead>
            <tbody>
                @forelse($products as $product)
                    <tr>
                        <td>
                            <strong>{{ $product->name }}</strong>
                            <span class="muted">{{ $product->barcode ?? 'SEM-CODIGO' }}</span>
                        </td>
                        <td>{{ $product->category->name ?? 'Sem categoria' }}</td>
                        <td>
                            <strong>{{ number_format((float) ($product->operation_stock_quantity ?? $product->stock_quantity), 0, ',', '.') }}</strong>
                            @if(\App\Services\ModuleSettings::enabled('stock_warehouses'))
                                <span class="muted">Total: {{ number_format((float) $product->stock_quantity, 0, ',', '.') }}</span>
                            @endif
                        </td>
                        <td><input type="number" name="counts[{{ $product->id }}]" min="0" placeholder="{{ (int) ($product->operation_stock_quantity ?? $product->stock_quantity) }}"></td>
                        <td>{{ $product->unit ?? 'un' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="empty">Nenhum produto para contar.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="inventory-footer">
            @if(\App\Services\ModuleSettings::enabled('stock_warehouses'))
                <input type="hidden" name="warehouse_id" value="{{ $selectedWarehouseId }}">
            @endif
            <input type="text" name="notes" placeholder="Observacao geral do inventario">
            <button type="submit">Aplicar diferencas</button>
        </div>
        <div class="pagination-wrap">{{ $products->links() }}</div>
    </form>
</div>

<style>
    .stock-page { max-width: 1200px; margin: 0 auto; color: #cbd5e1; }
    .stock-header, .filter-bar, .inventory-footer { display: flex; align-items: center; gap: 12px; }
    .stock-header { justify-content: space-between; margin-bottom: 20px; }
    .stock-header h1 { color: #fff; margin: 0; font-size: 2rem; }
    .stock-header p, .muted { color: #94a3b8; font-size: .82rem; }
    .muted { display: block; }
    .btn-secondary, .filter-bar button, .inventory-footer button { border: 0; border-radius: 8px; padding: 10px 14px; font-weight: 700; text-decoration: none; cursor: pointer; }
    .btn-secondary { background: #1e293b; color: #e2e8f0; }
    .filter-bar, .table-card { background: #0f172a; border: 1px solid #1e293b; border-radius: 8px; }
    .filter-bar { padding: 12px; margin-bottom: 16px; flex-wrap: wrap; }
    .filter-bar input, .filter-bar select, .inventory-footer input, td input { background: #020617; border: 1px solid #334155; border-radius: 8px; color: #e2e8f0; padding: 9px 10px; }
    .filter-bar button, .inventory-footer button { background: #ea580c; color: #fff; }
    .table-card { overflow: hidden; }
    table { width: 100%; border-collapse: collapse; }
    th { color: #94a3b8; font-size: .72rem; text-transform: uppercase; text-align: left; padding: 12px; border-bottom: 1px solid #1e293b; }
    td { padding: 12px; border-bottom: 1px solid #1e293b; }
    td input { width: 150px; }
    .inventory-footer { justify-content: flex-end; padding: 12px; border-top: 1px solid #1e293b; }
    .inventory-footer input { min-width: 300px; }
    .inventory-footer select { min-width: 220px; }
    .alert { margin-bottom: 16px; padding: 12px 14px; border-radius: 8px; }
    .alert-success { background: #052e1b; color: #86efac; border: 1px solid #166534; }
    .empty { text-align: center; color: #94a3b8; padding: 36px; }
    .pagination-wrap { padding: 12px; }
</style>
@endsection
