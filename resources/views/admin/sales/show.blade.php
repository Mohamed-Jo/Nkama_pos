@extends('layouts.admin')

@section('content')
    <style>
        .invoice-box {
            background: #0f172a;
            padding: 24px;
            border-radius: 18px;
            border: 1px solid rgba(255, 255, 255, .06);
            color: #e2e8f0;
        }

        .invoice-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
            gap: 20px;
        }

        .company-header {
            align-items: center;
            display: flex;
            gap: 14px;
        }

        .company-logo {
            align-items: center;
            background: #070a12;
            border: 1px solid rgba(255, 255, 255, .08);
            border-radius: 8px;
            display: flex;
            height: 64px;
            justify-content: center;
            overflow: hidden;
            width: 96px;
        }

        .company-logo img {
            max-height: 100%;
            max-width: 100%;
            object-fit: contain;
        }

        .company-name {
            color: #fff;
            font-size: 18px;
            font-weight: 800;
            margin-bottom: 4px;
        }

        .company-meta {
            color: #94a3b8;
            font-size: 12px;
            line-height: 1.5;
        }

        .btn-print {
            background: #f97316;
            color: black;
            padding: 10px 14px;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: .2s;
        }

        .btn-print:hover {
            transform: translateY(-2px);
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .table th {
            text-align: left;
            padding: 10px;
            border-bottom: 1px solid rgba(255, 255, 255, .08);
            color: #94a3b8;
            font-size: 13px;
        }

        .table td {
            padding: 12px 10px;
            border-bottom: 1px solid rgba(255, 255, 255, .05);
        }

        .total-box {
            text-align: right;
            margin-top: 20px;
            font-size: 14px;
            font-weight: 700;
            color: #f97316;
        }

        .total-box div {
            margin-top: 6px;
        }

        .total-box strong {
            font-size: 20px;
        }

        .nc-panel {
            background: rgba(239, 68, 68, .08);
            border: 1px solid rgba(239, 68, 68, .2);
            border-radius: 8px;
            margin-top: 22px;
            padding: 14px;
        }

        .nc-row {
            align-items: center;
            border-top: 1px solid rgba(255, 255, 255, .07);
            display: flex;
            gap: 12px;
            justify-content: space-between;
            padding: 10px 0;
        }

        .nc-row:first-of-type {
            border-top: none;
        }

        .nc-link {
            background: #ef4444;
            border-radius: 8px;
            color: #fff;
            font-size: 12px;
            font-weight: 800;
            padding: 8px 10px;
            text-decoration: none;
            white-space: nowrap;
        }
    </style>

    <div class="invoice-box">
        @php
            $viewTicket = \App\Services\ModuleSettings::enabled('view_ticket');
        @endphp

        <!-- HEADER -->
        <div class="invoice-header">

            <div>
                <div class="company-header">
                    @if($logoUrl)
                        <div class="company-logo">
                            <img src="{{ $logoUrl }}" alt="Logotipo da empresa">
                        </div>
                    @endif

                    <div>
                        <div class="company-name">{{ $company['name'] ?: config('app.name', 'Nkama ERP') }}</div>
                        <div class="company-meta">
                            @if(!empty($company['location']))
                                <div>{{ $company['location'] }}</div>
                            @endif
                            @if(!empty($company['nif']))
                                <div>NIF: {{ $company['nif'] }}</div>
                            @endif
                            @if(!empty($company['iban']))
                                <div>IBAN: {{ $company['iban'] }}</div>
                            @endif
                            @if(!empty($company['account_number']))
                                <div>No. conta: {{ $company['account_number'] }}</div>
                            @endif
                            @if(!empty($company['swift']))
                                <div>SWIFT: {{ $company['swift'] }}</div>
                            @endif
                        </div>
                    </div>
                </div>

                <h1 class="text-2xl font-bold" style="margin-top:18px;">
                    🧾 Fatura #{{ $sale->invoice_number }}
                </h1>
            </div>

            <div style="display:flex; gap:8px; align-items:center;">
                @php
                    $saleTicketUrl = route('admin.sales.ticket', $sale);
                    $salePrintUrl = route('admin.print.sales', $sale);
                @endphp
                <a href="{{ $viewTicket ? $saleTicketUrl : $salePrintUrl }}"
                    @if($viewTicket) target="_blank" @else data-direct-print-url="{{ $salePrintUrl }}" @endif
                    class="btn-print" style="text-decoration:none;">
                    {{ $viewTicket ? 'Ticket' : 'Imprimir ticket' }}
                </a>
                @php
                    $availableToCredit = max((float) $sale->total - (float) $sale->creditNotes->sum('total'), 0);
                @endphp
                @if(\App\Services\OperatorPermissions::allows(session('operator_role'), 'sales.credit_note') && $availableToCredit > 0)
                    <a href="{{ route('admin.sales.credit-notes.create', $sale) }}" class="btn-print" style="background:#ef4444; color:#fff; text-decoration:none;">
                        Emitir NC
                    </a>
                @endif
                <button onclick="window.print()" class="btn-print">
                    Imprimir
                </button>
            </div>

        </div>

        <!-- INFO -->
        <div style="margin-bottom:20px; color:#94a3b8;">

            <p>
                Cliente:
                <strong style="color:white;">
                    {{ $sale->customer->name ?? 'Consumidor Final' }}
                </strong>
            </p>

            <p>
                Data:
                <strong style="color:white;">
                    {{ optional($sale->created_at)->format('d/m/Y H:i') }}
                </strong>
            </p>

        </div>

        <!-- TABLE -->
        <table class="table">

            <thead>
                <tr>
                    <th>Produto</th>
                    <th>Qtd</th>
                    <th>Preço</th>
                    <th>IVA</th>
                    <th>Subtotal</th>
                </tr>
            </thead>

            <tbody>

                @foreach ($sale->items as $item)
                    <tr>

                        <td>
                            {{ $item->product->name ?? 'Produto removido' }}
                        </td>

                        <td>
                            {{ $item->quantity }}
                        </td>

                        <td>
                            {{ number_format($item->unit_price, 2) }}
                        </td>

                        <td>
                            {{ number_format($item->tax_rate ?? 0, 2) }}%
                        </td>

                        <td>
                            {{ number_format($item->subtotal, 2) }}
                        </td>

                    </tr>
                @endforeach

            </tbody>

        </table>

        <!-- TOTAL -->
        <div class="total-box">
            <div>Subtotal: AOA {{ number_format($sale->subtotal, 2) }}</div>
            <div>
                IVA
                @if(($sale->items ?? collect())->pluck('tax_rate')->unique()->count() === 1)
                    ({{ number_format($sale->tax_rate ?? 0, 2) }}%)
                @else
                    (taxas varias)
                @endif:
                AOA {{ number_format($sale->tax, 2) }}
            </div>
            <div><strong>Total: AOA {{ number_format($sale->total, 2) }}</strong></div>
        </div>

        @if($sale->creditNotes->isNotEmpty())
            <div class="nc-panel">
                <h3 style="color:#fff; font-size:15px; margin:0 0 8px;">Notas de Crédito emitidas</h3>
                @foreach($sale->creditNotes as $note)
                    <div class="nc-row">
                        <div>
                            <strong style="color:#fca5a5;">NC {{ $note->invoice_number }}</strong>
                            <div style="color:#94a3b8; font-size:12px;">
                                {{ optional($note->created_at)->format('d/m/Y H:i') }} · AOA {{ number_format((float) $note->total, 2, ',', '.') }}
                            </div>
                        </div>
                        @php
                            $noteTicketUrl = route('admin.credit-notes.ticket', $note);
                            $notePrintUrl = route('admin.print.credit-notes', $note);
                        @endphp
                        <a class="nc-link"
                            href="{{ $viewTicket ? $noteTicketUrl : $notePrintUrl }}"
                            @if($viewTicket) target="_blank" @else data-direct-print-url="{{ $notePrintUrl }}" @endif>
                            {{ $viewTicket ? 'Ver/Reimprimir' : 'Imprimir' }}
                        </a>
                    </div>
                @endforeach
            </div>
        @endif

    </div>
@endsection
