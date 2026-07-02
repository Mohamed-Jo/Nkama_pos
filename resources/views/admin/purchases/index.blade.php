@extends('layouts.admin')

@section('page-title', 'Compras')

@section('content')
    <style>
        .purchase-wrap { display: grid; gap: 18px; }
        .purchase-head { align-items: center; display: flex; justify-content: space-between; gap: 14px; }
        .purchase-title { color: #fff; font-size: 24px; font-weight: 900; margin: 0; }
        .purchase-subtitle { color: #94a3b8; font-size: 13px; margin-top: 4px; }
        .purchase-btn { background: #38bdf8; border: none; border-radius: 8px; color: #020617; display: inline-flex; font-weight: 900; padding: 11px 14px; text-decoration: none; }
        .purchase-alert { background: rgba(16,185,129,.12); border: 1px solid rgba(16,185,129,.28); border-radius: 8px; color: #bbf7d0; padding: 12px 14px; }
        .purchase-error { background: rgba(239,68,68,.12); border-color: rgba(239,68,68,.28); color: #fecaca; }
        .stats-grid { display: grid; gap: 12px; grid-template-columns: repeat(6, minmax(0, 1fr)); }
        .stat-box { background: #0f172a; border: 1px solid rgba(255,255,255,.07); border-radius: 8px; padding: 16px; }
        .stat-label { color: #94a3b8; font-size: 12px; font-weight: 800; text-transform: uppercase; }
        .stat-value { color: #fff; font-size: 22px; font-weight: 900; margin-top: 7px; }
        .purchase-panel { background: #0f172a; border: 1px solid rgba(255,255,255,.07); border-radius: 8px; overflow: hidden; }
        .purchase-table { border-collapse: collapse; width: 100%; }
        .purchase-table th { color: #94a3b8; font-size: 12px; text-align: left; text-transform: uppercase; }
        .purchase-table th, .purchase-table td { border-bottom: 1px solid rgba(255,255,255,.06); padding: 13px 14px; }
        .purchase-table td { color: #e2e8f0; font-size: 13px; }
        .badge { border-radius: 999px; display: inline-flex; font-size: 11px; font-weight: 900; padding: 5px 8px; text-transform: uppercase; }
        .badge-draft { background: rgba(251,191,36,.12); color: #fde68a; }
        .badge-ordered { background: rgba(56,189,248,.12); color: #bae6fd; }
        .badge-partial { background: rgba(249,115,22,.14); color: #fed7aa; }
        .badge-received { background: rgba(16,185,129,.12); color: #bbf7d0; }
        .badge-rejected { background: rgba(239,68,68,.14); color: #fecaca; }
        .empty-box { color: #94a3b8; padding: 28px; text-align: center; }
        @media (max-width: 900px) { .stats-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    </style>

    <div class="purchase-wrap">
        <div class="purchase-head">
            <div>
                <h1 class="purchase-title">Compras</h1>
                <div class="purchase-subtitle">Registo de entradas de mercadoria e atualização de stock.</div>
            </div>
            @if($canCreatePurchase)
                <a class="purchase-btn" href="{{ route('admin.purchases.create') }}">Nova compra</a>
            @endif
        </div>

        @if(session('success'))
            <div class="purchase-alert">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="purchase-alert purchase-error">{{ session('error') }}</div>
        @endif

        <div class="stats-grid">
            <div class="stat-box"><div class="stat-label">Total</div><div class="stat-value">{{ $totals['count'] }}</div></div>
            <div class="stat-box"><div class="stat-label">Em aberto</div><div class="stat-value">{{ $totals['open'] }}</div></div>
            <div class="stat-box"><div class="stat-label">Por aprovar</div><div class="stat-value">{{ $totals['pending_approval'] }}</div></div>
            <div class="stat-box"><div class="stat-label">Parciais</div><div class="stat-value">{{ $totals['partial'] }}</div></div>
            <div class="stat-box"><div class="stat-label">Recebidas</div><div class="stat-value">{{ $totals['received'] }}</div></div>
            <div class="stat-box"><div class="stat-label">Valor</div><div class="stat-value">AOA {{ number_format($totals['value'], 2, ',', '.') }}</div></div>
        </div>

        <div class="purchase-panel">
            @if($purchases->isEmpty())
                <div class="empty-box">Nenhuma compra registada.</div>
            @else
                <table class="purchase-table">
                    <thead>
                        <tr>
                            <th>Compra</th>
                            <th>Fornecedor</th>
                            <th>Data</th>
                            <th>Aprovacao</th>
                            <th>Estado</th>
                            <th>Liquidação</th>
                            <th>Pagamento</th>
                            <th>Saldo</th>
                            <th>Total</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($purchases as $purchase)
                            <tr>
                                <td>#{{ $purchase->id }}<br><span style="color:#94a3b8;">{{ $purchase->document_number ?: 'Sem documento' }}</span></td>
                                <td>{{ $purchase->supplier->company_name ?? 'Fornecedor removido' }}</td>
                                <td>{{ optional($purchase->purchase_date)->format('d/m/Y') }}</td>
                                <td>
                                    <span class="badge {{ $purchase->approvalBadgeClass() }}">
                                        {{ $purchase->approvalLabel() }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge {{ $purchase->statusBadgeClass() }}">
                                        {{ $purchase->statusLabel() }}
                                    </span>
                                </td>
                                <td>{{ $purchase->payment_type === 'credit' ? 'Conta corrente' : 'Direta' }}</td>
                                <td>
                                    <span class="badge {{ $purchase->paymentBadgeClass() }}">{{ $purchase->paymentStatusLabel() }}</span>
                                    @if($purchase->isOverdue())
                                        <br><span style="color:#fb7185; font-size:11px;">Vencida</span>
                                    @endif
                                </td>
                                <td>AOA {{ number_format((float) $purchase->balance, 2, ',', '.') }}</td>
                                <td>AOA {{ number_format((float) $purchase->total, 2, ',', '.') }}</td>
                                <td><a class="purchase-btn" style="padding:8px 10px;" href="{{ route('admin.purchases.show', $purchase) }}">Abrir</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        {{ $purchases->links() }}
    </div>
@endsection
