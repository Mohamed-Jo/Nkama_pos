@extends('layouts.admin')

@section('content')
<div class="form-container">
    <div class="form-card">
        <div class="form-header">
            <h1>Editar Produto</h1>
            <p>Atualize os dados do artigo no sistema.</p>
        </div>

        <form method="POST" action="{{ route('admin.products.update', $product) }}" class="form-grid">
            @csrf @method('PUT')

            <div class="form-group">
                <label>Nome do Artigo</label>
                <input name="name" type="text" value="{{ old('name', $product->name) }}" required>
            </div>

            <div class="form-group">
                <label>Categoria</label>
                <select name="category_id" required>
                    <option value="">Selecione uma categoria</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ old('category_id', $product->category_id) == $category->id ? 'selected' : '' }}>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="row-2">
                <div class="form-group">
                    <label>Preço de Venda (AOA)</label>
                    <input name="price" type="number" step="0.01" value="{{ old('price', $product->selling_price) }}" required>
                </div>
                <div class="form-group">
                    <label>Quantidade em Stock</label>
                    <input name="stock" type="number" value="{{ old('stock', $product->stock_quantity) }}" required>
                </div>
            </div>

            <div class="form-group">
                <label>Código de Barras</label>
                <input name="barcode" type="text" value="{{ old('barcode', $product->barcode) }}">
            </div>

            <div class="form-group">
                <label>Descrição</label>
                <textarea name="description" rows="3">{{ old('description', $product->description) }}</textarea>
            </div>

            <div class="row-2">
                <label class="checkbox-box">
                    <input type="checkbox" name="available_restaurant" value="1" {{ old('available_restaurant', $product->available_restaurant ?? false) ? 'checked' : '' }}>
                    <span>Restaurante</span>
                </label>
                <label class="checkbox-box">
                    <input type="checkbox" name="available_supermarket" value="1" {{ old('available_supermarket', $product->available_supermarket ?? false) ? 'checked' : '' }}>
                    <span>Supermercado</span>
                </label>
            </div>

            <div class="form-actions">
                <a href="{{ route('admin.products.index') }}" class="btn-cancel">Cancelar</a>
                <button type="submit" class="btn-save">Gravar Alterações</button>
            </div>
        </form>
    </div>
</div>

<style>
    /* Estilos base do Formulário */
    .form-container { max-width: 600px; margin: 0 auto; padding: 20px; }
    .form-card { background: #0f172a; border: 1px solid #1e293b; border-radius: 20px; padding: 40px; }
    .form-header { margin-bottom: 30px; border-bottom: 1px solid #1e293b; padding-bottom: 20px; }
    .form-header h1 { font-size: 1.5rem; color: #fff; margin: 0; }
    .form-header p { color: #64748b; font-size: 0.9rem; margin-top: 5px; }

    /* Inputs */
    .form-group { margin-bottom: 20px; }
    .form-group label { display: block; font-size: 0.8rem; color: #94a3b8; margin-bottom: 8px; font-weight: 600; }
    .form-group input, .form-group select, .form-group textarea { 
        width: 100%; padding: 12px 16px; background: #020617; border: 1px solid #334155; 
        border-radius: 10px; color: #fff; transition: all 0.3s; 
    }
    .form-group input:focus, .form-group select:focus { border-color: #f97316; outline: none; }

    /* Layout */
    .row-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
/* Ajuste preciso do Checkbox */
.checkbox-box { 
    display: flex; 
    align-items: center; 
    gap: 12px; 
    background: #020617; 
    padding: 12px 16px; 
    border-radius: 10px; 
    border: 1px solid #334155; 
    cursor: pointer; 
    color: #94a3b8; 
    font-size: 0.9rem; 
}

/* Tamanho controlado do checkbox */
.checkbox-box input[type="checkbox"] {
    width: 18px !important;
    height: 18px !important;
    margin: 0;
    accent-color: #ea580c; /* Cor laranja ao selecionar */
    cursor: pointer;
}

/* Ajuste de alinhamento do texto ao lado */
.checkbox-box span {
    line-height: 1;
    margin-top: 2px;
}
    /* Ações */
    .form-actions { display: flex; gap: 15px; margin-top: 30px; border-top: 1px solid #1e293b; pt: 20px; padding-top: 20px; }
    .btn-cancel { width: 30%; text-align: center; padding: 12px; border-radius: 10px; border: 1px solid #334155; color: #94a3b8; text-decoration: none; }
    .btn-save { width: 70%; padding: 12px; border-radius: 10px; border: none; background: #ea580c; color: #fff; font-weight: bold; cursor: pointer; }
    .btn-save:hover { background: #c2410c; }
</style>
@endsection