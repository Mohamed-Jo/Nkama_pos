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
            <td><span>Dinheiro</span><strong>{{ number_format($totals['cash'], 2, ',', '.') }}</strong></td>
            <td><span>Multicaixa</span><strong>{{ number_format($totals['card'] + $totals['multi'], 2, ',', '.') }}</strong></td>
            <td><span>Transferencia</span><strong>{{ number_format($totals['transf'], 2, ',', '.') }}</strong></td>
            <td><span>Reembolsos</span><strong>{{ number_format($totals['refunds'], 2, ',', '.') }}</strong></td>
            <td><span>Liquido</span><strong>{{ number_format($totals['net'], 2, ',', '.') }}</strong></td>
        </tr>
    </table>

    <table class="data">
        <thead>
            <tr>
                <th>Data</th>
                <th>Referencia</th>
                <th>Metodo</th>
                <th>Operador</th>
                <th class="right">Valor</th>
            </tr>
        </thead>
        <tbody>
            @foreach($payments as $payment)
                <tr>
                    <td>{{ optional($payment->created_at)->format('d/m/Y H:i') }}</td>
                    <td>
                        @if($payment->creditNote)
                            NC {{ $payment->creditNote->invoice_number ?? '#' . $payment->credit_note_id }}
                        @elseif($payment->sale)
                            {{ $payment->sale->invoice_number }}
                        @else
                            #{{ $payment->id }}
                        @endif
                    </td>
                    <td>{{ strtoupper($payment->method) }}</td>
                    <td>{{ $payment->operator->name ?? '-' }}</td>
                    <td class="right {{ $payment->amount < 0 ? 'negative' : '' }}">{{ number_format($payment->amount, 2, ',', '.') }}</td>
                </tr>
            @endforeach
            @foreach($cashMovements as $movement)
                <tr>
                    <td>{{ optional($movement->created_at)->format('d/m/Y H:i') }}</td>
                    <td>Conta Corrente #{{ $movement->id }}</td>
                    <td>{{ strtoupper($movement->method) }}</td>
                    <td>{{ $movement->operator->name ?? '-' }}</td>
                    <td class="right {{ $movement->amount < 0 ? 'negative' : '' }}">{{ number_format($movement->amount, 2, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">Turnos no periodo: {{ $shifts->count() }}</div>
</body>
</html>
