<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket {{ $sale->invoice_number }}</title>
    @php
        $printSettings = array_merge(\App\Services\BusinessSettings::PRINT_DEFAULTS, $printSettings ?? []);
        $itemFontSize = max((float) $printSettings['content_font_size_px'] - 1, 8);
        $itemProductWidth = max((float) $printSettings['item_product_width_mm'] - 2, 12);
        $itemTaxWidth = max((float) $printSettings['item_tax_width_mm'] - 1, 3);
        $itemQtyWidth = max((float) $printSettings['item_qty_width_mm'] - 1, 3);
        $itemPriceWidth = max((float) $printSettings['item_price_width_mm'] - 2, 8);
        $itemSubtotalWidth = max((float) $printSettings['item_subtotal_width_mm'] - 4, 10);
    @endphp
    <style>
        @page {
            margin: {{ $printSettings['page_margin_top_mm'] }}mm {{ $printSettings['page_margin_right_mm'] }}mm {{ $printSettings['page_margin_bottom_mm'] }}mm {{ $printSettings['page_margin_left_mm'] }}mm;
            size: {{ $printSettings['paper_width_mm'] }}mm auto;
        }

        * { box-sizing: border-box; }

        body {
            background: #f3f4f6;
            color: #000;
            font-family: {!! $printSettings['font_family'] !!};
            font-size: {{ $printSettings['base_font_size_px'] }}px;
            font-weight: 600;
            margin: 0;
            padding: 12px;
        }

        .ticket {
            background: #fff;
            margin: 0 auto;
            padding: {{ $printSettings['ticket_padding_mm'] }}mm;
            width: {{ $printSettings['ticket_width_mm'] }}mm;
        }

        .center { text-align: center; }

        .logo {
            margin: 0 auto 6px;
            max-height: 42px;
            max-width: 120px;
            object-fit: contain;
        }

        .company-name {
            font-size: {{ $printSettings['company_font_size_px'] }}px;
            font-weight: 900;
            text-transform: uppercase;
        }

        .muted {
            color: #000;
            font-weight: 600;
        }

        .line {
            border-top: 1px solid #000;
            margin: 7px 0;
        }

        .row {
            display: table;
            font-size: {{ $printSettings['content_font_size_px'] }}px;
            table-layout: fixed;
            width: 100%;
        }

        .row > span,
        .row > strong {
            display: table-cell;
            vertical-align: top;
        }

        .row > span:last-child,
        .row > strong:last-child {
            overflow-wrap: anywhere;
            text-align: right;
        }

        .item { margin-bottom: 5px; }

        .item-line {
            display: table;
            font-size: {{ $itemFontSize }}px;
            table-layout: fixed;
            margin-right: 7%;
            width: 93%;
        }

        .item-product,
        .item-tax,
        .item-qty,
        .item-price,
        .item-subtotal {
            display: table-cell;
            vertical-align: top;
        }

        .item-product {
            font-weight: 900;
            overflow-wrap: anywhere;
            width: {{ $itemProductWidth }}mm;
        }

        .item-tax,
        .item-qty,
        .item-price {
            font-size: {{ $itemFontSize }}px;
            font-weight: 700;
            line-height: 1.25;
            text-align: center;
        }

        .item-tax {
            width: {{ $itemTaxWidth }}mm;
        }

        .item-qty {
            width: {{ $itemQtyWidth }}mm;
        }

        .item-price {
            text-align: right;
            overflow-wrap: anywhere;
            word-break: break-word;
            width: {{ $itemPriceWidth }}mm;
        }

        .item-subtotal {
            font-weight: 900;
            overflow-wrap: anywhere;
            text-align: right;
            word-break: break-word;
            width: {{ $itemSubtotalWidth }}mm;
        }

        .totals {
            font-size: {{ $printSettings['content_font_size_px'] }}px;
            font-weight: 700;
        }

        .summary-table {
            border-collapse: collapse;
            font-family: inherit;
            font-size: {{ $printSettings['content_font_size_px'] }}px;
            font-weight: 700;
            table-layout: fixed;
            width: 100%;
        }

        .summary-table td {
            padding: 1px 0;
            vertical-align: top;
        }

        .summary-table td:first-child {
            overflow-wrap: anywhere;
            width: 55%;
        }

        .summary-table td:last-child {
            overflow-wrap: anywhere;
            text-align: right;
            width: 45%;
        }

        .summary-table .summary-final td {
            font-size: {{ $printSettings['total_font_size_px'] }}px;
            font-weight: 900;
            padding-top: 4px;
        }

        .tax-summary-title {
            font-size: {{ $printSettings['tax_summary_font_size_px'] }}px;
            font-weight: 900;
            padding: 4px 0 2px;
            text-align: center;
            text-transform: uppercase;
        }

        .tax-summary-table {
            border-collapse: collapse;
            font-family: inherit;
            font-size: {{ $printSettings['tax_summary_font_size_px'] }}px;
            font-weight: 700;
            table-layout: fixed;
            width: 100%;
        }

        .tax-summary-table td {
            padding: 1px 0;
            vertical-align: top;
        }

        .tax-summary-table td:nth-child(1) {
            width: 18%;
        }

        .tax-summary-table td:nth-child(2),
        .tax-summary-table td:nth-child(3) {
            overflow-wrap: anywhere;
            text-align: right;
            width: 41%;
        }

        .tax-summary-head td {
            border-bottom: 1px solid #000;
            font-size: {{ max((float) $printSettings['tax_summary_font_size_px'] - 1, 8) }}px;
            font-weight: 900;
            padding-bottom: 2px;
        }

        .total-final {
            font-size: {{ $printSettings['total_font_size_px'] }}px;
            font-weight: 900;
            margin-top: 4px;
        }

        .agt-qr {
            font-size: {{ max((float) $printSettings['content_font_size_px'] - 1, 8) }}px;
            font-weight: 800;
            text-align: center;
            overflow-wrap: anywhere;
        }

        .agt-qr svg {
            display: block;
            height: 88px;
            margin: 4px auto;
            width: 88px;
        }

        .agt-qr-link {
            font-size: {{ max((float) $printSettings['content_font_size_px'] - 3, 7) }}px;
            font-weight: 600;
            line-height: 1.2;
        }

        .ticket > .center:not(:first-child) {
            font-size: {{ $printSettings['content_font_size_px'] }}px;
        }

        .actions {
            display: flex;
            gap: 8px;
            justify-content: center;
            margin: 12px auto;
            width: {{ $printSettings['ticket_width_mm'] }}mm;
        }

        .actions button,
        .actions a {
            background: #111827;
            border: none;
            border-radius: 6px;
            color: #fff;
            cursor: pointer;
            font-family: system-ui, sans-serif;
            font-size: 12px;
            font-weight: 700;
            padding: 8px 10px;
            text-decoration: none;
        }

        @media print {
            body {
                background: #fff;
                padding: 0;
            }

            .ticket {
                margin: 0 auto;
                width: {{ $printSettings['ticket_width_mm'] }}mm;
            }

            .actions { display: none; }
        }

        body.direct-print {
            background: #fff;
            padding: 0;
        }

        body.direct-print .ticket {
            margin: 0 auto;
            width: {{ $printSettings['ticket_width_mm'] }}mm;
        }

        body.direct-print .actions {
            display: none;
        }
    </style>
