@extends('layouts.admin')

@section('page-title', 'Detalhe da Compra')

@section('content')
@php
    $warehouses = $warehouses ?? collect();
    $warehouseDefaults = $warehouseDefaults ?? [];
    $operations = $operations ?? [];
@endphp

    <style>
        .purchase-wrap { display: grid; gap: 16px; }
        .panel { background: #0f172a; border: 1px solid rgba(255,255,255,.07); border-radius: 8px; padding: 18px; }
        .head { align-items: flex-start; display: flex; justify-content: space-between; gap: 14px; }
        .title { color: #fff; font-size: 24px; font-weight: 900; margin: 0; }
        .muted { color: #94a3b8; font-size: 13px; }
        .grid { display: grid; gap: 12px; grid-template-columns: repeat(4, minmax(0, 1fr)); }
        .label { color: #94a3b8; font-size: 11px; font-weight: 900; text-transform: uppercase; }
        .value { color: #fff; font-weight: 800; margin-top: 5px; }
        .btn { border: none; border-radius: 8px; cursor: pointer; display: inline-flex; font-weight: 900; padding: 11px 14px; text-decoration: none; }
        .btn-primary { background: #10b981; color: #020617; }
        .btn-info { background: #38bdf8; color: #020617; }
        .btn-warning { background: #f59e0b; color: #020617; }
        .btn-danger { background: #ef4444; color: #fff; }
        .btn-ghost { background: #020617; border: 1px solid #1e293b; color: #94a3b8; }
        .alert { background: rgba(16,185,129,.12); border: 1px solid rgba(16,185,129,.28); border-radius: 8px; color: #bbf7d0; padding: 12px 14px; }
        .error { background: rgba(239,68,68,.12); border-color: rgba(239,68,68,.28); color: #fecaca; }
        .items-table { border-collapse: collapse; width: 100%; }
        .items-table th, .items-table td { border-bottom: 1px solid rgba(255,255,255,.06); padding: 12px; }
        .items-table th { color: #94a3b8; font-size: 12px; text-align: left; text-transform: uppercase; }
        .items-table td { color: #e2e8f0; font-size: 13px; }
        .receive-select { background:#020617; border:1px solid #1e293b; border-radius:8px; color:#fff; min-width:220px; padding:8px; }
        .receive-input { background:#020617; border:1px solid #1e293b; border-radius:8px; color:#fff; max-width:90px; padding:8px; width:100%; }
        .reject-input { background:#020617; border:1px solid #1e293b; border-radius:8px; color:#fff; min-height:42px; padding:8px 10px; width:220px; }
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
        @if($errors->any())
            <div class="alert error">{{ $errors->first() }}</div>
        @endif

        @php
            $currentOperatorId = (int) session('operator_id');
            $reviewedByCreator = $purchase->operator_id && $currentOperatorId && (int) $purchase->operator_id === $currentOperatorId;
        @endphp

        <div class="panel head">
            <div>
                <h1 class="title">Compra #{{ $purchase->id }}</h1>
                <div class="muted">{{ $purchase->document_number ?: 'Sem numero de documento' }}</div>
            </div>
            <div style="display:flex; gap:10px; flex-wrap:wrap;">
                <a class="btn btn-ghost" href="{{ route('admin.purchases.index') }}">Voltar</a>
                @if($canApprovePurchase && !$purchase->isClosedForReceiving() && !$purchase->isApproved() && !$reviewedByCreator)
                    <form method="POST" action="{{ route('admin.purchases.approve', $purchase) }}">
                        @csrf
                        @method('PATCH')
                        <button class="btn btn-primary" type="submit">Aprovar</button>
                    </form>
                @endif
                @if($canApprovePurchase && !$purchase->isClosedForReceiving() && !$purchase->isApproved() && !$purchase->isRejected() && !$reviewedByCreator && $purchase->items->sum('received_quantity') <= 0)
                    <form method="POST" action="{{ route('admin.purchases.reject', $purchase) }}" style="display:flex; gap:8px;">
                        @csrf
                        @method('PATCH')
                        <input class="reject-input" type="text" name="rejection_reason" placeholder="Motivo opcional">
                        <button class="btn btn-danger" type="submit">Rejeitar</button>
                    </form>
                @endif
                @if(!$purchase->isClosedForReceiving() && !$purchase->isApproved() && !$purchase->isRejected() && (!$canApprovePurchase || $reviewedByCreator))
                    <span class="muted" style="align-self:center;">Aguardando aprovacao de outro operador.</span>
                @endif
                @if($canCreatePurchase && $purchase->isApproved() && $purchase->status === \App\Models\Purchase::STATUS_DRAFT)
                    <form method="POST" action="{{ route('admin.purchases.status', $purchase) }}">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="status" value="{{ \App\Models\Purchase::STATUS_ORDERED }}">
                        <button class="btn btn-info" type="submit">Marcar pedido enviado</button>
                    </form>
                @endif
            </div>
        </div>

        <div class="panel grid">
            <div><div class="label">Fornecedor</div><div class="value">{{ $purchase->supplier->company_name ?? 'Fornecedor removido' }}</div></div>
            <div><div class="label">Data</div><div class="value">{{ optional($purchase->purchase_date)->format('d/m/Y') }}</div></div>
            <div><div class="label">Registada por</div><div class="value">{{ $purchase->operator->name ?? 'Sistema' }}</div></div>
            <div><div class="label">Vencimento</div><div class="value">{{ optional($purchase->due_date)->format('d/m/Y') ?: '-' }}</div></div>
            <div>
                <div class="label">Aprovacao</div>
                <div class="value">
                    {{ $purchase->approvalLabel() }}
                    @if($purchase->approved_at)
                        <br><span class="muted">{{ optional($purchase->approved_at)->format('d/m/Y H:i') }} por {{ $purchase->approver->name ?? 'Sistema' }}</span>
                    @elseif($purchase->rejected_at)
                        <br><span class="muted">{{ optional($purchase->rejected_at)->format('d/m/Y H:i') }} por {{ $purchase->rejecter->name ?? 'Sistema' }}</span>
                    @endif
                </div>
            </div>
            <div><div class="label">Estado</div><div class="value">{{ $purchase->statusLabel() }}</div></div>
            <div>
                <div class="label">Liquidação</div>
                <div class="value">
                    {{ $purchase->payment_type === 'credit' ? 'Conta corrente' : 'Direta' }}
                    @if($purchase->currentAccountEntry)
                        <br><a class="muted" href="{{ route('admin.current-accounts.index', ['entity_type' => 'supplier', 'entity_id' => $purchase->supplier_id]) }}">Ver extrato</a>
                    @endif
                </div>
            </div>
            <div><div class="label">Pagamento</div><div class="value">{{ $purchase->paymentStatusLabel() }} @if($purchase->isOverdue()) <span style="color:#fb7185;">(vencida)</span> @endif</div></div>
            <div><div class="label">Pago</div><div class="value">AOA {{ number_format((float) $purchase->paid_amount, 2, ',', '.') }}</div></div>
            <div><div class="label">Saldo</div><div class="value">AOA {{ number_format((float) $purchase->balance, 2, ',', '.') }}</div></div>
            <div><div class="label">Recebida em</div><div class="value">{{ optional($purchase->received_at)->format('d/m/Y H:i') ?: '-' }}</div></div>
        </div>

        <form class="panel" method="POST" action="{{ route('admin.purchases.receive', $purchase) }}">
            @csrf
            <table class="items-table">
                <thead>
                    <tr>
                        <th>Produto</th>
                        <th>Pedida</th>
                        <th>Recebida</th>
                        <th>Pendente</th>
                        <th>Custo</th>
                        <th>IVA</th>
                        <th>Total</th>
                        @if($canReceivePurchase && $purchase->isApproved() && !$purchase->isClosedForReceiving())
                            <th>Receber agora</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach($purchase->items as $item)
                        @php
                            $pending = max((int) $item->quantity - (int) $item->received_quantity, 0);
                        @endphp
                        <tr>
                            <td>{{ $item->product->name ?? 'Produto removido' }}</td>
                            <td>{{ $item->quantity }}</td>
                            <td>{{ $item->received_quantity }}</td>
                            <td>{{ $pending }}</td>
                            <td>AOA {{ number_format((float) $item->unit_cost, 2, ',', '.') }}</td>
                            <td>{{ number_format((float) $item->tax_rate, 2, ',', '.') }}%</td>
                            <td>AOA {{ number_format((float) $item->total, 2, ',', '.') }}</td>
                            @if($canReceivePurchase && $purchase->isApproved() && !$purchase->isClosedForReceiving())
                                <td>
                                    <input class="receive-input" type="number" name="received[{{ $item->id }}]" min="0" max="{{ $pending }}" value="{{ $pending }}">
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="summary">
                <div>Subtotal: <strong>AOA {{ number_format((float) $purchase->subtotal, 2, ',', '.') }}</strong></div>
                <div>IVA: <strong>AOA {{ number_format((float) $purchase->tax, 2, ',', '.') }}</strong></div>
                <div style="font-size:18px;">Total: <strong>AOA {{ number_format((float) $purchase->total, 2, ',', '.') }}</strong></div>
            </div>
            @if($canReceivePurchase && $purchase->isApproved() && !$purchase->isClosedForReceiving())
                <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:14px;">                @if(\App\Services\ModuleSettings::enabled('stock_warehouses'))
                    <select class="receive-select" name="warehouse_id">
                        @foreach($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}" @selected(($warehouseDefaults['purchases'] ?? null) == $warehouse->id)>{{ $warehouse->name }}</option>
                        @endforeach
                    </select>
                @endif
                    <button class="btn btn-primary" type="submit">Registar recebimento</button>
                </div>
            @elseif(!$purchase->isApproved())
                <div class="muted" style="margin-top:14px; text-align:right;">Aprovacao necessaria para receber stock.</div>
            @endif
        </form>

        @if($purchase->rejection_reason)
            <div class="panel">
                <div class="label">Motivo da rejeicao</div>
                <div class="value">{{ $purchase->rejection_reason }}</div>
            </div>
        @endif

        @if($purchase->notes)
            <div class="panel">
                <div class="label">Observações</div>
                <div class="value">{{ $purchase->notes }}</div>
            </div>
        @endif
    </div>
@endsection
