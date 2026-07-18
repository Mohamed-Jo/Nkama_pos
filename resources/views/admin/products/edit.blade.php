@extends('layouts.admin')

@section('content')
<div class="form-container">
    <div class="form-card">
        <div class="form-header">
            <h1>Editar Produto</h1>
            <p>Atualize dados comerciais, disponibilidade e parametros de stock.</p>
        </div>

        @if ($errors->any())
            <div class="error-box">
                <p><strong>Atencao:</strong> Verifique os campos abaixo.</p>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.products.update', $product) }}" class="form-grid">
            @csrf @method('PUT')

            <div class="row-2">
                <div class="form-group">
                    <label>Nome do Artigo</label>
                    <input name="name" type="text" value="{{ old('name', $product->name) }}" required>
                </div>
                <div class="form-group">
                    <label>Categoria</label>
                    <select name="category_id" required>
                        <option value="">Selecione uma categoria</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ old('category_id', $product->category_id) == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="row-3">
                <div class="form-group">
                    <label>Preco de compra (AOA)</label>
                    <input name="purchase_price" type="number" step="0.01" value="{{ old('purchase_price', $product->purchase_price ?? 0) }}">
                </div>
                <div class="form-group">
                    <label>Preco de venda com IVA (AOA)</label>
                    <input name="price" type="number" step="0.01" value="{{ old('price', $product->selling_price) }}" required>
                </div>
                <div class="form-group">
                    <label>IVA do Produto (%)</label>
                    <input name="tax_rate" type="number" min="0" max="100" step="0.01" value="{{ old('tax_rate', $product->tax_rate ?? 0) }}" required>
                </div>
            </div>

            <div class="row-3">
                <div class="form-group">
                    <label>Quantidade em Stock</label>
                    <input name="stock" type="number" value="{{ old('stock', $product->stock_quantity) }}" min="0" required>
                </div>
                <div class="form-group">
                    <label>Stock minimo</label>
                    <input name="minimum_stock" type="number" value="{{ old('minimum_stock', $product->minimum_stock ?? 5) }}" min="0" required>
                </div>
                <div class="form-group">
                    <label>Stock ideal</label>
                    <input name="target_stock" type="number" value="{{ old('target_stock', $product->target_stock ?? 0) }}" min="0">
                </div>
            </div>

            <div class="row-3">
                <div class="form-group">
                    <label>Unidade</label>
                    <input name="unit" type="text" value="{{ old('unit', $product->unit ?? 'un') }}" maxlength="20" required>
                </div>
                <div class="form-group">
                    <label>Codigo de Barras</label>
                    <input name="barcode" type="text" value="{{ old('barcode', $product->barcode) }}">
                </div>
                <div class="form-group">
                    <label>Localizacao</label>
                    <input name="stock_location" type="text" value="{{ old('stock_location', $product->stock_location) }}" placeholder="Ex: Loja, Armazem, Bar">
                </div>
            </div>

            <div class="form-group">
                <label>Descricao</label>
                <textarea name="description" rows="3">{{ old('description', $product->description) }}</textarea>
            </div>

            <div class="row-4">
                <label class="checkbox-box"><input type="checkbox" name="status" value="1" {{ old('status', $product->status ?? true) ? 'checked' : '' }}><span>Ativo</span></label>
                <label class="checkbox-box"><input type="checkbox" name="track_stock" value="1" {{ old('track_stock', $product->track_stock ?? true) ? 'checked' : '' }}><span>Controlar stock</span></label>
                <label class="checkbox-box"><input type="checkbox" name="available_restaurant" value="1" {{ old('available_restaurant', $product->available_restaurant ?? false) ? 'checked' : '' }}><span>Restaurante</span></label>
                <label class="checkbox-box"><input type="checkbox" name="available_supermarket" value="1" {{ old('available_supermarket', $product->available_supermarket ?? false) ? 'checked' : '' }}><span>Supermercado</span></label>
            </div>

            <div class="form-actions">
                <a href="{{ route('admin.products.index') }}" class="btn-cancel">Cancelar</a>
                <button type="submit" class="btn-save">Gravar Alteracoes</button>
            </div>
        </form>
    </div>
</div>

@include('admin.products.partials.form-style')
@endsection