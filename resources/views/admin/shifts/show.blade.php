@extends('layouts.admin')

@section('page-title', 'Auditoria do Caixa #' . $shift->id)

@section('content')
<style>
    .audit-container {
        padding: 24px;
        max-width: 1600px;
        margin: 0 auto;
        font-family: system-ui, -apple-system, sans-serif;
    }

    /* BOTÃO VOLTAR */
    .btn-back {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        color: var(--muted);
        text-decoration: none;
        font-size: 14px;
        margin-bottom: 20px;
        transition: color 0.2s;
    }
    .btn-back:hover {
        color: var(--primary);
    }

    .btn-print {
        background: rgba(249, 115, 22, 0.12);
        border: 1px solid rgba(249, 115, 22, 0.28);
        border-radius: 6px;
        color: #f97316;
        cursor: pointer;
        font-size: 13px;
        font-weight: 700;
        padding: 8px 12px;
    }

    /* GRELHA REPARTIDA */
    .audit-grid {
        display: grid;
        grid-template-columns: 1fr 2fr;
        gap: 24px;
    }

    @media (max-width: 1024px) {
        .audit-grid {
            grid-template-columns: 1fr;
        }
    }

    .audit-card {
        background: rgba(17, 24, 39, 0.7);
        backdrop-filter: blur(12px);
        border: 1px solid rgba(255, 255, 255, 0.06);
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        height: fit-content;
    }

    .card-title {
        font-size: 16px;
        font-weight: 600;
        color: #ffffff;
        margin-top: 0;
        margin-bottom: 20px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.06);
        padding-bottom: 10px;
    }

    /* DETALHES DE LINHA */
    .info-row {
        display: flex;
        justify-content: space-between;
        padding: 12px 0;
        border-bottom: 1px solid rgba(255, 255, 255, 0.04);
        font-size: 14px;
    }
    .info-row:last-child {
        border-bottom: none;
    }
    .info-label { color: var(--muted); }
    .info-value { color: #e5e7eb; font-weight: 500; }

    /* BALANÇO CENTRAL */
    .balance-box {
        background: rgba(255, 255, 255, 0.02);
        border-radius: 12px;
        padding: 16px;
        text-align: center;
        margin-top: 15px;
        border: 1px solid rgba(255, 255, 255, 0.04);
    }

    .badge {
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 13px;
        font-weight: 600;
        display: inline-block;
        margin-top: 8px;
    }
    .badge-success { background: rgba(16, 185, 129, 0.15); color: #10b981; }
    .badge-danger { background: rgba(239, 68, 68, 0.15); color: #ef4444; }
    .badge-neutral { background: rgba(156, 163, 175, 0.15); color: #9ca3af; }

    /* TABELA DE PAGAMENTOS */
    .custom-table {
        width: 100%;
        border-collapse: collapse;
        text-align: left;
        font-size: 14px;
    }

    .custom-table th {
        padding: 12px 16px;
        color: #9ca3af;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 11px;
        letter-spacing: 0.05em;
        border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    }

    .custom-table td {
        padding: 14px 16px;
        color: #e5e7eb;
        border-bottom: 1px solid rgba(255, 255, 255, 0.04);
    }

    .method-tag {
        font-size: 11px;
        text-transform: uppercase;
        font-weight: 700;
        padding: 2px 6px;
        border-radius: 4px;
        background: rgba(255,255,255,0.08);
    }
</style>

<div class="audit-container">
    
    <a href="{{ route('admin.shifts.history') }}" class="btn-back">
        ← Voltar ao Histórico
    </a>

    <form method="POST" action="{{ route('admin.print.shifts', $shift) }}" style="margin-bottom:20px;">
        @csrf
        <button type="submit" class="btn-print">Imprimir resumo</button>
    </form>

    <div class="audit-grid">
        
        <div class="audit-card">
            <h3 class="card-title">Resumo Financeiro</h3>
            
            <div class="info-row">
                <span class="info-label">Operador ID</span>
                <span class="info-value">#{{ $shift->operator_id }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Abertura</span>
                <span class="info-value">{{ \Carbon\Carbon::parse($shift->opened_at)->format('d/m/Y H:i') }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Fecho</span>
                <span class="info-value">{{ \Carbon\Carbon::parse($shift->closed_at)->format('d/m/Y H:i') }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Fundo Inicial</span>
                <span class="info-value">{{ number_format($shift->opening_cash, 2) }} Kz</span>
            </div>
            <div class="info-row" style="border-bottom: 1px dashed rgba(255,255,255,0.1); padding-bottom: 15px;">
                <span class="info-label">Vendas em Dinheiro (Físico)</span>
                <span class="info-value">{{ number_format($shift->cash_sales_total, 2) }} Kz</span>
            </div>

            <div class="info-row" style="margin-top: 10px;">
                <span class="info-label">Esperado em Caixa</span>
                <span class="info-value" style="color: var(--primary);">{{ number_format($shift->expected_cash, 2) }} Kz</span>
            </div>
            <div class="info-row">
                <span class="info-label">Contado pelo Operador</span>
                <span class="info-value">{{ number_format($shift->closing_cash, 2) }} Kz</span>
            </div>

            <div class="balance-box">
                <span style="font-size: 12px; color: var(--muted); display: block;">Resultado da Auditoria</span>
                @if($shift->difference == 0)
                    <span class="badge badge-success">✓ Caixa Perfeito</span>
                @elseif($shift->difference > 0)
                    <span class="badge badge-neutral">+{{ number_format($shift->difference, 2) }} Kz (Sobra)</span>
                @else
                    <span class="badge badge-danger">{{ number_format($shift->difference, 2) }} Kz (Quebra)</span>
                @endif
            </div>

            <h3 class="card-title" style="margin-top: 30px; margin-bottom: 15px;">Valores Digitais (Banco)</h3>
            <div class="info-row">
                <span class="info-label">Multicaixa / Cartão</span>
                <span class="info-value">{{ number_format($shift->card_sales_total + $shift->multi_sales_total, 2) }} Kz</span>
            </div>
            <div class="info-row">
                <span class="info-label">Transferências</span>
                <span class="info-value">{{ number_format($shift->transf_sales_total, 2) }} Kz</span>
            </div>
        </div>

        <div class="audit-card">
            <h3 class="card-title">Fluxo de Entradas do Turno</h3>
            
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>Hora</th>
                        <th>Ref. Pagamento</th>
                        <th>Método</th>
                        <th style="text-align: right;">Valor</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($payments as $payment)
                        <tr>
                            <td style="color: var(--muted);">{{ \Carbon\Carbon::parse($payment->created_at)->format('H:i:s') }}</td>
                            <td>#{{ $payment->id }}</td>
                            <td>
                                @if($payment->amount < 0)
                                    <span class="method-tag" style="color: #fb7185;">Reembolso</span>
                                @endif
                                @if($payment->method == 'cash')
                                    <span class="method-tag" style="color: #10b981;">Dinheiro</span>
                                @elseif($payment->method == 'card' || $payment->method == 'multi')
                                    <span class="method-tag" style="color: #3b82f6;">Multicaixa</span>
                                @else
                                    <span class="method-tag" style="color: #a855f7;">Transf.</span>
                                @endif
                            </td>
                            <td style="text-align: right; font-weight: 600; color: {{ $payment->amount < 0 ? '#fb7185' : 'inherit' }};">{{ number_format($payment->amount, 2) }} Kz</td>
                        </tr>
                    @endforeach

                    @foreach(($cashMovements ?? collect()) as $movement)
                        <tr>
                            <td style="color: var(--muted);">{{ \Carbon\Carbon::parse($movement->created_at)->format('H:i:s') }}</td>
                            <td>CC #{{ $movement->id }}</td>
                            <td>
                                <span class="method-tag" style="color: {{ $movement->amount < 0 ? '#fb7185' : '#10b981' }};">
                                    {{ $movement->amount < 0 ? 'Saida' : 'Entrada' }}
                                </span>
                                <span class="method-tag" style="color: #a3e635;">{{ strtoupper($movement->method) }}</span>
                            </td>
                            <td style="text-align: right; font-weight: 600; color: {{ $movement->amount < 0 ? '#fb7185' : 'inherit' }};">{{ number_format($movement->amount, 2) }} Kz</td>
                        </tr>
                    @endforeach

                    @if($payments->isEmpty() && ($cashMovements ?? collect())->isEmpty())
                        <tr>
                            <td colspan="4" style="text-align: center; color: var(--muted); padding: 40px;">
                                Nenhuma transação financeira registada neste turno.
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>

    </div>
</div>
@endsection
