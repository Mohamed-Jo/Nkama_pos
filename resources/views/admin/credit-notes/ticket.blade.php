<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NC {{ $creditNote->invoice_number }}</title>
    @php
        $printSettings = array_merge(\App\Services\BusinessSettings::PRINT_DEFAULTS, $printSettings ?? []);
    @endphp
    <style>
        @page { margin: {{ $printSettings['page_margin_top_mm'] }}mm {{ $printSettings['page_margin_right_mm'] }}mm {{ $printSettings['page_margin_bottom_mm'] }}mm {{ $printSettings['page_margin_left_mm'] }}mm; size: {{ $printSettings['paper_width_mm'] }}mm auto; }
        * { box-sizing: border-box; }
        body { background:#f3f4f6; color:#000; font-family:{!! $printSettings['font_family'] !!}; font-size:{{ $printSettings['base_font_size_px'] }}px; font-weight:600; margin:0; padding:12px; }
        .ticket { background:#fff; margin:0 auto; padding:{{ $printSettings['ticket_padding_mm'] }}mm; width:{{ $printSettings['ticket_width_mm'] }}mm; }
        .center { text-align:center; }
        .logo { margin:0 auto 6px; max-height:42px; max-width:120px; object-fit:contain; }
        .company-name { font-size:{{ $printSettings['company_font_size_px'] }}px; font-weight:900; text-transform:uppercase; }
        .muted { color:#000; font-weight:600; }
        .line { border-top:1px solid #000; margin:7px 0; }
        .row { display:table; font-size:{{ $printSettings['content_font_size_px'] }}px; table-layout:fixed; width:100%; }
        .row > span, .row > strong { display:table-cell; vertical-align:top; }
        .row > span:last-child, .row > strong:last-child { overflow-wrap:anywhere; text-align:right; }
        .item { margin-bottom:5px; }
        .item-line { display:table; font-size:{{ $printSettings['content_font_size_px'] }}px; table-layout:fixed; width:100%; }
        .item-product, .item-tax, .item-qty, .item-price, .item-subtotal { display:table-cell; vertical-align:top; }
        .item-product { font-weight:900; overflow-wrap:anywhere; width:{{ $printSettings['item_product_width_mm'] }}mm; }
        .item-tax, .item-qty, .item-price { font-size:{{ $printSettings['content_font_size_px'] }}px; font-weight:700; line-height:1.25; text-align:center; }
        .item-tax { width:{{ $printSettings['item_tax_width_mm'] }}mm; }
        .item-qty { width:{{ $printSettings['item_qty_width_mm'] }}mm; }
        .item-price { text-align:right; width:{{ $printSettings['item_price_width_mm'] }}mm; }
        .item-subtotal { font-weight:900; overflow-wrap:anywhere; text-align:right; white-space:nowrap; width:{{ $printSettings['item_subtotal_width_mm'] }}mm; }
        .totals { font-size:{{ $printSettings['content_font_size_px'] }}px; font-weight:700; }
        .summary-table { border-collapse:collapse; font-family:inherit; font-size:{{ $printSettings['content_font_size_px'] }}px; font-weight:700; table-layout:fixed; width:100%; }
        .summary-table td { padding:1px 0; vertical-align:top; }
        .summary-table td:first-child { overflow-wrap:anywhere; width:55%; }
        .summary-table td:last-child { overflow-wrap:anywhere; text-align:right; width:45%; }
        .summary-table .summary-final td { font-size:{{ $printSettings['total_font_size_px'] }}px; font-weight:900; padding-top:4px; }
        .tax-summary-title { font-size:{{ $printSettings['tax_summary_font_size_px'] }}px; font-weight:900; padding:4px 0 2px; text-align:center; text-transform:uppercase; }
        .tax-summary-table { border-collapse:collapse; font-family:inherit; font-size:{{ $printSettings['tax_summary_font_size_px'] }}px; font-weight:700; table-layout:fixed; width:100%; }
        .tax-summary-table td { padding:1px 0; vertical-align:top; }
        .tax-summary-table td:nth-child(1) { width:18%; }
        .tax-summary-table td:nth-child(2), .tax-summary-table td:nth-child(3) { overflow-wrap:anywhere; text-align:right; width:41%; }
        .tax-summary-head td { border-bottom:1px solid #000; font-size:{{ max((float) $printSettings['tax_summary_font_size_px'] - 1, 8) }}px; font-weight:900; padding-bottom:2px; }
        .total-final { font-size:{{ $printSettings['total_font_size_px'] }}px; font-weight:900; margin-top:4px; }
        .ticket > .center:not(:first-child) { font-size:{{ $printSettings['content_font_size_px'] }}px; }
        .actions { display:flex; gap:8px; justify-content:center; margin:12px auto; width:{{ $printSettings['ticket_width_mm'] }}mm; }
        .actions button, .actions a { background:#111827; border:none; border-radius:6px; color:#fff; cursor:pointer; font-family:system-ui,sans-serif; font-size:12px; font-weight:700; padding:8px 10px; text-decoration:none; }
        @media print { body { background:#fff; padding:0; } .ticket { margin:0 auto; width:{{ $printSettings['ticket_width_mm'] }}mm; } .actions { display:none; } }
        body.direct-print { background:#fff; padding:0; }
        body.direct-print .ticket { margin:0 auto; width:{{ $printSettings['ticket_width_mm'] }}mm; }
        body.direct-print .actions { display:none; }
    </style>
</head>
<body class="{{ !empty($directPrint) ? 'direct-print' : '' }}">
    @php
        $viewTicket = \App\Services\ModuleSettings::enabled('view_ticket');
    @endphp
    @if(empty($directPrint))
        <div class="actions">
            <button type="button" onclick="window.print()">Imprimir</button>
            @if($viewTicket)
                <a href="{{ route('admin.sales.ticket', $creditNote->originalSale) }}">Documento original</a>
            @else
                <a href="{{ route('admin.print.sales', $creditNote->originalSale) }}" data-direct-print-url="{{ route('admin.print.sales', $creditNote->originalSale) }}">Imprimir original</a>
            @endif
        </div>
    @endif

    <main class="ticket">
        <div class="center">
            <div class="company-name">{{ $company['name'] ?: config('app.name', 'Nkama ERP') }}</div>
            @if(!empty($company['location']))<div class="muted">{{ $company['location'] }}</div>@endif
            @if(!empty($company['nif']))<div class="muted">NIF: {{ $company['nif'] }}</div>@endif
        </div>

        <div class="line"></div>
        <div class="center"><strong>NOTA DE CREDITO</strong></div>
        <div class="row"><span>NC</span><strong>{{ $creditNote->invoice_number }}</strong></div>
        <div class="row"><span>Ref.</span><span>{{ $creditNote->originalSale->invoice_number ?? '-' }}</span></div>
        <div class="row"><span>Data</span><span>{{ optional($creditNote->created_at)->format('d/m/Y H:i') }}</span></div>
        <div class="row"><span>Cliente</span><span>{{ $creditNote->customer->name ?? 'Consumidor Final' }}</span></div>
        @if($creditNote->reason)
            <div class="row"><span>Motivo</span><span>{{ $creditNote->reason }}</span></div>
        @endif

        <div class="line"></div>

        @foreach($creditNote->items as $item)
            @php
                $taxRateLabel = rtrim(rtrim(number_format((float) ($item->tax_rate ?? 0), 2, ',', '.'), '0'), ',');
            @endphp
            <div class="item">
                <div class="item-line">
                    <span class="item-product">{{ $item->product->name ?? 'Produto removido' }}</span>
                    <span class="item-tax">{{ $taxRateLabel }}%</span>
                    <span class="item-qty">{{ number_format($item->quantity, 2, ',', '.') }}</span>
                    <span class="item-price">{{ number_format($item->unit_price, 2, ',', '.') }}</span>
                    <strong class="item-subtotal">{{ number_format($item->subtotal, 2, ',', '.') }}</strong>
                </div>
            </div>
        @endforeach

        <div class="line"></div>
        @php
            $taxBreakdown = [];
            foreach ($creditNote->items as $item) {
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
                    <td>{{ number_format($creditNote->subtotal, 2, ',', '.') }}</td>
                </tr>
                <tr class="summary-final">
                    <td>TOTAL NC</td>
                    <td>AOA {{ number_format($creditNote->total, 2, ',', '.') }}</td>
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

        @php
            $refundTotal = abs((float) $creditNote->payments->sum('amount'));
        @endphp
        @if($refundTotal > 0)
            <div class="line"></div>
            <table class="summary-table">
                <tr>
                    <td>Reembolso</td>
                    <td>{{ number_format($refundTotal, 2, ',', '.') }}</td>
                </tr>
                <tr>
                    <td>Metodo</td>
                    <td>{{ strtoupper($creditNote->payments->first()?->method ?? '-') }}</td>
                </tr>
            </table>
        @endif

        <div class="line"></div>
        <div class="center">
            <strong>Documento retificativo</strong>
            <div class="muted">Processado por programa informatico</div>
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
