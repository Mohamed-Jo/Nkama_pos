@extends('layouts.admin')

@section('page-title', 'Transferencias de Stock')

@section('content')
<div class="transfer-page">
    <div class="page-header">
        <div>
            <h1>Transferencias de Stock</h1>
            <p>Pedidos internos com aprovacao antes de movimentar stock.</p>
        </div>
        <a href="{{ route('admin.warehouses.index') }}" class="btn-secondary">Voltar aos armazens</a>
    </div>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-error">{{ session('error') }}</div>@endif

    <section class="panel">
        <table>
            <thead>
                <tr>
                    <th>Referencia</th>
                    <th>Data</th>
                    <th>Origem</th>
                    <th>Destino</th>
                    <th>Artigos</th>
                    <th>Estado</th>
                    <th>Operador</th>
                    <th>Acao</th>
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
                                @if($item->lot_number || $item->serial_number || $item->expires_at)
                                    <small>Lote {{ $item->lot_number ?: '-' }} @if($item->serial_number) · Serie {{ $item->serial_number }} @endif @if($item->expires_at) · Val. {{ $item->expires_at->format('d/m/Y') }} @endif</small>
                                @endif
                            @endforeach
                            @if($transfer->notes)<small>{{ $transfer->notes }}</small>@endif
                        </td>
                        <td>
                            <span class="status status-{{ $transfer->status }}">{{ ucfirst($transfer->status) }}</span>
                            @if($transfer->approved_at)<small>Aprovado por {{ $transfer->approver->name ?? 'Sistema' }} em {{ $transfer->approved_at->format('d/m/Y H:i') }}</small>@endif
                            @if($transfer->rejected_at)<small>Rejeitado por {{ $transfer->rejecter->name ?? 'Sistema' }}</small>@endif
                            @if($transfer->rejection_reason)<small>{{ $transfer->rejection_reason }}</small>@endif
                        </td>
                        <td>{{ $transfer->operator->name ?? 'Sistema' }}</td>
                        <td>
                            @if($transfer->status === 'pending')
                                <div class="actions">
                                    <form method="POST" action="{{ route('admin.warehouses.transfers.approve', $transfer) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button class="btn-ok" type="submit">Aprovar</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.warehouses.transfers.reject', $transfer) }}">
                                        @csrf
                                        @method('PATCH')
                                        <input name="rejection_reason" placeholder="Motivo">
                                        <button class="btn-danger" type="submit">Rejeitar</button>
                                    </form>
                                </div>
                            @else
                                <span class="muted">Fechado</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="empty">Sem transferencias registadas.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="pagination-wrap">{{ $transfers->links() }}</div>
    </section>
</div>

<style>
    .transfer-page { max-width: 1300px; margin: 0 auto; color: #cbd5e1; }
    .page-header { display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-bottom: 20px; }
    .page-header h1 { color: #fff; margin: 0; font-size: 2rem; }
    .page-header p, small, .muted { color: #94a3b8; }
    .btn-secondary, .btn-ok, .btn-danger { border: 0; border-radius: 8px; cursor: pointer; font-weight: 800; padding: 9px 12px; text-decoration: none; }
    .btn-secondary { background: #1e293b; color: #e2e8f0; }
    .btn-ok { background: #16a34a; color: #fff; }
    .btn-danger { background: #dc2626; color: #fff; }
    .alert { margin-bottom: 16px; padding: 12px 14px; border-radius: 8px; }
    .alert-success { background: #052e1b; color: #86efac; border: 1px solid #166534; }
    .alert-error { background: #450a0a; color: #fecaca; border: 1px solid #991b1b; }
    .panel { background: #0f172a; border: 1px solid #1e293b; border-radius: 8px; overflow: hidden; }
    table { width: 100%; border-collapse: collapse; }
    th { color: #94a3b8; font-size: .72rem; text-transform: uppercase; text-align: left; padding: 12px; border-bottom: 1px solid #1e293b; }
    td { padding: 12px; border-bottom: 1px solid #1e293b; vertical-align: top; }
    td strong { color: #fff; }
    small { display: block; font-size: .76rem; margin-top: 3px; }
    .status { border-radius: 999px; display: inline-flex; font-size: .72rem; font-weight: 900; padding: 4px 8px; text-transform: uppercase; }
    .status-pending { background: #78350f; color: #fde68a; }
    .status-completed { background: #064e3b; color: #a7f3d0; }
    .status-rejected { background: #7f1d1d; color: #fecaca; }
    .actions { display: grid; gap: 8px; min-width: 180px; }
    .actions form { display: flex; gap: 6px; }
    .actions input { background: #020617; border: 1px solid #334155; border-radius: 8px; color: #e2e8f0; min-width: 90px; padding: 8px; width: 100%; }
    .empty { text-align: center; color: #94a3b8; padding: 32px; }
    .pagination-wrap { padding: 12px; }
</style>
@endsection