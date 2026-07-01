@extends('layouts.admin')

@section('page-title', 'Detalhe da Compra')

@section('content')
    <style>
        .purchase-wrap { display: grid; gap: 16px; }
        .panel { background: #0f172a; border: 1px solid rgba(255,255,255,.07); border-radius: 8px; padding: 18px; }
        .head { align-items: flex-start; display: flex; justify-content: space-between; gap: 14px; }
        .title { color: #fff; font-size: 24px; font-weight: 900; margin: 0; }
        .muted { color: #94a3b8; font-size: 13px; }
        .grid { display: grid; gap: 12px; grid-template-columns: repeat(5, minmax(0, 1fr)); }
        .label { color: #94a3b8; font-size: 11px; font-weight: 900; text-transform: uppercase; }
        .value { color: #fff; font-weight: 800; margin-top: 5px; }
        .btn { border: none; border-radius: 8px; cursor: pointer; display: inline-flex; font-weight: 900; padding: 11px 14px; text-decoration: none; }
        .btn-primary { background: #10b981; color: #020617; }
        .btn-ghost { background: #020617; border: 1px solid #1e293b; color: #94a3b8; }
        .alert { background: rgba(16,185,129,.12); border: 1px solid rgba(16,185,129,.28); border-radius: 8px; color: #bbf7d0; padding: 12px 14px; }
        .error { background: rgba(239,68,68,.12); border-color: rgba(239,68,68,.28); color: #fecaca; }
        .items-table { border-collapse: collapse; width: 100%; }
        .items-table th, .items-table td { border-bottom: 1px solid rgba(255,255,255,.06); padding: 12px; }
        .items-table th { color: #94a3b8; font-size: 12px; text-align: left; text-transform: uppercase; }
        .items-table td { color: #e2e8f0; font-size: 13px; }
        .summary { display: grid; gap: 7px; justify-content: end; text-align: right; color: #fff; }
        @media (max-width: 900px) { .grid { grid-template-columns: 1fr 1fr; } .head { flex-direction: column; } }
    </style>

    <div class="purchase-wrap">
        @if(session('success'))
            <div class="alert">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert error">{{ session('error') }}</div>
        @endif

        <div class="panel head">
            <div>
                <h1 class="title">Compra #{{ $purchase->id }}</h1>
                <div class="muted">{{ $purchase->document_number ?: 'Sem numero de documento' }}</div>
            </div>
            <div style="display:flex; gap:10px;">
                <a class="btn btn-ghost" href="{{ route('admin.purchases.index') }}">Voltar</a>
                @if($purchase->status !== 'received')
                    <form method="POST" action="{{ route('admin.purchases.receive', $purchase) }}">
                        @csrf
                        <button class="btn btn-primary" type="submit">Receber stock</button>
                    </form>
                @endif
            </div>
        </div>

        <div class="panel grid">
            <div><div class="label">Fornecedor</div><div class="value">{{ $purchase->supplier->company_name ?? 'Fornecedor removido' }}</div></div>
            <div><div class="label">Data</div><div class="value">{{ optional($purchase->purchase_date)->format('d/m/Y') }}</div></div>
            <div><div class="label">Estado</div><div class="value">{{ $purchase->status === 'received' ? 'Recebida' : 'Por receber' }}</div></div>
            <div>
                <div class="label">Liquidação</div>
                <div class="value">
                    {{ $purchase->payment_type === 'credit' ? 'Conta corrente' : 'Direta' }}
                    @if($purchase->currentAccountEntry)
                        <br><a class="muted" href="{{ route('admin.current-accounts.index', ['entity_type' => 'supplier', 'entity_id' => $purchase->supplier_id]) }}">Ver extrato</a>
                    @endif
                </div>
            </div>
            <div><div class="label">Recebida em</div><div class="value">{{ optional($purchase->received_at)->format('d/m/Y H:i') ?: '-' }}</div></div>
        </div>

        <div class="panel">
            <table class="items-table">
                <thead>
                    <tr>
                        <th>Produto</th>
                        <th>Qtd</th>
                        <th>Custo</th>
                        <th>IVA</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($purchase->items as $item)
                        <tr>
                            <td>{{ $item->product->name ?? 'Produto removido' }}</td>
                            <td>{{ $item->quantity }}</td>
                            <td>AOA {{ number_format((float) $item->unit_cost, 2, ',', '.') }}</td>
                            <td>{{ number_format((float) $item->tax_rate, 2, ',', '.') }}%</td>
                            <td>AOA {{ number_format((float) $item->total, 2, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="summary">
                <div>Subtotal: <strong>AOA {{ number_format((float) $purchase->subtotal, 2, ',', '.') }}</strong></div>
                <div>IVA: <strong>AOA {{ number_format((float) $purchase->tax, 2, ',', '.') }}</strong></div>
                <div style="font-size:18px;">Total: <strong>AOA {{ number_format((float) $purchase->total, 2, ',', '.') }}</strong></div>
            </div>
        </div>

        @if($purchase->notes)
            <div class="panel">
                <div class="label">Observações</div>
                <div class="value">{{ $purchase->notes }}</div>
            </div>
        @endif
    </div>
@endsection
