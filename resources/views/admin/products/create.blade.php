@extends('layouts.admin')

@section('content')
<div class="space-y-6 max-w-3xl">
    <div class="rounded-3xl border border-slate-800 bg-slate-900 p-8 shadow-xl shadow-black/20">
        <div class="mb-8">
            <h1 class="text-3xl font-semibold text-white">Novo Produto</h1>
            <p class="mt-2 text-sm text-slate-400">Preencha os dados do produto antes de gravar.</p>
        </div>

        @if(session('success'))
            <div class="mb-6 rounded-2xl border border-emerald-500/20 bg-emerald-500/10 p-4 text-emerald-200">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-6 rounded-2xl border border-rose-500/20 bg-rose-500/10 p-4 text-rose-200">
                <p class="font-semibold">Atenção:</p>
                <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-rose-100">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.products.store') }}" class="space-y-6">
            @csrf

            <div>
                <label for="name" class="block text-sm font-semibold text-slate-300">Nome do Artigo</label>
                <input id="name" name="name" value="{{ old('name') }}" placeholder="Ex: Produto Exemplo" class="mt-2 w-full rounded-2xl border border-slate-700 bg-slate-950 px-4 py-3 text-white outline-none focus:border-orange-500" required>
            </div>

            <div>
                <label for="category_id" class="block text-sm font-semibold text-slate-300">Categoria</label>
                <select id="category_id" name="category_id" class="mt-2 w-full rounded-2xl border border-slate-700 bg-slate-950 px-4 py-3 text-white outline-none focus:border-orange-500" required>
                    <option value="">Selecione a categoria</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="grid gap-6 md:grid-cols-2">
                <div>
                    <label for="price" class="block text-sm font-semibold text-slate-300">Preço de Venda (AOA)</label>
                    <input id="price" name="price" type="number" step="0.01" value="{{ old('price') }}" placeholder="0.00" class="mt-2 w-full rounded-2xl border border-slate-700 bg-slate-950 px-4 py-3 text-white outline-none focus:border-orange-500" required>
                </div>
                <div>
                    <label for="stock" class="block text-sm font-semibold text-slate-300">Quantidade em Stock</label>
                    <input id="stock" name="stock" type="number" value="{{ old('stock') }}" placeholder="0" class="mt-2 w-full rounded-2xl border border-slate-700 bg-slate-950 px-4 py-3 text-white outline-none focus:border-orange-500" required>
                </div>
            </div>

            <div>
                <label for="barcode" class="block text-sm font-semibold text-slate-300">Código de Barras</label>
                <input id="barcode" name="barcode" value="{{ old('barcode') }}" placeholder="Opcional" class="mt-2 w-full rounded-2xl border border-slate-700 bg-slate-950 px-4 py-3 text-white outline-none focus:border-orange-500">
            </div>

            <div>
                <label for="description" class="block text-sm font-semibold text-slate-300">Descrição</label>
                <textarea id="description" name="description" rows="4" class="mt-2 w-full rounded-2xl border border-slate-700 bg-slate-950 px-4 py-3 text-white outline-none focus:border-orange-500">{{ old('description') }}</textarea>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <label class="flex items-center gap-3 rounded-2xl border border-slate-700 bg-slate-950 px-4 py-3">
                    <input type="checkbox" name="available_restaurant" value="1" {{ old('available_restaurant') ? 'checked' : '' }} class="h-4 w-4 text-orange-500 focus:ring-orange-500">
                    <span class="text-sm text-slate-300">Disponível no Restaurante</span>
                </label>
                <label class="flex items-center gap-3 rounded-2xl border border-slate-700 bg-slate-950 px-4 py-3">
                    <input type="checkbox" name="available_supermarket" value="1" {{ old('available_supermarket') ? 'checked' : '' }} class="h-4 w-4 text-orange-500 focus:ring-orange-500">
                    <span class="text-sm text-slate-300">Disponível no Supermercado</span>
                </label>
            </div>

            <button type="submit" class="w-full rounded-2xl bg-orange-500 px-5 py-3 text-sm font-semibold text-white transition hover:bg-orange-400">Guardar Produto</button>
        </form>
    </div>
</div>
@endsection
