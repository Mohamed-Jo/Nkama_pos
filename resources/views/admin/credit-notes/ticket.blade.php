<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NC {{ $creditNote->invoice_number }}</title>
    <style>
        @page { margin: 0; size: 80mm auto; }
        * { box-sizing: border-box; }
        body { background:#f3f4f6; color:#111827; font-family:"Consolas","Courier New",monospace; font-size:11px; margin:0; padding:12px; }
        .ticket { background:#fff; margin:0 auto; padding:10px; width:76mm; }
        .center { text-align:center; }
        .logo { margin:0 auto 6px; max-height:42px; max-width:120px; object-fit:contain; }
        .company-name { font-size:14px; font-weight:800; text-transform:uppercase; }
        .muted { color:#374151; }
        .line { border-top:1px dashed #111827; margin:8px 0; }
        .row { display:flex; gap:8px; justify-content:space-between; }
        .row strong:last-child, .row span:last-child { text-align:right; }
        .item { margin-bottom:7px; }
        .item-name { font-weight:700; overflow-wrap:anywhere; }
        .totals { font-size:12px; font-weight:700; }
        .total-final { font-size:15px; margin-top:4px; }
        .actions { display:flex; gap:8px; justify-content:center; margin:12px auto; width:76mm; }
        .actions button, .actions a { background:#111827; border:none; border-radius:6px; color:#fff; cursor:pointer; font-family:system-ui,sans-serif; font-size:12px; font-weight:700; padding:8px 10px; text-decoration:none; }
        @media print { body { background:#fff; padding:0; } .ticket { margin:0; width:80mm; } .actions { display:none; } }
    </style>
</head>
<body>
    <div class="actions">
        <button type="button" onclick="window.print()">Imprimir</button>
        <a href="{{ route('admin.sales.ticket', $creditNote->originalSale) }}">Documento original</a>
    </div>

    <main class="ticket">
        <div class="center">
            @if($logoUrl)
                <img class="logo" src="{{ $logoUrl }}" alt="Logotipo">
            @endif
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
            <div class="item">
                <div class="item-name">{{ $item->product->name ?? 'Produto removido' }}</div>
                <div class="row">
                    <span>{{ number_format($item->quantity, 2, ',', '.') }} x {{ number_format($item->unit_price, 2, ',', '.') }}</span>
                    <strong>{{ number_format($item->subtotal, 2, ',', '.') }}</strong>
                </div>
                <div class="row muted">
                    <span>IVA {{ number_format($item->tax_rate ?? 0, 2, ',', '.') }}%</span>
                    <span>{{ number_format($item->tax_amount ?? 0, 2, ',', '.') }}</span>
                </div>
            </div>
        @endforeach

        <div class="line"></div>
        <div class="totals">
            <div class="row"><span>Subtotal</span><span>{{ number_format($creditNote->subtotal, 2, ',', '.') }}</span></div>
            <div class="row"><span>IVA incluido</span><span>{{ number_format($creditNote->tax, 2, ',', '.') }}</span></div>
            <div class="row total-final"><span>TOTAL NC</span><span>AOA {{ number_format($creditNote->total, 2, ',', '.') }}</span></div>
        </div>

        <div class="line"></div>
        <div class="center">
            <strong>Documento retificativo</strong>
            <div class="muted">Processado por programa informatico</div>
        </div>
    </main>

    @if(request()->boolean('print'))
        <script>window.addEventListener('load', () => window.print());</script>
    @endif
</body>
</html>
