@extends('layouts.admin')

@section('content')
<div class="max-w-2xl mx-auto px-4">
    <div class="rounded-2xl border border-slate-800 bg-slate-900 p-6 sm:p-8 shadow-xl">
        
        <div class="mb-6 border-b border-slate-800 pb-4">
            <h1 class="text-2xl font-bold text-white">Editar Produto</h1>
            <p class="text-sm text-slate-400 mt-1">Atualize os dados do artigo no sistema.</p>
        </div>

        <form method="POST" action="{{ route('admin.products.update', $product) }}" class="space-y-5">
            @csrf
            @method('PUT')

            <div>
                <label for="name" class="block text-sm font-medium text-slate-300 mb-2">Nome do Artigo</label>
                <input id="name" name="name" type="text" 
                    value="{{ old('name', $product->name) }}" 
                    class="w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 text-white outline-none focus:border-orange-500 transition-colors" required>
                @error('name')
                    <p class="mt-1 text-xs text-rose-400">⚠️ {{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="category_id" class="block text-sm font-medium text-slate-300 mb-2">Categoria</label>
                <select id="category_id" name="category_id" 
                    class="w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 text-white outline-none focus:border-orange-500 transition-colors" required>
                    <option value="">Selecione uma categoria</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ old('category_id', $product->category_id) == $category->id ? 'selected' : '' }}>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
                @error('category_id')
                    <p class="mt-1 text-xs text-rose-400">⚠️ {{ $message }}</p>
                @enderror
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="price" class="block text-sm font-medium text-slate-300 mb-2">Preço de Venda (AOA)</label>
                    <input id="price" name="price" type="number" step="0.01" 
                        value="{{ old('price', $product->price) }}" 
                        class="w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 text-white outline-none focus:border-orange-500 transition-colors" required>
                    @error('price')
                        <p class="mt-1 text-xs text-rose-400">⚠️ {{ $message }}</p>
                    @enderror
                </div>
                
                <div>
                    <label for="stock" class="block text-sm font-medium text-slate-300 mb-2">Quantidade em Stock</label>
                    <input id="stock" name="stock" type="number" 
                        value="{{ old('stock', $product->stock) }}" 
                        class="w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 text-white outline-none focus:border-orange-500 transition-colors" required>
                    @error('stock')
                        <p class="mt-1 text-xs text-rose-400">⚠️ {{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div>
                <label for="barcode" class="block text-sm font-medium text-slate-300 mb-2">Código de Barras</label>
                <input id="barcode" name="barcode" type="text" 
                    value="{{ old('barcode', $product->barcode) }}" 
                    class="w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 text-white outline-none focus:border-orange-500 transition-colors">
                @error('barcode')
                    <p class="mt-1 text-xs text-rose-400">⚠️ {{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="description" class="block text-sm font-medium text-slate-300 mb-2">Descrição</label>
                <textarea id="description" name="description" rows="3" 
                    class="w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 text-white outline-none focus:border-orange-500 transition-colors">{{ old('description', $product->description) }}</textarea>
                @error('description')
                    <p class="mt-1 text-xs text-rose-400">⚠️ {{ $message }}</p>
                @enderror
            </div>

            <div class="grid gap-4 sm:grid-cols-2 pt-2">
                <label class="flex items-center gap-3 rounded-xl border border-slate-800 bg-slate-950 px-4 py-3 cursor-pointer hover:bg-slate-900 transition-colors">
                    <input type="checkbox" name="available_restaurant" value="1" 
                        {{ old('available_restaurant', $product->available_restaurant) ? 'checked' : '' }} 
                        class="h-4 w-4 rounded border-slate-700 text-orange-500 bg-slate-900 focus:ring-0">
                    <span class="text-sm text-slate-300">Disponível no Restaurante</span>
                </label>
                
                <label class="flex items-center gap-3 rounded-xl border border-slate-800 bg-slate-950 px-4 py-3 cursor-pointer hover:bg-slate-900 transition-colors">
                    <input type="checkbox" name="available_supermarket" value="1" 
                        {{ old('available_supermarket', $product->available_supermarket) ? 'checked' : '' }} 
                        class="h-4 w-4 rounded border-slate-700 text-orange-500 bg-slate-900 focus:ring-0">
                    <span class="text-sm text-slate-300">Disponível no Supermercado</span>
                </label>
            </div>

            <div class="flex gap-3 pt-4 border-t border-slate-800">
                <a href="{{ route('admin.products.index') }}" 
                   class="w-1/3 text-center rounded-xl border border-slate-700 bg-transparent px-4 py-3 text-sm font-semibold text-slate-300 hover:bg-slate-800 hover:text-white transition-colors">
                    Cancelar
                </a>
                <button type="submit" 
                        class="w-2/3 rounded-xl bg-orange-500 px-4 py-3 text-sm font-semibold text-white hover:bg-orange-400 transition-colors shadow-lg shadow-orange-500/10">
                    Gravar Alterações
                </button>
            </div>
        </form>
    </div>
</div>
@endsection