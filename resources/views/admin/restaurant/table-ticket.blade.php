<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consulta {{ $table->name }}</title>
    <style>
        @page { margin: 0; size: 80mm auto; }
        * { box-sizing: border-box; }
        body {
            background: #f3f4f6;
            color: #111827;
            font-family: "Consolas", "Courier New", monospace;
            font-size: 11px;
            margin: 0;
            padding: 12px;
        }
        .ticket { background: #fff; margin: 0 auto; padding: 10px; width: 76mm; }
        .center { text-align: center; }
        .logo { margin: 0 auto 6px; max-height: 42px; max-width: 120px; object-fit: contain; }
        .company-name { font-size: 14px; font-weight: 800; text-transform: uppercase; }
        .muted { color: #374151; }
        .line { border-top: 1px dashed #111827; margin: 8px 0; }
        .row { display: flex; gap: 8px; justify-content: space-between; }
        .row strong:last-child, .row span:last-child { text-align: right; }
        .item { margin-bottom: 7px; }
        .item-name { font-weight: 700; overflow-wrap: anywhere; }
        .totals { font-size: 12px; font-weight: 700; }
        .total-final { font-size: 15px; margin-top: 4px; }
        .actions { display: flex; gap: 8px; justify-content: center; margin: 12px auto; width: 76mm; }
        .actions button {
            background: #111827;
            border: none;
            border-radius: 6px;
            color: #fff;
            cursor: pointer;
            font-family: system-ui, sans-serif;
            font-size: 12px;
            font-weight: 700;
            padding: 8px 10px;
        }
        @media print {
            body { background: #fff; padding: 0; }
            .ticket { margin: 0; width: 80mm; }
            .actions { display: none; }
        }
    </style>
</head>
<body>
    <div class="actions">
        <button type="button" onclick="window.print()">Imprimir Consulta</button>
    </div>

    <main class="ticket">
        <div class="center">
            @if($logoUrl)
                <img class="logo" src="{{ $logoUrl }}" alt="Logotipo">
            @endif
            <div class="company-name">{{ $company['name'] ?: config('app.name', 'Nkama ERP') }}</div>
            @if(!empty($company['location']))
                <div class="muted">{{ $company['location'] }}</div>
            @endif
            @if(!empty($company['nif']))
                <div class="muted">NIF: {{ $company['nif'] }}</div>
            @endif
        </div>

        <div class="line"></div>

        <div class="center">
            <strong>CONSULTA DE MESA</strong>
            <div class="muted">Nao e documento fiscal</div>
        </div>

        <div class="line"></div>

        <div class="row">
            <span>Mesa</span>
            <strong>{{ $table->name }}</strong>
        </div>
        <div class="row">
            <span>Conta</span>
            <span>#{{ $order->id }}</span>
        </div>
        <div class="row">
            <span>Data</span>
            <span>{{ now()->format('d/m/Y H:i') }}</span>
        </div>
        <div class="row">
            <span>Operador</span>
            <span>{{ $order->operator->name ?? session('operator_name', 'Operador') }}</span>
        </div>

        <div class="line"></div>

        @foreach($order->items as $item)
            @php
                $taxRate = (float) ($item->product?->tax_rate ?? 0);
                $taxAmount = \App\Services\BusinessSettings::splitGrossTotal((float) $item->subtotal, $taxRate)['tax'];
            @endphp
            <div class="item">
                <div class="item-name">{{ $item->product->name ?? 'Produto removido' }}</div>
                <div class="row">
                    <span>{{ number_format($item->qty, 0) }} x {{ number_format($item->price, 2, ',', '.') }}</span>
                    <strong>{{ number_format($item->subtotal, 2, ',', '.') }}</strong>
                </div>
                <div class="row muted">
                    <span>IVA {{ number_format($taxRate, 2, ',', '.') }}%</span>
                    <span>{{ number_format($taxAmount, 2, ',', '.') }}</span>
                </div>
            </div>
        @endforeach

        <div class="line"></div>

        <div class="totals">
            <div class="row">
                <span>Subtotal</span>
                <span>{{ number_format($totals['subtotal'], 2, ',', '.') }}</span>
            </div>
            <div class="row">
                <span>IVA incluido</span>
                <span>{{ number_format($totals['tax'], 2, ',', '.') }}</span>
            </div>
            <div class="row total-final">
                <span>TOTAL</span>
                <span>AOA {{ number_format($totals['total'], 2, ',', '.') }}</span>
            </div>
        </div>

        <div class="line"></div>

        <div class="center">
            <strong>Pre-conta para conferencia</strong>
            <div class="muted">A mesa continua aberta ate ao pagamento</div>
        </div>
    </main>

    @if(request()->boolean('print'))
        <script>
            window.addEventListener('load', () => window.print());
        </script>
    @endif
</body>
</html>