</head>
<body class="{{ !empty($directPrint) ? 'direct-print' : '' }}">
    @if(empty($directPrint))
        <div class="actions">
            <button type="button" onclick="window.print()">Imprimir</button>
        </div>
    @endif

    <main class="ticket">
        <div class="center">
            <div class="company-name">{{ $company['name'] ?: config('app.name', 'Nkama ERP') }}</div>

            @if(!empty($company['location']))
                <div class="muted">{{ $company['location'] }}</div>
            @endif

            @if(!empty($company['nif']))
                <div class="muted">NIF: {{ $company['nif'] }}</div>
            @endif

            @if(!empty($company['iban']))
                <div class="muted">IBAN: {{ $company['iban'] }}</div>
            @endif
        </div>

        <div class="line"></div>

        <div class="row">
            <span>Ticket</span>
            <strong>{{ $sale->invoice_number }}</strong>
        </div>
        <div class="row">
            <span>Data</span>
            <span>{{ optional($sale->created_at)->format('d/m/Y H:i') }}</span>
        </div>
        <div class="row">
            <span>Operador</span>
            <span>{{ $sale->operator->name ?? session('operator_name', 'Operador') }}</span>
        </div>
        <div class="row">
            <span>Cliente</span>
            <span>{{ $sale->customer->name ?? 'Consumidor Final' }}</span>
        </div>
        @if($sale->customerCard || $sale->customer?->card)
            <div class="row">
                <span>Cartao Cliente</span>
                <span>{{ ($sale->customerCard ?? $sale->customer?->card)?->card_number }}</span>
            </div>
        @endif

        @php
            $agtQrUrl = \App\Services\BusinessSettings::agtDocumentUrl($company, $sale->invoice_number);
            $agtQrSvg = \App\Services\BusinessSettings::agtQrSvg($company, $sale->invoice_number, 88);
        @endphp
        @if($agtQrSvg)
            <div class="line"></div>
            <div class="agt-qr">
                <div>Consulta AGT</div>
                {!! $agtQrSvg !!}
                <div class="agt-qr-link">{{ $agtQrUrl }}</div>
            </div>
        @endif

        <div class="line"></div>
        @foreach($sale->items as $item)
            @php
                $taxRateLabel = rtrim(rtrim(number_format((float) ($item->tax_rate ?? 0), 2, ',', '.'), '0'), ',');
            @endphp
            <div class="item">
                <div class="item-line">
                    <span class="item-product">{{ $item->product->name ?? 'Produto removido' }}</span>
                    <span class="item-tax">{{ $taxRateLabel }}%</span>
                    <span class="item-qty">{{ number_format($item->quantity, 0) }}</span>
                    <span class="item-price">{{ number_format($item->unit_price, 2, ',', '.') }}</span>
                    <strong class="item-subtotal">{{ number_format($item->subtotal, 2, ',', '.') }}</strong>
                </div>
            </div>
        @endforeach

        <div class="line"></div>

        @php
            $taxBreakdown = [];
            foreach ($sale->items as $item) {
                $rate = round((float) ($item->tax_rate ?? 0), 2);
                $key = number_format($rate, 2, '.', '');
                $taxBreakdown[$key] ??= ['rate' => $rate, 'incidence' => 0.0, 'tax' => 0.0];
                $itemTax = (float) ($item->tax_amount ?? 0);
                $itemIncidence = $item->net_subtotal !== null
                    ? (float) $item->net_subtotal
                    : max((float) $item->subtotal - $itemTax, 0);
                $taxBreakdown[$key]['incidence'] += $itemIncidence;
                $taxBreakdown[$key]['tax'] += $itemTax;
            }
            ksort($taxBreakdown, SORT_NUMERIC);
        @endphp

        <div class="totals">
            <table class="summary-table">
                <tr>
                    <td>Subtotal</td>
                    <td>{{ number_format($sale->subtotal, 2, ',', '.') }}</td>
                </tr>
                <tr class="summary-final">
                    <td>TOTAL</td>
                    <td>AOA {{ number_format($sale->total, 2, ',', '.') }}</td>
                </tr>
            </table>

            @if(!empty($taxBreakdown))
                <div class="tax-summary-title">Resumo IVA</div>
                <table class="tax-summary-table">
                    <tr class="tax-summary-head">
                        <td>Taxa</td>
                        <td>Incid.</td>
                        <td>IVA</td>
                    </tr>
                    @foreach($taxBreakdown as $taxRow)
                        @php
                            $rateLabel = rtrim(rtrim(number_format((float) $taxRow['rate'], 2, ',', '.'), '0'), ',');
                        @endphp
                        <tr>
                            <td>{{ $rateLabel }}%</td>
                            <td>{{ number_format($taxRow['incidence'], 2, ',', '.') }}</td>
                            <td>{{ number_format($taxRow['tax'], 2, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </table>
            @endif
        </div>

        <div class="line"></div>

        @php
            $pendingAmount = max((float) $sale->total - (float) ($sale->paid ?? 0), 0);
            $paymentLabels = [
                'cash' => 'Dinheiro',
                'card' => 'Multicaixa',
                'multi' => 'Multicaixa',
                'transf' => 'Transferencia',
                'transfer' => 'Transferencia',
                'credit' => 'Conta corrente',
                'mixed' => 'Pagamento Misto',
                'mixed_credit' => 'Misto + Conta',
                'customer_card' => 'Cartao Cliente/Fidelidade',
            ];
            $paymentLines = $sale->payments
                ->filter(fn ($payment) => (float) $payment->amount > 0)
                ->groupBy(fn ($payment) => $payment->method ?? 'unknown')
                ->map(fn ($payments, $method) => [
                    'method' => $method,
                    'label' => $paymentLabels[$method] ?? strtoupper((string) $method),
                    'amount' => (float) $payments->sum('amount'),
                ])
                ->values();
            $storedPaymentLabel = $paymentLabels[$sale->payment_method] ?? strtoupper($sale->payment_method ?? '-');
            $paymentMethodLabel = in_array($sale->payment_method, ['mixed', 'mixed_credit'], true) || $paymentLines->count() > 1
                ? $storedPaymentLabel
                : ($paymentLines->first()['label'] ?? $storedPaymentLabel);
        @endphp
        <table class="summary-table">
            <tr>
                <td>Pago</td>
                <td>{{ number_format($sale->paid ?? $sale->total, 2, ',', '.') }}</td>
            </tr>
            @if($pendingAmount > 0)
                <tr>
                    <td>Pendente</td>
                    <td>{{ number_format($pendingAmount, 2, ',', '.') }}</td>
                </tr>
            @endif
            <tr>
                <td>Troco</td>
                <td>{{ number_format($sale->change ?? 0, 2, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Metodo</td>
                <td>{{ $paymentMethodLabel }}</td>
            </tr>
            @foreach($paymentLines as $paymentLine)
                @if($paymentLines->count() > 1)
                    <tr>
                        <td>{{ $paymentLine['label'] }}</td>
                        <td>{{ number_format($paymentLine['amount'], 2, ',', '.') }}</td>
                    </tr>
                @endif
            @endforeach
        </table>
        @php
            $loyaltyCard = $sale->customerCard ?? $sale->customer?->card;
            $loyaltyEarn = $sale->pointTransactions->where('type', 'earn')->first();
            $loyaltyRedeem = $sale->pointTransactions->where('type', 'redeem')->first();
            $loyaltyBalanceUsed = $loyaltyCard?->balanceTransactions?->where('sale_id', $sale->id)->where('type', 'purchase')->first();
        @endphp
        @if($loyaltyCard || $loyaltyEarn)
            <div class="line"></div>
            <table>
                <tr>
                    <td colspan="2"><strong>Cliente Fidelizado</strong></td>
                </tr>
                @if($loyaltyCard)
                    <tr>
                        <td>Pontos atuais</td>
                        <td>{{ number_format($loyaltyCard->points, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td>Nivel</td>
                        <td>{{ $loyaltyCard->level }}</td>
                    </tr>
                    <tr>
                        <td>Proximo nivel</td>
                        <td>{{ $loyaltyCard->next_level ?? 'Nivel maximo' }}</td>
                    </tr>
                @endif
                @if($loyaltyRedeem)
                    <tr>
                        <td>Bonus usado</td>
                        <td>{{ number_format(abs($loyaltyRedeem->points), 0, ',', '.') }} pts</td>
                    </tr>
                @endif
                @if($loyaltyBalanceUsed)
                    <tr>
                        <td>Saldo usado</td>
                        <td>{{ number_format(abs($loyaltyBalanceUsed->amount), 2, ',', '.') }}</td>
                    </tr>
                @endif                @if($loyaltyEarn)
                    <tr>
                        <td>Ganhos nesta compra</td>
                        <td>+{{ number_format($loyaltyEarn->points, 0, ',', '.') }}</td>
                    </tr>
                @endif
            </table>
        @endif
        <div class="line"></div>

        <div class="center">
            <strong>Obrigado pela preferencia</strong>
            <div class="muted">Documento processado por programa informatico</div>
        </div>
    </main>

    @if(empty($directPrint) && request()->boolean('print'))
        <script>
            let closeAfterPrintTimer = null;

            function closePrintWindow() {
                clearTimeout(closeAfterPrintTimer);
                window.close();
            }

            window.addEventListener('afterprint', closePrintWindow);
            window.addEventListener('load', () => {
                closeAfterPrintTimer = setTimeout(closePrintWindow, 3000);
                window.print();
            });
        </script>
    @endif
</body>
</html>
