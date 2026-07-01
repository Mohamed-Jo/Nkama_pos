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
            <td><span>Debitos</span><strong>{{ number_format($totals['debit'], 2, ',', '.') }}</strong></td>
            <td><span>Creditos</span><strong>{{ number_format($totals['credit'], 2, ',', '.') }}</strong></td>
            <td><span>Saldo</span><strong>{{ number_format($totals['balance'], 2, ',', '.') }}</strong></td>
        </tr>
    </table>

    <table class="data">
        <thead>
            <tr>
                <th>Data</th>
                <th>Entidade</th>
                <th>Origem</th>
                <th>Descricao</th>
                <th class="right">Debito</th>
                <th class="right">Credito</th>
                <th class="right">Saldo Mov.</th>
            </tr>
        </thead>
        <tbody>
            @foreach($entries as $entry)
                <tr>
                    <td>{{ optional($entry->entry_date)->format('d/m/Y') }}</td>
                    <td>{{ $entry->entity_name }}<br><span class="muted">{{ $entry->entity_type === 'customer' ? 'Cliente' : 'Fornecedor' }}</span></td>
                    <td>
                        @php
                            $originLabel = match($entry->document_type) {
                                'sale' => 'FT',
                                'credit_note' => 'NC',
                                'purchase' => 'Compra',
                                'current_account_settlement' => 'Liquidacao',
                                default => 'Ajuste',
                            };
                        @endphp
                        {{ $originLabel }}@if($entry->document_id) #{{ $entry->document_id }} @endif
                    </td>
                    <td>{{ $entry->description ?: '-' }}</td>
                    <td class="right">{{ number_format($entry->debit, 2, ',', '.') }}</td>
                    <td class="right">{{ number_format($entry->credit, 2, ',', '.') }}</td>
                    <td class="right">{{ number_format($entry->signed_amount, 2, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">Documento gerado pelo Nkama ERP.</div>
</body>
</html>
