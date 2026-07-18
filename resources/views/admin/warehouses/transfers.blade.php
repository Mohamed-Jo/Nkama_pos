@extends('layouts.admin')

@section('page-title', 'Transferencias de Stock')

@section('content')
<div class="transfer-page">
    <div class="page-header">
        <div>
            <h1>Transferencias de Stock</h1>
            <p>Historico de movimentos internos entre armazens.</p>
        </div>
        <a href="{{ route('admin.warehouses.index') }}" class="btn-secondary">Voltar aos armazens</a>
    </div>

    <section class="panel">
        <table>
            <thead>
                <tr>
                    <th>Referencia</th>
                    <th>Data</th>
                    <th>Origem</th>
                    <th>Destino</th>
                    <th>Artigos</th>
                    <th>Operador</th>
                    <th>Observacao</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transfers as $transfer)
                    <tr>
                        <td><strong>{{ $transfer->reference }}</strong></td>
                        <td>{{ $transfer->created_at?->format('d/m/Y H:i') }}</td>
                        <td>{{ $transfer->fromWarehouse->name ?? '-' }}</td>
                        <td>{{ $transfer->toWarehouse->name ?? '-' }}</td>
                        <td>
                            @foreach($transfer->items as $item)
                                <div>{{ $item->product->name ?? 'Produto removido' }}: {{ number_format((float) $item->quantity, 0, ',', '.') }}</div>
                            @endforeach
                        </td>
                        <td>{{ $transfer->operator->name ?? 'Sistema' }}</td>
                        <td>{{ $transfer->notes ?: '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="empty">Sem transferencias registadas.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="pagination-wrap">{{ $transfers->links() }}</div>
    </section>
</div>

<style>
    .transfer-page { max-width: 1200px; margin: 0 auto; color: #cbd5e1; }
    .page-header { display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-bottom: 20px; }
    .page-header h1 { color: #fff; margin: 0; font-size: 2rem; }
    .page-header p { color: #94a3b8; }
    .btn-secondary { background: #1e293b; border-radius: 8px; color: #e2e8f0; font-weight: 800; padding: 10px 14px; text-decoration: none; }
    .panel { background: #0f172a; border: 1px solid #1e293b; border-radius: 8px; overflow: hidden; }
    table { width: 100%; border-collapse: collapse; }
    th { color: #94a3b8; font-size: .72rem; text-transform: uppercase; text-align: left; padding: 12px; border-bottom: 1px solid #1e293b; }
    td { padding: 12px; border-bottom: 1px solid #1e293b; vertical-align: top; }
    td strong { color: #fff; }
    .empty { text-align: center; color: #94a3b8; padding: 32px; }
    .pagination-wrap { padding: 12px; }
</style>
@endsection
