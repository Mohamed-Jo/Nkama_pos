@extends('layouts.admin')

@section('page-title', 'Catálogo de Produtos')

@section('content')
<div class="max-w-7xl mx-auto">
    
    <div class="header-container">
        <div>
            <h1>Catálogo</h1>
            <p>Gestão de inventário e ativos da loja.</p>
        </div>
        <a href="{{ route('admin.products.create') }}" class="btn-primary">
            <span>+</span> Novo Produto
        </a>
    </div>

    <div class="table-card">
        <div class="search-box">
            <form method="GET">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Pesquisar...">
            </form>
        </div>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Produto</th>
                        <th>Categoria</th>
                        <th>Preço</th>
                        <th>IVA</th>
                        <th>Stock</th>
                        <th class="text-right">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                        <tr>
                            <td>
                                <div class="prod-name">{{ $product->name }}</div>
                                <div class="prod-sku">{{ $product->sku ?? 'SEM-SKU' }}</div>
                            </td>
                            <td>{{ $product->category->name ?? '—' }}</td>
                            <td class="price">AOA {{ number_format($product->selling_price, 2, ',', '.') }}</td>
                            <td>{{ number_format($product->tax_rate ?? 0, 2, ',', '.') }}%</td>
                            <td>
                                <div class="stock-container">
                                    <div class="stock-bar">
                                        <div class="fill {{ $product->stock_quantity > 5 ? 'bg-green' : 'bg-amber' }}" 
                                             style="width: {{ min(($product->stock_quantity / 50) * 100, 100) }}%"></div>
                                    </div>
                                    <span>{{ $product->stock_quantity }} un</span>
                                </div>
                            </td>
                            <td class="actions">
                                <a href="{{ route('admin.products.edit', $product) }}" class="btn-icon" title="Editar">✎</a>
                                <form action="{{ route('admin.products.destroy', $product) }}" method="POST" onsubmit="return confirm('Eliminar?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-icon btn-del" title="Eliminar">✕</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" style="text-align:center; padding: 40px;">Nenhum produto.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
    /* Reset e Estrutura */
    .header-container { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
    h1 { font-size: 2rem; color: #fff; margin: 0; }
    
    .table-card { background: #0f172a; border: 1px solid #1e293b; border-radius: 20px; overflow: hidden; }
    .search-box { padding: 20px; border-bottom: 1px solid #1e293b; }
    .search-box input { width: 300px; padding: 10px; border-radius: 8px; border: 1px solid #334155; background: #020617; color: #fff; }

    /* Tabela */
    table { width: 100%; border-collapse: collapse; color: #cbd5e1; }
    th { text-align: left; padding: 15px 20px; font-size: 0.75rem; text-transform: uppercase; color: #64748b; }
    td { padding: 20px; border-bottom: 1px solid #1e293b; }

    /* Estilos dos Dados */
    .prod-name { font-weight: 600; color: #fff; }
    .prod-sku { font-size: 0.75rem; color: #64748b; font-family: monospace; }
    .price { color: #f97316; font-weight: bold; }
    
    /* Barra de Stock */
    .stock-container { display: flex; align-items: center; gap: 10px; }
    .stock-bar { width: 80px; height: 6px; background: #1e293b; border-radius: 3px; overflow: hidden; }
    .fill { height: 100%; }
    .bg-green { background: #10b981; }
    .bg-amber { background: #f59e0b; }

    /* Botões */
    .btn-primary { background: #ea580c; color: #fff; padding: 12px 24px; border-radius: 10px; text-decoration: none; font-weight: bold; }
    .actions { display: flex; justify-content: flex-end; gap: 8px; }
    .btn-icon { padding: 8px 12px; background: #1e293b; border-radius: 6px; color: #94a3b8; cursor: pointer; border: none; font-size: 1rem; }
    .btn-icon:hover { background: #334155; color: #fff; }
    .btn-del:hover { background: #7f1d1d; color: #f87171; }
</style>
@endsection
