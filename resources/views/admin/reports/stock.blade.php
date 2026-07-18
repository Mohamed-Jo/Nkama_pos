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
            <td><span>Produtos</span><strong>{{ $totals['items'] }}</strong></td>
            <td><span>Qtd. stock</span><strong>{{ number_format($totals['stock'], 2, ',', '.') }}</strong></td>
            <td><span>Stock baixo</span><strong>{{ $totals['low'] }}</strong></td>
            <td><span>Valor venda estimado</span><strong>{{ number_format($totals['value'], 2, ',', '.') }}</strong></td>
        </tr>
    </table>

    <table class="data">
        <thead>
            <tr>
                <th>Produto</th>
                <th>Categoria</th>
                <th>Un.</th>
                <th class="right">Stock</th>
                <th class="right">Min.</th>
                <th class="right">Preco venda</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            @foreach($products as $product)
                @php
                    $low = (float) $product->stock_quantity <= (float) $product->minimum_stock;
                @endphp
                <tr>
                    <td>
                        <strong>{{ $product->name }}</strong>
                        @if($product->barcode)
                            <br><span class="muted">{{ $product->barcode }}</span>
                        @endif
                    </td>
                    <td>{{ $product->category->name ?? '-' }}</td>
                    <td>{{ $product->unit ?? '-' }}</td>
                    <td class="right {{ $low ? 'negative' : '' }}">{{ number_format((float) $product->stock_quantity, 2, ',', '.') }}</td>
                    <td class="right">{{ number_format((float) $product->minimum_stock, 2, ',', '.') }}</td>
                    <td class="right">{{ number_format((float) $product->selling_price, 2, ',', '.') }}</td>
                    <td>{{ $product->status ? 'Ativo' : 'Inativo' }}{{ $low ? ' / Stock baixo' : '' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">Documento gerado pelo MARIA ERP.</div>
</body>
</html>
