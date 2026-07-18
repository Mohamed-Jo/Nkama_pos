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
            <td><span>Movimentos</span><strong>{{ $totals['count'] }}</strong></td>
            <td><span>Entradas</span><strong>{{ number_format($totals['in'], 2, ',', '.') }}</strong></td>
            <td><span>Saidas</span><strong>{{ number_format($totals['out'], 2, ',', '.') }}</strong></td>
            <td><span>Saldo qtd.</span><strong>{{ number_format($totals['in'] - $totals['out'], 2, ',', '.') }}</strong></td>
        </tr>
    </table>

    <table class="data">
        <thead>
            <tr>
                <th>Data</th>
                <th>Produto</th>
                <th>Tipo</th>
                <th class="right">Qtd</th>
                <th class="right">Antes</th>
                <th class="right">Depois</th>
                <th>Notas</th>
                <th>Operador</th>
            </tr>
        </thead>
        <tbody>
            @forelse($movements as $movement)
                <tr>
                    <td>{{ optional($movement->created_at)->format('d/m/Y H:i') }}</td>
                    <td>{{ $movement->product->name ?? 'Produto removido' }}</td>
                    <td>{{ $movement->type }}</td>
                    <td class="right">{{ number_format((float) $movement->quantity, 2, ',', '.') }}</td>
                    <td class="right">{{ number_format((float) $movement->stock_before, 2, ',', '.') }}</td>
                    <td class="right">{{ number_format((float) $movement->stock_after, 2, ',', '.') }}</td>
                    <td>{{ $movement->notes ?: '-' }}</td>
                    <td>{{ $movement->operator->name ?? 'Sistema' }}</td>
                </tr>
            @empty
                <tr><td colspan="8" class="muted">Sem movimentos de stock neste periodo.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">Documento gerado pelo MARIA ERP.</div>
</body>
</html>
