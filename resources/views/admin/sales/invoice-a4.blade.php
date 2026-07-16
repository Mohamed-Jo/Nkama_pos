<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Fatura {{ $sale->invoice_number }}</title>
    <style>
        @page { margin: 22px 26px; }
        body { color: #111827; font-family: DejaVu Sans, sans-serif; font-size: 10.2px; }
        .top { border-bottom: 1px solid #d1d5db; display: table; margin-bottom: 10px; padding-bottom: 10px; width: 100%; }
        .company-side { display: table-cell; vertical-align: top; width: 52%; }
        .customer-side { display: table-cell; text-align: right; vertical-align: top; width: 48%; }
        .logo { margin-bottom: 6px; }
        .logo img { max-height: 68px; max-width: 195px; object-fit: contain; }
        .company-name { font-size: 15px; font-weight: 800; text-transform: uppercase; }
        .muted { color: #4b5563; }
        .label { color: #374151; font-size: 9px; font-weight: 800; margin-bottom: 4px; text-transform: uppercase; }
        .document-head { border-bottom: 2px solid #111827; margin-bottom: 14px; padding-bottom: 10px; text-align: right; }
        .doc-title { font-size: 16px; font-weight: 900; letter-spacing: .03em; text-transform: uppercase; }
        .doc-number { font-size: 13px; font-weight: 800; margin-top: 4px; }
        .doc-meta { color: #4b5563; margin-top: 2px; }
        table { border-collapse: collapse; width: 100%; }
        th { background: #111827; color: #fff; font-size: 8.8px; padding: 7px 6px; text-align: left; text-transform: uppercase; }
        td { border-bottom: 1px solid #e5e7eb; padding: 7px 6px; vertical-align: top; }
        .right { text-align: right; }
        .summary-wrap { display: table; margin-top: 14px; width: 100%; }
        .left-summary { display: table-cell; padding-right: 18px; vertical-align: top; width: 55%; }
        .totals-box { display: table-cell; vertical-align: top; width: 45%; }
        .tax-summary th { background: #374151; }
        .tax-summary td { font-size: 10px; padding: 5px 6px; }
        .bank-box, .info-box, .amount-words { border: 1px solid #d1d5db; margin-top: 10px; padding: 8px; }
        .bank-line, .info-line { margin-top: 3px; }
        .totals { margin-left: auto; width: 100%; }
        .totals td { border-bottom: 1px solid #e5e7eb; padding: 6px; }
        .grand td { background: #111827; color: #fff; font-size: 13px; font-weight: 900; }
        .footer { border-top: 1px solid #d1d5db; color: #6b7280; font-size: 9px; margin-top: 18px; padding-top: 7px; text-align: center; }
    </style>
</head>
<body>
    @php
        $docLabel = ($sale->document_type_code ?? 'FR') === 'FT' ? 'Factura' : 'Factura Recibo';
        $pending = max((float) $sale->total - (float) $sale->paid, 0);
        $currency = strtoupper((string) ($sale->currency ?: 'AOA'));
        $formatMoney = fn (float $amount): string => $currency . ' ' . number_format($amount, 2, ',', '.');
        $taxRows = $sale->items->groupBy(function ($item) {
            return number_format((float) ($item->tax_rate ?? 0), 2, '.', '');
        });

        $numberToWords = function (int $number) use (&$numberToWords): string {
            $units = ['', 'um', 'dois', 'tres', 'quatro', 'cinco', 'seis', 'sete', 'oito', 'nove', 'dez', 'onze', 'doze', 'treze', 'catorze', 'quinze', 'dezasseis', 'dezassete', 'dezoito', 'dezanove'];
            $tens = ['', '', 'vinte', 'trinta', 'quarenta', 'cinquenta', 'sessenta', 'setenta', 'oitenta', 'noventa'];
            $hundreds = ['', 'cento', 'duzentos', 'trezentos', 'quatrocentos', 'quinhentos', 'seiscentos', 'setecentos', 'oitocentos', 'novecentos'];

            if ($number === 0) return 'zero';
            if ($number === 100) return 'cem';
            if ($number < 20) return $units[$number];
            if ($number < 100) {
                $ten = intdiv($number, 10);
                $rest = $number % 10;
                return $tens[$ten] . ($rest ? ' e ' . $units[$rest] : '');
            }
            if ($number < 1000) {
                $hundred = intdiv($number, 100);
                $rest = $number % 100;
                return $hundreds[$hundred] . ($rest ? ' e ' . $numberToWords($rest) : '');
            }
            if ($number < 1000000) {
                $thousands = intdiv($number, 1000);
                $rest = $number % 1000;
                $prefix = $thousands === 1 ? 'mil' : $numberToWords($thousands) . ' mil';
                return $prefix . ($rest ? ' e ' . $numberToWords($rest) : '');
            }

            $millions = intdiv($number, 1000000);
            $rest = $number % 1000000;
            $prefix = $millions === 1 ? 'um milhao' : $numberToWords($millions) . ' milhoes';
            return $prefix . ($rest ? ' e ' . $numberToWords($rest) : '');
        };

        $amountToWords = function (float $amount) use ($numberToWords, $currency): string {
            $integer = (int) floor($amount);
            $cents = (int) round(($amount - $integer) * 100);
            $mainCurrency = $currency === 'AOA' ? ($integer === 1 ? 'kwanza' : 'kwanzas') : $currency;
            $words = ucfirst($numberToWords($integer)) . ' ' . $mainCurrency;
            if ($cents > 0) {
                $words .= ' e ' . $numberToWords($cents) . ' ' . ($cents === 1 ? 'centimo' : 'centimos');
            }
            return $words;
        };
    @endphp

    <div class="top">
        <div class="company-side">
            @if($logoUrl)
                <div class="logo"><img src="{{ $logoUrl }}" alt="Logotipo"></div>
            @endif
            <div class="company-name">{{ $company['name'] ?: config('app.name', 'Nkama ERP') }}</div>
            @if(!empty($company['nif']))<div class="muted">NIF: {{ $company['nif'] }}</div>@endif
            @if(!empty($company['location']))<div class="muted">{{ $company['location'] }}</div>@endif
        </div>
        <div class="customer-side">
            <div class="label">Cliente</div>
            <strong>{{ $sale->customer->name ?? 'Consumidor Final' }}</strong>
            @if($sale->customer?->phone)<div class="muted">Telefone: {{ $sale->customer->phone }}</div>@endif
            @if($sale->customer?->email)<div class="muted">{{ $sale->customer->email }}</div>@endif
            @if($sale->customer?->address)<div class="muted">{{ $sale->customer->address }}</div>@endif
        </div>
    </div>

    <div class="document-head">
        <div class="doc-title">{{ $docLabel }}</div>
        <div class="doc-number">{{ $sale->invoice_number }}</div>
        <div class="doc-meta">Data: {{ optional($sale->created_at)->format('d/m/Y H:i') }} &nbsp; | &nbsp; Tipo: {{ $sale->document_type_code ?? 'FR' }}</div>
        <div class="doc-meta">Moeda: {{ $currency }} &nbsp; | &nbsp; Cambio: {{ number_format((float) ($sale->exchange_rate ?: 1), 6, ',', '.') }}</div>
        @if(!empty($sale->payment_condition) || $sale->due_date)
            <div class="doc-meta">Condicao: {{ $sale->payment_condition ?: '-' }} @if($sale->due_date)&nbsp; | &nbsp; Vencimento: {{ optional($sale->due_date)->format('d/m/Y') }}@endif</div>
        @endif
    </div>

    <table>
        <thead>
            <tr>
                <th>Artigo</th>
                <th class="right">Qtd</th>
                <th class="right">Preco</th>
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

    <div class="summary-wrap">
        <div class="left-summary">
            <table class="tax-summary">
                <thead><tr><th>IVA</th><th class="right">Incidencia</th><th class="right">Imposto</th></tr></thead>
                <tbody>
                    @foreach($taxRows as $rate => $items)
                        <tr>
                            <td>{{ number_format((float) $rate, 2, ',', '.') }}%</td>
                            <td class="right">{{ $formatMoney((float) $items->sum('net_subtotal')) }}</td>
                            <td class="right">{{ $formatMoney((float) $items->sum('tax_amount')) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            @if(!empty($sale->exemption_reason))
                <div class="info-box">
                    <div class="label">Motivo de isencao</div>
                    <div class="info-line">{{ $sale->exemption_reason }}</div>
                </div>
            @endif

            @if(!empty($company['iban']) || !empty($company['account_number']) || !empty($company['bank_name']))
                <div class="bank-box">
                    <div class="label">Dados bancarios</div>
                    @if(!empty($company['bank_name']))<div class="bank-line">Banco: <strong>{{ $company['bank_name'] }}</strong></div>@endif
                    @if(!empty($company['account_number']))<div class="bank-line">No. conta: {{ $company['account_number'] }}</div>@endif
                    @if(!empty($company['iban']))<div class="bank-line">IBAN: {{ $company['iban'] }}</div>@endif
                    @if(!empty($company['swift']))<div class="bank-line">SWIFT: {{ $company['swift'] }}</div>@endif
                </div>
            @endif
        </div>
        <div class="totals-box">
            <table class="totals">
                <tr><td>Subtotal</td><td class="right">{{ $formatMoney((float) $sale->subtotal) }}</td></tr>
                <tr><td>IVA</td><td class="right">{{ $formatMoney((float) $sale->tax) }}</td></tr>
                @if((float) ($sale->discount ?? 0) > 0)
                    <tr><td>Desconto comercial {{ number_format((float) ($sale->commercial_discount ?? 0), 2, ',', '.') }}%</td><td class="right">{{ $formatMoney((float) $sale->discount) }}</td></tr>
                @endif
                @if((float) $sale->paid > 0)
                    <tr><td>Pago</td><td class="right">{{ $formatMoney((float) $sale->paid) }}</td></tr>
                @endif
                @if((float) $sale->change > 0)
                    <tr><td>Troco</td><td class="right">{{ $formatMoney((float) $sale->change) }}</td></tr>
                @endif
                @if($pending > 0)
                    <tr><td>Pendente</td><td class="right">{{ $formatMoney($pending) }}</td></tr>
                @endif
                <tr class="grand"><td>Total</td><td class="right">{{ $formatMoney((float) $sale->total) }}</td></tr>
            </table>
            <div class="amount-words">
                <div class="label">Valor por extenso</div>
                {{ $amountToWords((float) $sale->total) }}
            </div>
        </div>
    </div>

    <div class="footer">
        Obrigado pela preferencia.
    </div>
</body>
</html>