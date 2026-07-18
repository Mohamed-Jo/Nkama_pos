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
            <td><span>Turnos</span><strong>{{ $totals['count'] }}</strong></td>
            <td><span>Abertos</span><strong>{{ $totals['open'] }}</strong></td>
            <td><span>Fechados</span><strong>{{ $totals['closed'] }}</strong></td>
            <td><span>Vendas</span><strong>{{ number_format($totals['sales'], 2, ',', '.') }}</strong></td>
        </tr>
        <tr>
            <td><span>Fundo inicial</span><strong>{{ number_format($totals['opening_cash'], 2, ',', '.') }}</strong></td>
            <td><span>Esperado</span><strong>{{ number_format($totals['expected_cash'], 2, ',', '.') }}</strong></td>
            <td><span>Contado</span><strong>{{ number_format($totals['closing_cash'], 2, ',', '.') }}</strong></td>
            <td><span>Diferenca</span><strong>{{ number_format($totals['difference'], 2, ',', '.') }}</strong></td>
        </tr>
    </table>

    <table class="data">
        <thead>
            <tr>
                <th>Operador</th>
                <th>Abertura</th>
                <th>Fecho</th>
                <th>Estado</th>
                <th class="right">Esperado</th>
                <th class="right">Contado</th>
                <th class="right">Diferenca</th>
            </tr>
        </thead>
        <tbody>
            @forelse($shifts as $shift)
                <tr>
                    <td>{{ $shift->operator->name ?? 'Sistema' }}</td>
                    <td>{{ optional($shift->opened_at)->format('d/m/Y H:i') }}</td>
                    <td>{{ optional($shift->closed_at)->format('d/m/Y H:i') ?: '-' }}</td>
                    <td>{{ $shift->status }}</td>
                    <td class="right">{{ number_format((float) ($shift->expected_cash ?? 0), 2, ',', '.') }}</td>
                    <td class="right">{{ number_format((float) ($shift->closing_cash ?? 0), 2, ',', '.') }}</td>
                    <td class="right {{ (float) ($shift->difference ?? 0) < 0 ? 'negative' : '' }}">{{ number_format((float) ($shift->difference ?? 0), 2, ',', '.') }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="muted">Sem turnos neste periodo.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">Documento gerado pelo MARIA ERP.</div>
</body>
</html>
