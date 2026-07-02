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
            <td><span>Em aberto</span><strong>{{ $totals['open'] }}</strong></td>
            <td><span>Por aprovar</span><strong>{{ $totals['pending_approval'] }}</strong></td>
            <td><span>Aprovadas</span><strong>{{ $totals['approved'] }}</strong></td>
        </tr>
        <tr>
            <td><span>Rejeitadas</span><strong>{{ $totals['rejected'] }}</strong></td>
            <td><span>Parciais</span><strong>{{ $totals['partial'] }}</strong></td>
            <td><span>Recebidas</span><strong>{{ $totals['received'] }}</strong></td>
            <td><span>Vencidas</span><strong>{{ $totals['overdue'] }}</strong></td>
        </tr>
        <tr>
            <td><span>Conta corrente</span><strong>{{ number_format($totals['credit'], 2, ',', '.') }}</strong></td>
            <td><span>Diretas</span><strong>{{ number_format($totals['direct'], 2, ',', '.') }}</strong></td>
            <td><span>Pago</span><strong>{{ number_format($totals['paid'], 2, ',', '.') }}</strong></td>
            <td><span>Saldo</span><strong>{{ number_format($totals['balance'], 2, ',', '.') }}</strong></td>
        </tr>
        <tr>
            <td><span>Total</span><strong>{{ number_format($totals['total'], 2, ',', '.') }}</strong></td>
            <td colspan="3"><span>Periodo</span><strong>{{ $from->format('d/m/Y') }} - {{ $to->format('d/m/Y') }}</strong></td>
        </tr>
    </table>

    <table class="data">
        <thead>
            <tr>
                <th>Data</th>
                <th>Documento</th>
                <th>Fornecedor</th>
                <th>Aprovacao</th>
                <th>Estado</th>
                <th>Liquidacao</th>
                <th>Pagamento</th>
                <th class="right">Saldo</th>
                <th class="right">Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse($purchases as $purchase)
                <tr>
                    <td>{{ optional($purchase->created_at)->format('d/m/Y H:i') }}</td>
                    <td>#{{ $purchase->id }}<br><span class="muted">{{ $purchase->document_number ?: '-' }}</span></td>
                    <td>{{ $purchase->supplier->company_name ?? 'Fornecedor removido' }}</td>
                    <td>{{ $purchase->approvalLabel() }}</td>
                    <td>{{ $purchase->statusLabel() }}</td>
                    <td>{{ $purchase->payment_type === 'credit' ? 'Conta corrente' : 'Direta' }}</td>
                    <td>{{ $purchase->paymentStatusLabel() }}{{ $purchase->isOverdue() ? ' / Vencida' : '' }}</td>
                    <td class="right">{{ number_format((float) $purchase->balance, 2, ',', '.') }}</td>
                    <td class="right">{{ number_format((float) $purchase->total, 2, ',', '.') }}</td>
                </tr>
            @empty
                <tr><td colspan="9" class="muted">Sem compras neste periodo.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">Documento gerado pelo Nkama ERP.</div>
</body>
</html>
