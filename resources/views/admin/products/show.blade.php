@extends('layouts.admin')

@section('content')
<div class="product-show">
    <div class="page-header">
        <div>
            <h1>{{ $product->name }}</h1>
            <p>{{ $product->barcode ?? 'SEM-CODIGO' }} · {{ $product->category->name ?? 'Sem categoria' }}</p>
        </div>
        <div class="header-actions">
            <a href="{{ route('admin.stock.movements', ['product_id' => $product->id]) }}" class="btn-secondary">Movimentos</a>
            <a href="{{ route('admin.products.edit', $product) }}" class="btn-primary">Editar Produto</a>
            <a href="{{ route('admin.products.index') }}" class="btn-secondary">Voltar</a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-error">{{ session('error') }}</div>
    @endif

    @php
        $cost = (float) ($product->purchase_price ?? 0);
        $price = (float) $product->selling_price;
        $margin = $price > 0 ? (($price - $cost) / $price) * 100 : 0;
    @endphp

    <div class="metric-grid">
        <div class="metric"><span>Stock atual</span><strong>{{ number_format((float) $product->stock_quantity, 0, ',', '.') }} {{ $product->unit ?? 'un' }}</strong></div>
        <div class="metric"><span>Estado</span><strong class="{{ $product->stockStatusClass() }}">{{ $product->stockStatusLabel() }}</strong></div>
        <div class="metric"><span>Minimo / ideal</span><strong>{{ number_format((float) $product->minimum_stock, 0, ',', '.') }} / {{ number_format((float) ($product->target_stock ?? 0), 0, ',', '.') }}</strong></div>
        <div class="metric"><span>Preco venda</span><strong>AOA {{ number_format($price, 2, ',', '.') }}</strong></div>
        <div class="metric"><span>Custo medio</span><strong>AOA {{ number_format($purchaseAvg ?: $cost, 2, ',', '.') }}</strong></div>
        <div class="metric"><span>Margem estimada</span><strong>{{ number_format($margin, 1, ',', '.') }}%</strong></div>
        <div class="metric"><span>Vendido 30 dias</span><strong>{{ number_format($sold30, 0, ',', '.') }}</strong></div>
        <div class="metric"><span>Cobertura</span><strong>{{ $daysCoverage === null ? 'Sem consumo' : $daysCoverage . ' dias' }}</strong></div>
    </div>

    <div class="detail-grid">
        <section class="panel">
            <h2>Dados do produto</h2>
            <dl>
                <dt>IVA</dt><dd>{{ number_format((float) ($product->tax_rate ?? 0), 2, ',', '.') }}%</dd>
                <dt>Localizacao</dt><dd>{{ $product->stock_location ?: '-' }}</dd>
                <dt>Controlo de stock</dt><dd>{{ $product->track_stock ? 'Sim' : 'Nao' }}</dd>
                <dt>Ativo</dt><dd>{{ $product->status ? 'Sim' : 'Nao' }}</dd>
                <dt>Restaurante</dt><dd>{{ $product->available_restaurant ? 'Sim' : 'Nao' }}</dd>
                <dt>Supermercado</dt><dd>{{ $product->available_supermarket ? 'Sim' : 'Nao' }}</dd>
            </dl>
            <p class="description">{{ $product->description ?: 'Sem descricao.' }}</p>
        </section>

        <section class="panel">
            <h2>Ajuste rapido</h2>
            <form method="POST" action="{{ route('admin.stock.adjust') }}" class="adjust-form">
                @csrf
                <input type="hidden" name="product_id" value="{{ $product->id }}">
                <label>Modo<select name="mode"><option value="in">Entrada</option><option value="out">Saida</option><option value="set">Definir stock real</option></select></label>
                <label>Quantidade<input type="number" name="quantity" min="0" required></label>
                <label>Motivo<input type="text" name="reason" placeholder="Quebra, contagem, correcao..." required></label>
                <label>Notas<textarea name="notes" rows="3"></textarea></label>
                <button type="submit">Registar ajuste</button>
            </form>
        </section>
    </div>

    <section class="panel movements-panel">
        <h2>Ultimos movimentos</h2>
        <table>
            <thead><tr><th>Data</th><th>Tipo</th><th>Qtd.</th><th>Antes</th><th>Depois</th><th>Motivo</th><th>Operador</th></tr></thead>
            <tbody>
                @forelse($movements as $movement)
                    <tr>
                        <td>{{ $movement->created_at?->format('d/m/Y H:i') }}</td>
                        <td class="{{ $movement->type === 'IN' ? 'positive' : 'negative' }}">{{ $movement->type }}</td>
                        <td>{{ number_format((float) $movement->quantity, 0, ',', '.') }}</td>
                        <td>{{ number_format((float) $movement->stock_before, 0, ',', '.') }}</td>
                        <td>{{ number_format((float) $movement->stock_after, 0, ',', '.') }}</td>
                        <td>{{ $movement->reason ?? $movement->notes ?? '-' }}</td>
                        <td>{{ $movement->operator->name ?? 'Sistema' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="empty">Sem movimentos registados.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>
</div>

<style>
    .product-show { max-width: 1300px; margin: 0 auto; color: #cbd5e1; }
    .page-header, .header-actions { display: flex; align-items: center; gap: 10px; }
    .page-header { justify-content: space-between; margin-bottom: 22px; }
    .page-header h1 { color: #fff; margin: 0; font-size: 2rem; }
    .page-header p { color: #94a3b8; }
    .btn-primary, .btn-secondary, .adjust-form button { border: 0; border-radius: 8px; padding: 10px 14px; text-decoration: none; font-weight: 800; cursor: pointer; }
    .btn-primary, .adjust-form button { background: #ea580c; color: #fff; }
    .btn-secondary { background: #1e293b; color: #e2e8f0; }
    .alert { margin-bottom: 16px; padding: 12px 14px; border-radius: 8px; }
    .alert-success { background: #052e1b; color: #86efac; border: 1px solid #166534; }
    .alert-error { background: #450a0a; color: #fecaca; border: 1px solid #991b1b; }
    .metric-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 10px; margin-bottom: 16px; }
    .metric, .panel { background: #0f172a; border: 1px solid #1e293b; border-radius: 8px; }
    .metric { padding: 14px; }
    .metric span { display: block; color: #94a3b8; font-size: .72rem; text-transform: uppercase; }
    .metric strong { display: block; margin-top: 6px; color: #fff; }
    .stock-ok { color: #86efac !important; } .stock-low { color: #fbbf24 !important; } .stock-out { color: #f87171 !important; } .stock-muted { color: #cbd5e1 !important; }
    .detail-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px; }
    .panel { padding: 16px; }
    .panel h2 { color: #fff; margin: 0 0 14px; font-size: 1rem; }
    dl { display: grid; grid-template-columns: 150px 1fr; gap: 8px 14px; margin: 0; }
    dt { color: #94a3b8; } dd { margin: 0; color: #fff; }
    .description { color: #cbd5e1; margin-top: 16px; }
    .adjust-form { display: grid; gap: 12px; }
    .adjust-form label { display: grid; gap: 6px; color: #94a3b8; font-size: .82rem; font-weight: 700; }
    .adjust-form input, .adjust-form select, .adjust-form textarea { width: 100%; background: #020617; border: 1px solid #334155; border-radius: 8px; color: #e2e8f0; padding: 10px; }
    table { width: 100%; border-collapse: collapse; }
    th { text-align: left; color: #94a3b8; font-size: .72rem; text-transform: uppercase; padding: 10px; border-bottom: 1px solid #1e293b; }
    td { padding: 10px; border-bottom: 1px solid #1e293b; }
    .positive { color: #86efac; font-weight: 800; } .negative { color: #fca5a5; font-weight: 800; }
    .empty { text-align: center; color: #94a3b8; padding: 28px; }
    @media (max-width: 900px) { .metric-grid, .detail-grid { grid-template-columns: 1fr; } .page-header { flex-direction: column; align-items: flex-start; } }
</style>
@endsection