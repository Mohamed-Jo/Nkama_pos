<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Proforma {{ $sale->invoice_number }}</title>
    <style>
        @page { margin: 24px 28px; }
        body { color: #111827; font-family: DejaVu Sans, sans-serif; font-size: 10.5px; }
        .brand-line { background: #ea580c; height: 5px; margin-bottom: 16px; width: 100%; }
        .top { display: table; width: 100%; }
        .company, .doc-box { display: table-cell; vertical-align: top; }
        .company { width: 58%; }
        .doc-box { text-align: right; width: 42%; }
        .logo img { max-height: 64px; max-width: 190px; object-fit: contain; }
        .company-name { font-size: 16px; font-weight: 900; margin-top: 5px; text-transform: uppercase; }
        .muted { color: #4b5563; line-height: 1.45; }
        .doc-title { color: #111827; font-size: 22px; font-weight: 900; letter-spacing: .04em; text-transform: uppercase; }
        .doc-number { color: #ea580c; font-size: 14px; font-weight: 900; margin-top: 3px; }
        .stamp { border: 1px solid #f97316; color: #9a3412; display: inline-block; font-size: 8.5px; font-weight: 900; margin-top: 8px; padding: 5px 7px; text-transform: uppercase; }
        .notice { background: #fff7ed; border: 1px solid #fed7aa; color: #7c2d12; font-size: 9.5px; font-weight: 700; margin: 14px 0; padding: 8px 10px; }
        .section-grid { display: table; margin: 12px 0 14px; width: 100%; }
        .box { border: 1px solid #d1d5db; display: table-cell; padding: 10px; vertical-align: top; width: 50%; }
        .box + .box { border-left: 0; }
        .label { color: #374151; font-size: 8.6px; font-weight: 900; margin-bottom: 5px; text-transform: uppercase; }
        .strong { font-weight: 900; }
        table { border-collapse: collapse; width: 100%; }
        th { background: #111827; color: #fff; font-size: 8.8px; padding: 7px 6px; text-align: left; text-transform: uppercase; }
        td { border-bottom: 1px solid #e5e7eb; padding: 7px 6px; vertical-align: top; }
        .right { text-align: right; }
        .summary { display: table; margin-top: 14px; width: 100%; }
        .terms { display: table-cell; padding-right: 18px; vertical-align: top; width: 55%; }
        .totals { display: table-cell; vertical-align: top; width: 45%; }
        .terms-box, .words-box, .signature-box { border: 1px solid #d1d5db; margin-bottom: 9px; padding: 9px; }
        .totals table td { padding: 6px; }
        .grand td { background: #111827; color: #fff; font-size: 13px; font-weight: 900; }
        .bank-line { margin-top: 3px; }
        .signature { display: table; margin-top: 26px; width: 100%; }
        .signature-cell { display: table-cell; text-align: center; width: 50%; }
        .signature-line { border-top: 1px solid #111827; display: inline-block; padding-top: 5px; width: 72%; }
        .footer { border-top: 1px solid #d1d5db; color: #6b7280; font-size: 8.8px; margin-top: 14px; padding-top: 7px; text-align: center; }
    </style>
</head>
<body>
@php
    $currency = strtoupper((string) ($sale->currency ?: 'AOA'));
    $formatMoney = fn (float $amount): string => $currency . ' ' . number_format($amount, 2, ',', '.');
    $validUntil = $sale->due_date ?: optional($sale->created_at)->copy()?->addDays(15);
    $pending = max((float) $sale->total - (float) $sale->paid, 0);
    $taxRows = $sale->items->groupBy(fn ($item) => number_format((float) ($item->tax_rate ?? 0), 2, '.', ''));
@endphp
<div class="brand-line"></div>
<div class="top">
    <div class="company">
        @if($logoUrl)<div class="logo"><img src="{{ $logoUrl }}" alt="Logotipo"></div>@endif
        <div class="company-name">{{ $company['name'] ?: config('app.name', 'MARIA ERP') }}</div>
        @if(!empty($company['nif']))<div class="muted">NIF: {{ $company['nif'] }}</div>@endif
        @if(!empty($company['location']))<div class="muted">{{ $company['location'] }}</div>@endif
        @if(!empty($company['phone']))<div class="muted">Tel.: {{ $company['phone'] }}</div>@endif
        @if(!empty($company['email']))<div class="muted">{{ $company['email'] }}</div>@endif
    </div>
    <div class="doc-box">
        <div class="doc-title">Factura Proforma</div>
        <div class="doc-number">{{ $sale->invoice_number }}</div>
        <div class="muted">Data: {{ optional($sale->created_at)->format('d/m/Y H:i') }}</div>
        <div class="muted">Validade: {{ optional($validUntil)->format('d/m/Y') ?: '-' }}</div>
        <div class="stamp">Documento nao fiscal</div>
    </div>
</div>

<div class="notice">
    Esta factura proforma e uma proposta comercial para aprovacao do cliente. Nao substitui factura, factura-recibo, recibo nem documento fiscal AGT.
</div>

<div class="section-grid">
    <div class="box">
        <div class="label">Cliente</div>
        <div class="strong">{{ $sale->customer->name ?? 'Consumidor Final' }}</div>
        @if($sale->customer?->phone)<div class="muted">Telefone: {{ $sale->customer->phone }}</div>@endif
        @if($sale->customer?->email)<div class="muted">{{ $sale->customer->email }}</div>@endif
        @if($sale->customer?->address)<div class="muted">{{ $sale->customer->address }}</div>@endif
    </div>
    <div class="box">
        <div class="label">Condicoes comerciais</div>
        <div>Moeda: <span class="strong">{{ $currency }}</span></div>
        <div>Cambio: {{ number_format((float) ($sale->exchange_rate ?: 1), 6, ',', '.') }}</div>
        <div>Pagamento: {{ $sale->payment_condition ?: 'A combinar' }}</div>
        <div>Operador: {{ $sale->operator->name ?? 'Sistema' }}</div>
    </div>
</div>

<table>
    <thead>
        <tr>
            <th style="width:42%;">Descricao</th>
            <th class="right">Qtd</th>
            <th class="right">Preco unit.</th>
            <th class="right">IVA</th>
            <th class="right">Total</th>
        </tr>
    </thead>
    <tbody>
        @foreach($sale->items as $item)
            <tr>
                <td>{{ $item->product->name ?? 'Produto removido' }}</td>
                <td class="right">{{ number_format((float) $item->quantity, 2, ',', '.') }}</td>
                <td class="right">{{ $formatMoney((float) $item->unit_price) }}</td>
                <td class="right">{{ number_format((float) ($item->tax_rate ?? 0), 2, ',', '.') }}%</td>
                <td class="right">{{ $formatMoney((float) $item->subtotal) }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

<div class="summary">
    <div class="terms">
        <div class="terms-box">
            <div class="label">Resumo de IVA estimado</div>
            <table>
                <thead><tr><th>Taxa</th><th class="right">Incidencia</th><th class="right">Imposto</th></tr></thead>
                <tbody>
                    @foreach($taxRows as $rate => $items)
                        <tr><td>{{ number_format((float) $rate, 2, ',', '.') }}%</td><td class="right">{{ $formatMoney((float) $items->sum('net_subtotal')) }}</td><td class="right">{{ $formatMoney((float) $items->sum('tax_amount')) }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="terms-box">
            <div class="label">Dados bancarios</div>
            @if(!empty($company['bank_name']))<div class="bank-line">Banco: <strong>{{ $company['bank_name'] }}</strong></div>@endif
            @if(!empty($company['account_number']))<div class="bank-line">Conta: {{ $company['account_number'] }}</div>@endif
            @if(!empty($company['iban']))<div class="bank-line">IBAN: {{ $company['iban'] }}</div>@endif
            @if(!empty($company['swift']))<div class="bank-line">SWIFT: {{ $company['swift'] }}</div>@endif
            @if(empty($company['bank_name']) && empty($company['account_number']) && empty($company['iban']))<div class="muted">Dados bancarios nao configurados.</div>@endif
        </div>
    </div>
    <div class="totals">
        <table>
            <tr><td>Incidencia</td><td class="right">{{ $formatMoney((float) $sale->subtotal) }}</td></tr>
            <tr><td>IVA estimado</td><td class="right">{{ $formatMoney((float) $sale->tax) }}</td></tr>
            @if((float) ($sale->discount ?? 0) > 0)
                <tr><td>Desconto {{ number_format((float) ($sale->commercial_discount ?? 0), 2, ',', '.') }}%</td><td class="right">{{ $formatMoney((float) $sale->discount) }}</td></tr>
            @endif
            @if($pending > 0)<tr><td>Pendente</td><td class="right">{{ $formatMoney($pending) }}</td></tr>@endif
            <tr class="grand"><td>Total proforma</td><td class="right">{{ $formatMoney((float) $sale->total) }}</td></tr>
        </table>
        <div class="words-box">
            <div class="label">Observacoes</div>
            Valores sujeitos a confirmacao de disponibilidade de stock, validade comercial e aprovacao final antes da emissao da factura fiscal.
        </div>
    </div>
</div>

<div class="signature">
    <div class="signature-cell"><span class="signature-line">Emitido por</span></div>
    <div class="signature-cell"><span class="signature-line">Aprovacao do cliente</span></div>
</div>

<div class="footer">
    Proforma gerada pelo sistema. Documento sem valor fiscal ate emissao de factura/recibo valido.
</div>
</body>
</html>