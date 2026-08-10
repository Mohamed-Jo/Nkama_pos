@extends('layouts.admin')

@section('page-title', 'Etiquetas de Stock')

@section('content')
<div class="labels-page">
    <div class="page-header no-print">
        <div>
            <h1>Etiquetas</h1>
            <p>Codigos de barras, lotes, validade e series para impressao.</p>
        </div>
        <div class="actions">
            <a href="{{ route('admin.stock.index') }}" class="btn-secondary">Voltar</a>
            <button type="button" onclick="window.print()" class="btn-primary">Imprimir</button>
        </div>
    </div>

    <form method="GET" class="filter-bar no-print">
        <input name="search" value="{{ request('search') }}" placeholder="Pesquisar produto ou codigo">
        <button type="submit">Filtrar</button>
    </form>

    <section class="sheet">
        @foreach($products as $product)
            <article class="label-card">
                <strong>{{ $product->name }}</strong>
                <div class="barcode">{{ $product->barcode ?: 'PROD-' . $product->id }}</div>
                <small>AOA {{ number_format((float) $product->selling_price, 2, ',', '.') }} · {{ $product->unit ?? 'un' }}</small>
            </article>
        @endforeach
    </section>
    <div class="pagination-wrap no-print">{{ $products->links() }}</div>

    @if($batches->isNotEmpty())
        <h2 class="batch-title">Lotes em stock</h2>
        <section class="sheet batch-sheet">
            @foreach($batches as $batch)
                <article class="label-card compact">
                    <strong>{{ $batch->product->name ?? 'Produto removido' }}</strong>
                    <div class="barcode">{{ $batch->serial_number ?: ($batch->lot_number ?: 'LOTE-' . $batch->id) }}</div>
                    <small>{{ $batch->warehouse->name ?? 'Geral' }} · Qtd. {{ $batch->quantity }} @if($batch->expires_at) · Val. {{ $batch->expires_at->format('d/m/Y') }} @endif</small>
                </article>
            @endforeach
        </section>
    @endif
</div>

<style>
    .labels-page { max-width: 1200px; margin: 0 auto; color: #cbd5e1; }
    .page-header, .actions, .filter-bar { display: flex; align-items: center; gap: 10px; }
    .page-header { justify-content: space-between; margin-bottom: 18px; }
    .page-header h1 { color: #fff; margin: 0; font-size: 2rem; }
    .page-header p { color: #94a3b8; }
    .btn-primary, .btn-secondary, .filter-bar button { border: 0; border-radius: 8px; cursor: pointer; font-weight: 800; padding: 10px 14px; text-decoration: none; }
    .btn-primary, .filter-bar button { background: #ea580c; color: #fff; }
    .btn-secondary { background: #1e293b; color: #e2e8f0; }
    .filter-bar { background: #0f172a; border: 1px solid #1e293b; border-radius: 8px; margin-bottom: 16px; padding: 12px; }
    .filter-bar input { background: #020617; border: 1px solid #334155; border-radius: 8px; color: #e2e8f0; max-width: 320px; padding: 9px 10px; width: 100%; }
    .sheet { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 10px; }
    .label-card { background: #fff; border: 1px dashed #64748b; border-radius: 6px; color: #020617; min-height: 96px; padding: 10px; text-align: center; }
    .label-card strong { display: block; font-size: .82rem; min-height: 34px; }
    .barcode { font-family: 'Courier New', monospace; font-size: 1.35rem; font-weight: 900; letter-spacing: 0; margin: 8px 0 4px; }
    .label-card small { color: #334155; display: block; font-size: .72rem; }
    .compact { min-height: 84px; }
    .batch-title { color: #fff; font-size: 1rem; margin: 22px 0 10px; }
    .pagination-wrap { margin-top: 12px; }
    @media print {
        body { background: #fff !important; }
        .no-print, aside, nav, header { display: none !important; }
        .labels-page { color: #000; max-width: none; }
        .sheet { grid-template-columns: repeat(3, 1fr); gap: 6mm; }
        .label-card { break-inside: avoid; border-color: #000; }
    }
</style>
@endsection