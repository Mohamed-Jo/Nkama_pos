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
            <td><span>Total cartoes</span><strong>{{ $totals['cards'] }}</strong></td>
            <td><span>Ativos</span><strong>{{ $totals['active'] }}</strong></td>
            <td><span>Bloqueados</span><strong>{{ $totals['blocked'] }}</strong></td>
            <td><span>Pontos atuais</span><strong>{{ number_format($totals['points_balance'], 0, ',', '.') }}</strong></td>
            <td><span>Saldo atual</span><strong>{{ number_format($totals['money_balance'], 2, ',', '.') }}</strong></td>
        </tr>
        <tr>
            <td><span>Pontos ganhos</span><strong>{{ number_format($totals['points_earned'], 0, ',', '.') }}</strong></td>
            <td><span>Pontos usados</span><strong>{{ number_format($totals['points_used'], 0, ',', '.') }}</strong></td>
            <td><span>Recargas</span><strong>{{ number_format($totals['recharged'], 2, ',', '.') }}</strong></td>
            <td><span>Saldo usado</span><strong>{{ number_format($totals['balance_used'], 2, ',', '.') }}</strong></td>
            <td><span>Pago por cartao</span><strong>{{ number_format($totals['paid_by_card'], 2, ',', '.') }}</strong></td>
        </tr>
    </table>

    <table class="data">
        <thead>
            <tr>
                <th>Cartao</th>
                <th>Cliente</th>
                <th>Validade</th>
                <th>Nivel</th>
                <th>Estado</th>
                <th class="right">Pontos</th>
                <th class="right">Saldo</th>
            </tr>
        </thead>
        <tbody>
            @foreach($cards as $card)
                <tr>
                    <td>{{ $card->card_number }}</td>
                    <td>{{ $card->customer->name ?? '-' }}</td>
                    <td>{{ optional($card->expires_at)->format('d/m/Y') ?? '-' }}</td>
                    <td>{{ $card->level }}</td>
                    <td>{{ $card->status_label }}</td>
                    <td class="right">{{ number_format($card->points, 0, ',', '.') }}</td>
                    <td class="right">{{ number_format($card->balance, 2, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="title" style="font-size:13px;margin-top:16px;">Movimentos de pontos</div>
    <table class="data">
        <thead>
            <tr>
                <th>Data</th>
                <th>Cartao</th>
                <th>Cliente</th>
                <th>Tipo</th>
                <th>Documento</th>
                <th class="right">Pontos</th>
                <th class="right">Saldo pontos</th>
            </tr>
        </thead>
        <tbody>
            @forelse($pointTransactions as $transaction)
                <tr>
                    <td>{{ optional($transaction->created_at)->format('d/m/Y H:i') }}</td>
                    <td>{{ $transaction->card->card_number ?? '-' }}</td>
                    <td>{{ $transaction->card->customer->name ?? '-' }}</td>
                    <td>{{ $transaction->type_label }}</td>
                    <td>{{ $transaction->sale->invoice_number ?? '-' }}</td>
                    <td class="right">{{ number_format($transaction->points, 0, ',', '.') }}</td>
                    <td class="right">{{ number_format($transaction->balance_after, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="muted">Sem movimentos de pontos no periodo.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="title" style="font-size:13px;margin-top:16px;">Movimentos monetarios</div>
    <table class="data">
        <thead>
            <tr>
                <th>Data</th>
                <th>Cartao</th>
                <th>Cliente</th>
                <th>Tipo</th>
                <th>Metodo</th>
                <th>Documento</th>
                <th class="right">Valor</th>
                <th class="right">Saldo</th>
            </tr>
        </thead>
        <tbody>
            @forelse($balanceTransactions as $transaction)
                <tr>
                    <td>{{ optional($transaction->created_at)->format('d/m/Y H:i') }}</td>
                    <td>{{ $transaction->card->card_number ?? '-' }}</td>
                    <td>{{ $transaction->card->customer->name ?? '-' }}</td>
                    <td>{{ $transaction->type_label }}</td>
                    <td>{{ $transaction->method_label }}</td>
                    <td>{{ $transaction->sale->invoice_number ?? '-' }}</td>
                    <td class="right">{{ number_format($transaction->amount, 2, ',', '.') }}</td>
                    <td class="right">{{ number_format($transaction->balance_after, 2, ',', '.') }}</td>
                </tr>
            @empty
                <tr><td colspan="8" class="muted">Sem movimentos monetarios no periodo.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">Documento gerado pelo Nkama ERP.</div>
</body>
</html>