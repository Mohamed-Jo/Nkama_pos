@extends('layouts.admin')

@section('page-title', 'Movimentos de Stock')

@section('content')
<div class="stock-page">
    <div class="stock-header">
        <div>
            <h1>Movimentos de Stock</h1>
            <p>Historico auditavel de entradas, saidas, vendas, compras, notas de credito e ajustes.</p>
        </div>
        <a href="{{ route('admin.stock.index') }}" class="btn-secondary">Voltar ao stock</a>
    </div>

    <form method="GET" class="filter-bar">
        <select name="product_id">
            <option value="">Todos produtos</option>
            @foreach($products as $product)
                <option value="{{ $product->id }}" @selected(request('product_id') == $product->id)>{{ $product->name }}</option>
            @endforeach
        </select>
        <select name="type">
            <option value="">Entradas e saidas</option>
            <option value="IN" @selected(request('type') === 'IN')>Entradas</option>
            <option value="OUT" @selected(request('type') === 'OUT')>Saidas</option>
        </select>
        <input type="date" name="date_from" value="{{ request('date_from') }}">
        <input type="date" name="date_to" value="{{ request('date_to') }}">
        <button type="submit">Filtrar</button>
    </form>

    <div class="table-card">
        <table>
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Produto</th>
                    <th>Tipo</th>
                    <th>Qtd.</th>
                    <th>Antes</th>
                    <th>Depois</th>
                    <th>Motivo</th>
                    <th>Operador</th>
                </tr>
            </thead>
            <tbody>
                @forelse($movements as $movement)
                    <tr>
                        <td>{{ $movement->created_at?->format('d/m/Y H:i') }}</td>
                        <td>{{ $movement->product->name ?? 'Produto removido' }}</td>
                        <td><span class="{{ $movement->type === 'IN' ? 'positive' : 'negative' }}">{{ $movement->type }}</span></td>
                        <td>{{ number_format((float) $movement->quantity, 0, ',', '.') }}</td>
                        <td>{{ number_format((float) $movement->stock_before, 0, ',', '.') }}</td>
                        <td>{{ number_format((float) $movement->stock_after, 0, ',', '.') }}</td>
                        <td>{{ $movement->reason ?? $movement->notes ?? '-' }}</td>
                        <td>{{ $movement->operator->name ?? 'Sistema' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="empty">Sem movimentos no periodo.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="pagination-wrap">{{ $movements->links() }}</div>
    </div>
</div>

<style>
    .stock-page { max-width: 1300px; margin: 0 auto; color: #cbd5e1; }
    .stock-header, .filter-bar { display: flex; align-items: center; gap: 12px; }
    .stock-header { justify-content: space-between; margin-bottom: 20px; }
    .stock-header h1 { color: #fff; margin: 0; font-size: 2rem; }
    .stock-header p { color: #94a3b8; }
    .btn-secondary, .filter-bar button { border: 0; border-radius: 8px; padding: 10px 14px; font-weight: 700; text-decoration: none; cursor: pointer; }
    .btn-secondary { background: #1e293b; color: #e2e8f0; }
    .filter-bar { background: #0f172a; border: 1px solid #1e293b; border-radius: 8px; padding: 12px; margin-bottom: 16px; flex-wrap: wrap; }
    .filter-bar input, .filter-bar select { background: #020617; border: 1px solid #334155; border-radius: 8px; color: #e2e8f0; padding: 9px 10px; }
    .filter-bar button { background: #ea580c; color: #fff; }
    .table-card { background: #0f172a; border: 1px solid #1e293b; border-radius: 8px; overflow: hidden; }
    table { width: 100%; border-collapse: collapse; }
    th { color: #94a3b8; font-size: .72rem; text-transform: uppercase; text-align: left; padding: 12px; border-bottom: 1px solid #1e293b; }
    td { padding: 12px; border-bottom: 1px solid #1e293b; }
    .positive { color: #86efac; font-weight: 800; }
    .negative { color: #fca5a5; font-weight: 800; }
    .empty { text-align: center; color: #94a3b8; padding: 36px; }
    .pagination-wrap { padding: 12px; }
</style>
@endsection
