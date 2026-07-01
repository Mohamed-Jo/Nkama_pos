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
            <td><span>Compras</span><strong>{{ $totals['count'] }}</strong></td>
            <td><span>Por receber</span><strong>{{ $totals['draft'] }}</strong></td>
            <td><span>Recebidas</span><strong>{{ $totals['received'] }}</strong></td>
            <td><span>Total</span><strong>{{ number_format($totals['total'], 2, ',', '.') }}</strong></td>
        </tr>
        <tr>
            <td><span>Conta corrente</span><strong>{{ number_format($totals['credit'], 2, ',', '.') }}</strong></td>
            <td><span>Diretas</span><strong>{{ number_format($totals['direct'], 2, ',', '.') }}</strong></td>
            <td colspan="2"><span>Periodo</span><strong>{{ $from->format('d/m/Y') }} - {{ $to->format('d/m/Y') }}</strong></td>
        </tr>
    </table>

    <table class="data">
        <thead>
            <tr>
                <th>Data</th>
                <th>Documento</th>
                <th>Fornecedor</th>
                <th>Estado</th>
                <th>Liquidacao</th>
                <th class="right">Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse($purchases as $purchase)
                <tr>
                    <td>{{ optional($purchase->created_at)->format('d/m/Y H:i') }}</td>
                    <td>#{{ $purchase->id }}<br><span class="muted">{{ $purchase->document_number ?: '-' }}</span></td>
                    <td>{{ $purchase->supplier->company_name ?? 'Fornecedor removido' }}</td>
                    <td>{{ $purchase->status === 'received' ? 'Recebida' : 'Por receber' }}</td>
                    <td>{{ $purchase->payment_type === 'credit' ? 'Conta corrente' : 'Direta' }}</td>
                    <td class="right">{{ number_format((float) $purchase->total, 2, ',', '.') }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="muted">Sem compras neste periodo.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">Documento gerado pelo Nkama ERP.</div>
</body>
</html>
