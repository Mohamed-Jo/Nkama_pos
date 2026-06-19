@extends('layouts.admin')

@section('content')
<div class="space-y-6 max-w-3xl mx-auto">
    <div class="rounded-3xl border border-slate-800 bg-slate-900 p-8 shadow-xl shadow-black/20">
        
        <div class="mb-8">
            <h1 class="text-3xl font-semibold text-white">Editar Produto</h1>
            <p class="mt-2 text-sm text-slate-400">Atualize as informações do produto no catálogo.</p>
        </div>

        @if ($errors->any())
            <div class="mb-6 rounded-2xl border border-rose-500/20 bg-rose-500/10 p-4 text-rose-200">
                <p class="font-semibold text-sm">Por favor, corrija os erros assinalados no formulário abaixo.</p>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.products.update', $product) }}" class="space-y-6">
            @csrf
            @method('PUT')

            <div>
                <label for="name" class="block text-sm font-semibold text-slate-300">Nome do Artigo</label>
                <input id="name" name="name" type="text" 
                    value="{{ old('name', $product->name) }}" 
                    class="mt-2 w-full rounded-2xl border bg-slate-950 px-4 py-3 text-white outline-none transition-colors focus:border-orange-500 @error('name') border-rose-500/50 focus:border-rose-500 @else border-slate-700 @enderror" required>
                @error('name')
                    <p class="mt-1.5 text-xs text-rose-400 flex items-center gap-1">⚠️ {{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="category_id" class="block text-sm font-semibold text-slate-300">Categoria / Subcategoria</label>
                <select id="category_id" name="category_id" 
                    class="mt-2 w-full rounded-2xl border bg-slate-950 px-4 py-3 text-white outline-none transition-colors focus:border-orange-500 @error('category_id') border-rose-500/50 focus:border-rose-500 @else border-slate-700 @enderror" required>
                    <option value="">Selecione a categoria</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ old('category_id', $product->category_id) == $category->id ? 'selected' : '' }}>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
                @error('category_id')
                    <p class="mt-1.5 text-xs text-rose-400 flex items-center gap-1">⚠️ {{ $message }}</p>
                @enderror
            </div>

            <div class="grid gap-6 md:grid-cols-2">
                <div>
                    <label for="price" class="block text-sm font-semibold text-slate-300">Preço de Venda (AOA)</label>
                    <input id="price" name="price" type="number" step="0.01" 
                        value="{{ old('price', $product->price) }}" 
                        class="mt-2 w-full rounded-2xl border bg-slate-950 px-4 py-3 text-white outline-none transition-colors focus:border-orange-500 @error('price') border-rose-500/50 focus:border-rose-500 @else border-slate-700 @enderror" required>
                    @error('price')
                        <p class="mt-1.5 text-xs text-rose-400 flex items-center gap-1">⚠️ {{ $message }}</p>
                    @enderror
                </div>
                
                <div>
                    <label for="stock" class="block text-sm font-semibold text-slate-300">Quantidade em Stock</label>
                    <input id="stock" name="stock" type="number" 
                        value="{{ old('stock', $product->stock) }}" 
                        class="mt-2 w-full rounded-2xl border bg-slate-950 px-4 py-3 text-white outline-none transition-colors focus:border-orange-500 @error('stock') border-rose-500/50 focus:border-rose-500 @else border-slate-700 @enderror" required>
                    @error('stock')
                        <p class="mt-1.5 text-xs text-rose-400 flex items-center gap-1">⚠️ {{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div>
                <label for="barcode" class="block text-sm font-semibold text-slate-300">Código de Barras</label>
                <input id="barcode" name="barcode" type="text" 
                    value="{{ old('barcode', $product->barcode) }}" 
                    class="mt-2 w-full rounded-2xl border bg-slate-950 px-4 py-3 text-white outline-none transition-colors focus:border-orange-500 @error('barcode') border-rose-500/50 focus:border-rose-500 @else border-slate-700 @enderror">
                @error('barcode')
                    <p class="mt-1.5 text-xs text-rose-400 flex items-center gap-1">⚠️ {{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="description" class="block text-sm font-semibold text-slate-300">Descrição</label>
                <textarea id="description" name="description" rows="4" 
                    class="mt-2 w-full rounded-2xl border bg-slate-950 px-4 py-3 text-white outline-none transition-colors focus:border-orange-500 @error('description') border-rose-500/50 focus:border-rose-500 @else border-slate-700 @enderror">{{ old('description', $product->description) }}</textarea>
                @error('description')
                    <p class="mt-1.5 text-xs text-rose-400 flex items-center gap-1">⚠️ {{ $message }}</p>
                @enderror
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <label class="flex items-center gap-3 rounded-2xl border border-slate-700 bg-slate-950 px-4 py-3 cursor-pointer select-none group">
                    <input type="checkbox" name="available_restaurant" value="1" 
                        {{ old('available_restaurant', $product->available_restaurant) ? 'checked' : '' }} 
                        class="h-4 w-4 rounded border-slate-700 text-orange-500 focus:ring-orange-500 bg-slate-900">
                    <span class="text-sm text-slate-300 group-hover:text-white transition-colors">Disponível no Restaurante</span>
                </label>
                
                <label class="flex items-center gap-3 rounded-2xl border border-slate-700 bg-slate-950 px-4 py-3 cursor-pointer select-none group">
                    <input type="checkbox" name="available_supermarket" value="1" 
                        {{ old('available_supermarket', $product->available_supermarket) ? 'checked' : '' }} 
                        class="h-4 w-4 rounded border-slate-700 text-orange-500 focus:ring-orange-500 bg-slate-900">
                    <span class="text-sm text-slate-300 group-hover:text-white transition-colors">Disponível no Supermercado</span>
                </label>
            </div>

            <div class="flex items-center gap-4 pt-2">
                <a href="{{ route('admin.products.index') }}" class="w-1/3 text-center rounded-2xl border border-slate-700 bg-transparent px-5 py-3 text-sm font-semibold text-slate-300 transition hover:bg-slate-800 hover:text-white">
                    Cancelar
                </a>
                <button type="submit" class="w-2/3 rounded-2xl bg-orange-500 px-5 py-3 text-sm font-semibold text-white transition hover:bg-orange-400 shadow-lg shadow-orange-500/10">
                    Atualizar Produto
                </button>
            </div>
        </form>
    </div>
</div>
@endsection