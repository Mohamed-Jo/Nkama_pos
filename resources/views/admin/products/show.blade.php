@extends('layouts.admin')

@section('content')
<div class="space-y-6 max-w-4xl">
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
            <h1 class="text-3xl font-semibold text-white">Detalhes do Produto</h1>
            <p class="mt-2 text-sm text-slate-400">Visualize as informações do produto.</p>
        </div>
        <a href="{{ route('admin.products.index') }}" class="inline-flex items-center rounded-2xl bg-slate-800 px-5 py-3 text-sm font-semibold text-white hover:bg-slate-700">Voltar ao Catálogo</a>
    </div>

    <div class="grid gap-6 rounded-3xl border border-slate-800 bg-slate-900 p-8 shadow-xl shadow-black/20">
        <div class="grid gap-6 md:grid-cols-2">
            <div>
                <h2 class="text-sm font-semibold uppercase tracking-[0.2em] text-slate-400">Nome do Artigo</h2>
                <p class="mt-2 text-white">{{ $product->name }}</p>
            </div>
            <div>
                <h2 class="text-sm font-semibold uppercase tracking-[0.2em] text-slate-400">Categoria</h2>
                <p class="mt-2 text-slate-300">{{ $product->category->name ?? 'Sem Categoria' }}</p>
            </div>
        </div>

        <div class="grid gap-6 md:grid-cols-2">
            <div>
                <h2 class="text-sm font-semibold uppercase tracking-[0.2em] text-slate-400">Preço de Venda</h2>
                <p class="mt-2 text-orange-400">AOA {{ number_format($product->selling_price, 2) }}</p>
            </div>
            <div>
                <h2 class="text-sm font-semibold uppercase tracking-[0.2em] text-slate-400">IVA do Produto</h2>
                <p class="mt-2 text-slate-300">{{ number_format($product->tax_rate ?? 0, 2) }}%</p>
            </div>
        </div>

        <div class="grid gap-6 md:grid-cols-2">
            <div>
                <h2 class="text-sm font-semibold uppercase tracking-[0.2em] text-slate-400">Stock Atual</h2>
                <p class="mt-2 text-slate-300">{{ $product->stock_quantity }} un</p>
            </div>
        </div>

        <div>
            <h2 class="text-sm font-semibold uppercase tracking-[0.2em] text-slate-400">Descrição</h2>
            <p class="mt-2 text-slate-300">{{ $product->description ?? 'Sem descrição disponível.' }}</p>
        </div>

        <div class="grid gap-6 md:grid-cols-2">
            <div>
                <h2 class="text-sm font-semibold uppercase tracking-[0.2em] text-slate-400">Disponibilidade Restaurante</h2>
                <p class="mt-2 text-slate-300">{{ $product->available_restaurant ? 'Sim' : 'Não' }}</p>
            </div>
            <div>
                <h2 class="text-sm font-semibold uppercase tracking-[0.2em] text-slate-400">Disponibilidade Supermercado</h2>
                <p class="mt-2 text-slate-300">{{ $product->available_supermarket ? 'Sim' : 'Não' }}</p>
            </div>
        </div>

        <div class="flex flex-wrap gap-3">
            <a href="{{ route('admin.products.edit', $product) }}" class="rounded-2xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white hover:bg-blue-500">Editar Produto</a>
            <form action="{{ route('admin.products.destroy', $product) }}" method="POST" onsubmit="return confirm('Tem certeza que deseja remover este produto?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="rounded-2xl bg-red-600 px-5 py-3 text-sm font-semibold text-white hover:bg-red-500">Remover Produto</button>
            </form>
        </div>
    </div>
</div>
@endsection
