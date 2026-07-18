<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    @include('admin.reports.partials.pdf-style')
</head>
<body>
    @include('admin.reports.partials.header')

    <table class="summary">
        <tr>
            <td><span>FR</span><strong>{{ number_format($totals['fr'], 2, ',', '.') }}</strong></td>
            <td><span>FT</span><strong>{{ number_format($totals['ft'], 2, ',', '.') }}</strong></td>
            <td><span>Recebido</span><strong>{{ number_format($totals['paid'], 2, ',', '.') }}</strong></td>
            <td><span>Pendente</span><strong>{{ number_format($totals['pending'], 2, ',', '.') }}</strong></td>
            <td><span>NC</span><strong>{{ number_format($totals['nc'], 2, ',', '.') }}</strong></td>
            <td><span>Liquido</span><strong>{{ number_format($totals['net'], 2, ',', '.') }}</strong></td>
        </tr>
    </table>

    <table class="data">
        <thead>
            <tr>
                <th>Data</th>
                <th>Doc.</th>
                <th>Cliente</th>
                <th>Operador</th>
                <th class="right">Total</th>
                <th class="right">Pago</th>
                <th class="right">Pendente</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sales as $sale)
                @php
                    $pending = max((float) $sale->total - (float) $sale->creditNotes->sum('total') - (float) $sale->paid, 0);
                @endphp
                <tr>
                    <td>{{ optional($sale->created_at)->format('d/m/Y H:i') }}</td>
                    <td>{{ $sale->invoice_number }}</td>
                    <td>{{ $sale->customer->name ?? 'Consumidor Final' }}</td>
                    <td>{{ $sale->operator->name ?? '-' }}</td>
                    <td class="right">{{ number_format($sale->total, 2, ',', '.') }}</td>
                    <td class="right">{{ number_format($sale->paid, 2, ',', '.') }}</td>
                    <td class="right">{{ number_format($pending, 2, ',', '.') }}</td>
                </tr>
            @endforeach
            @foreach($creditNotes as $note)
                <tr>
                    <td>{{ optional($note->created_at)->format('d/m/Y H:i') }}</td>
                    <td>{{ $note->invoice_number }}<br><span class="muted">Ref. {{ $note->originalSale->invoice_number ?? '-' }}</span></td>
                    <td>{{ $note->customer->name ?? 'Consumidor Final' }}</td>
                    <td>{{ $note->operator->name ?? '-' }}</td>
                    <td class="right negative">-{{ number_format($note->total, 2, ',', '.') }}</td>
                    <td class="right">-</td>
                    <td class="right">-</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">Documento gerado pelo MARIA ERP.</div>
</body>
</html>
