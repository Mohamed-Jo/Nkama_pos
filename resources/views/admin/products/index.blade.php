@extends('layouts.admin')

@section('content')
<div class="space-y-6">
    @if(session('success'))
        <div class="rounded-3xl border border-emerald-500/20 bg-emerald-500/10 p-4 text-emerald-200">
            {{ session('success') }}
        </div>
    @endif

    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
            <h1 class="text-3xl font-semibold text-white">Catálogo de Produtos</h1>
            <p class="mt-2 text-sm text-slate-400">Gerencie produtos, preços, stock e disponibilidade.</p>
        </div>
        <a href="{{ route('admin.products.create') }}" class="inline-flex items-center rounded-2xl bg-orange-500 px-5 py-3 text-sm font-semibold text-white transition hover:bg-orange-400">
            Novo Produto
        </a>
    </div>

    <div class="overflow-hidden rounded-3xl border border-slate-800 bg-slate-900 shadow-xl shadow-black/20">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-800 text-sm">
                <thead class="bg-slate-950/80 text-slate-400">
                    <tr>
                        <th class="px-6 py-4 text-left uppercase tracking-[0.2em]">Nome do Artigo</th>
                        <th class="px-6 py-4 text-left uppercase tracking-[0.2em]">Categoria</th>
                        <th class="px-6 py-4 text-left uppercase tracking-[0.2em]">Preço</th>
                        <th class="px-6 py-4 text-left uppercase tracking-[0.2em]">Stock</th>
                        <th class="px-6 py-4 text-left uppercase tracking-[0.2em]">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800 bg-slate-900">
                    @forelse($products as $product)
                        <tr class="hover:bg-slate-950/50">
                            <td class="px-6 py-5 font-medium text-white">{{ $product->name }}</td>
                            <td class="px-6 py-5 text-slate-300">{{ $product->category->name ?? 'Sem Categoria' }}</td>
                            <td class="px-6 py-5 text-orange-400 font-semibold">AOA {{ number_format($product->selling_price, 2) }}</td>
                            <td class="px-6 py-5 text-slate-300">
                                @if($product->stock_quantity <= 0)
                                    <span class="rounded-full bg-red-500/10 px-3 py-1 text-xs text-red-300">Esgotado</span>
                                @elseif($product->stock_quantity <= 5)
                                    <span class="rounded-full bg-amber-400/10 px-3 py-1 text-xs text-amber-200">Crítico</span>
                                @else
                                    <span class="rounded-full bg-emerald-400/10 px-3 py-1 text-xs text-emerald-200">{{ $product->stock_quantity }} un</span>
                                @endif
                            </td>
                            <td class="px-6 py-5">
                                <div class="flex flex-wrap gap-2">
                                    <a href="{{ route('admin.products.show', $product) }}" class="rounded-2xl bg-slate-800 px-3 py-2 text-xs font-semibold text-slate-100 hover:bg-slate-700">Ver</a>
                                    <a href="{{ route('admin.products.edit', $product) }}" class="rounded-2xl bg-blue-600 px-3 py-2 text-xs font-semibold text-white hover:bg-blue-500">Editar</a>
                                    <form action="{{ route('admin.products.destroy', $product) }}" method="POST" class="inline-block" onsubmit="return confirm('Tem certeza que deseja remover este produto?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-2xl bg-red-600 px-3 py-2 text-xs font-semibold text-white hover:bg-red-500">Remover</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-slate-400">Nenhum produto registado no catálogo até ao momento.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-6">
        {{ $products->links() }}
    </div>
</div>
@endsection
