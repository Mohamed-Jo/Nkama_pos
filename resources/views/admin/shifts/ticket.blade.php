<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Fecho de Caixa #{{ $shift->id }}</title>
    @php
        $printSettings = array_merge(\App\Services\BusinessSettings::PRINT_DEFAULTS, $printSettings ?? []);
        $money = fn ($value) => number_format((float) $value, 2, ',', '.');
        $cashTotal = (float) ($shift->cash_sales_total ?? 0);
        $cardTotal = (float) ($shift->card_sales_total ?? 0);
        $multiTotal = (float) ($shift->multi_sales_total ?? 0);
        $transfTotal = (float) ($shift->transf_sales_total ?? 0);
        $digitalTotal = $cardTotal + $multiTotal + $transfTotal;
        $totalSales = (float) ($shift->total_sales ?? ($cashTotal + $digitalTotal));
        $difference = (float) ($shift->difference ?? 0);
    @endphp
    <style>
        @page {
            margin: {{ $printSettings['page_margin_top_mm'] }}mm {{ $printSettings['page_margin_right_mm'] }}mm {{ $printSettings['page_margin_bottom_mm'] }}mm {{ $printSettings['page_margin_left_mm'] }}mm;
            size: {{ $printSettings['paper_width_mm'] }}mm auto;
        }

        * { box-sizing: border-box; }

        body {
            background: #fff;
            color: #000;
            font-family: {!! $printSettings['font_family'] !!};
            font-size: {{ $printSettings['base_font_size_px'] }}px;
            font-weight: 700;
            margin: 0;
            padding: 0;
        }

        .ticket {
            margin: 0 auto;
            padding: {{ $printSettings['ticket_padding_mm'] }}mm;
            width: {{ $printSettings['ticket_width_mm'] }}mm;
        }

        .center { text-align: center; }
        .logo { margin: 0 auto 5px; max-height: 42px; max-width: 120px; object-fit: contain; }
        .company-name { font-size: {{ $printSettings['company_font_size_px'] }}px; font-weight: 900; text-transform: uppercase; }
        .title { font-size: {{ $printSettings['total_font_size_px'] }}px; font-weight: 900; margin-top: 5px; text-transform: uppercase; }
        .line { border-top: 1px solid #000; margin: 7px 0; }
        .row { display: table; font-size: {{ $printSettings['content_font_size_px'] }}px; table-layout: fixed; width: 100%; }
        .row span, .row strong { display: table-cell; padding: 1px 0; vertical-align: top; }
        .row span:first-child { overflow-wrap: anywhere; width: 55%; }
        .row strong:last-child { overflow-wrap: anywhere; text-align: right; width: 45%; }
        .section-title { font-size: {{ $printSettings['content_font_size_px'] }}px; font-weight: 900; margin: 6px 0 3px; text-align: center; text-transform: uppercase; }
        .total-row span, .total-row strong { font-size: {{ $printSettings['total_font_size_px'] }}px; font-weight: 900; padding-top: 4px; }
        .small { font-size: {{ max((float) $printSettings['content_font_size_px'] - 1, 8) }}px; line-height: 1.25; }
        .note { border-top: 1px dashed #000; margin-top: 6px; padding-top: 5px; overflow-wrap: anywhere; }

        @media print {
            body { padding: 0; }
            .ticket { margin: 0; }
        }
    </style>
</head>
<body>
    <div class="ticket">
        <div class="center">
            <div class="company-name">{{ $company['name'] ?: config('app.name', 'MARIA ERP') }}</div>
            @if(!empty($company['location']))
                <div class="small">{{ $company['location'] }}</div>
            @endif
            @if(!empty($company['nif']))
                <div class="small">NIF: {{ $company['nif'] }}</div>
            @endif
            <div class="title">Resumo Fecho de Caixa</div>
            <div class="small">Caixa #{{ $shift->id }}</div>
        </div>

        <div class="line"></div>

        <div class="row"><span>Operador</span><strong>{{ $shift->operator->name ?? ('#' . $shift->operator_id) }}</strong></div>
        <div class="row"><span>Abertura</span><strong>{{ $shift->opened_at ? \Carbon\Carbon::parse($shift->opened_at)->format('d/m/Y H:i') : '-' }}</strong></div>
        <div class="row"><span>Fecho</span><strong>{{ $shift->closed_at ? \Carbon\Carbon::parse($shift->closed_at)->format('d/m/Y H:i') : '-' }}</strong></div>
        <div class="row"><span>N. vendas</span><strong>{{ (int) ($shift->sales_count ?? 0) }}</strong></div>

        <div class="line"></div>
        <div class="section-title">Venda do Caixa</div>

        <div class="row"><span>Dinheiro</span><strong>{{ $money($cashTotal) }} Kz</strong></div>
        <div class="row"><span>Multicaixa</span><strong>{{ $money($cardTotal + $multiTotal) }} Kz</strong></div>
        <div class="row"><span>Transferencia</span><strong>{{ $money($transfTotal) }} Kz</strong></div>
        <div class="row total-row"><span>Total vendido</span><strong>{{ $money($totalSales) }} Kz</strong></div>

        <div class="line"></div>
        <div class="section-title">Auditoria do Dinheiro</div>

        <div class="row"><span>Fundo inicial</span><strong>{{ $money($shift->opening_cash) }} Kz</strong></div>
        <div class="row"><span>Esperado</span><strong>{{ $money($shift->expected_cash) }} Kz</strong></div>
        <div class="row"><span>Contado</span><strong>{{ $money($shift->closing_cash) }} Kz</strong></div>
        <div class="row total-row"><span>Diferenca</span><strong>{{ $money($difference) }} Kz</strong></div>

        @if($digitalTotal != 0)
            <div class="line"></div>
            <div class="row"><span>Total digital</span><strong>{{ $money($digitalTotal) }} Kz</strong></div>
        @endif

        @if(!empty($shift->notes))
            <div class="note small">
                <strong>Obs.:</strong> {{ $shift->notes }}
            </div>
        @endif

        <div class="line"></div>
        <div class="center small">Impresso em {{ now()->format('d/m/Y H:i') }}</div>
    </div>
</body>
</html>
