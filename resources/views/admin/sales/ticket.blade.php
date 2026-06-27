<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket {{ $sale->invoice_number }}</title>
    <style>
        @page {
            margin: 0;
            size: 80mm auto;
        }

        * { box-sizing: border-box; }

        body {
            background: #f3f4f6;
            color: #111827;
            font-family: "Consolas", "Courier New", monospace;
            font-size: 11px;
            margin: 0;
            padding: 12px;
        }

        .ticket {
            background: #fff;
            margin: 0 auto;
            padding: 10px;
            width: 76mm;
        }

        .center { text-align: center; }

        .logo {
            margin: 0 auto 6px;
            max-height: 42px;
            max-width: 120px;
            object-fit: contain;
        }

        .company-name {
            font-size: 14px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .muted { color: #374151; }

        .line {
            border-top: 1px dashed #111827;
            margin: 8px 0;
        }

        .row {
            display: flex;
            gap: 8px;
            justify-content: space-between;
        }

        .row strong:last-child,
        .row span:last-child {
            text-align: right;
        }

        .item { margin-bottom: 7px; }

        .item-name {
            font-weight: 700;
            overflow-wrap: anywhere;
        }

        .totals {
            font-size: 12px;
            font-weight: 700;
        }

        .total-final {
            font-size: 15px;
            margin-top: 4px;
        }

        .actions {
            display: flex;
            gap: 8px;
            justify-content: center;
            margin: 12px auto;
            width: 76mm;
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
                margin: 0;
                width: 80mm;
            }

            .actions { display: none; }
        }
    </style>
</head>
<body>
    <div class="actions">
        <button type="button" onclick="window.print()">Imprimir</button>
        <a href="{{ route('admin.sales.credit-notes.create', $sale) }}">Emitir NC</a>
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

        <div class="line"></div>

        @foreach($sale->items as $item)
            <div class="item">
                <div class="item-name">{{ $item->product->name ?? 'Produto removido' }}</div>
                <div class="row">
                    <span>{{ number_format($item->quantity, 0) }} x {{ number_format($item->unit_price, 2, ',', '.') }}</span>
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
            <div class="row">
                <span>Subtotal</span>
                <span>{{ number_format($sale->subtotal, 2, ',', '.') }}</span>
            </div>
            <div class="row">
                <span>IVA incluido</span>
                <span>{{ number_format($sale->tax, 2, ',', '.') }}</span>
            </div>
            <div class="row total-final">
                <span>TOTAL</span>
                <span>AOA {{ number_format($sale->total, 2, ',', '.') }}</span>
            </div>
        </div>

        <div class="line"></div>

        <div class="row">
            <span>Pago</span>
            <span>{{ number_format($sale->paid ?? $sale->total, 2, ',', '.') }}</span>
        </div>
        @php($pendingAmount = max((float) $sale->total - (float) ($sale->paid ?? 0), 0))
        @if($pendingAmount > 0)
            <div class="row">
                <span>Pendente</span>
                <span>{{ number_format($pendingAmount, 2, ',', '.') }}</span>
            </div>
        @endif
        <div class="row">
            <span>Troco</span>
            <span>{{ number_format($sale->change ?? 0, 2, ',', '.') }}</span>
        </div>
        <div class="row">
            <span>Metodo</span>
            <span>{{ strtoupper($sale->payment_method ?? '-') }}</span>
        </div>

        <div class="line"></div>

        <div class="center">
            <strong>Obrigado pela preferencia</strong>
            <div class="muted">Documento processado por programa informatico</div>
        </div>
    </main>

    @if(request()->boolean('print'))
        <script>
            window.addEventListener('load', () => window.print());
        </script>
    @endif
</body>
</html>
