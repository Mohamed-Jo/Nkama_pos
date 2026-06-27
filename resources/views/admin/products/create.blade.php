@extends('layouts.admin')

@section('content')
<div class="form-container">
    <div class="form-card">
        <div class="form-header">
            <h1>Novo Produto</h1>
            <p>Preencha os dados do produto antes de gravar.</p>
        </div>

        {{-- Alertas de Erro (Mantendo seu estilo, mas padronizado) --}}
        @if ($errors->any())
            <div class="error-box">
                <p><strong>Atenção:</strong> Verifique os campos abaixo.</p>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.products.store') }}" class="form-grid">
            @csrf

            <div class="form-group">
                <label>Nome do Artigo</label>
                <input name="name" type="text" value="{{ old('name') }}" placeholder="Ex: Produto Exemplo" required>
            </div>

            <div class="form-group">
                <label>Categoria</label>
                <select name="category_id" required>
                    <option value="">Selecione a categoria</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="row-2">
                <div class="form-group">
                    <label>Preço de Venda com IVA incluído (AOA)</label>
                    <input name="price" type="number" step="0.01" value="{{ old('price') }}" placeholder="0.00" required>
                </div>
                <div class="form-group">
                    <label>IVA do Produto (%)</label>
                    <input name="tax_rate" type="number" min="0" max="100" step="0.01" value="{{ old('tax_rate', $defaultTaxRate) }}" placeholder="0.00" required>
                </div>
            </div>

            <div class="row-2">
                <div class="form-group">
                    <label>Quantidade em Stock</label>
                    <input name="stock" type="number" value="{{ old('stock') }}" placeholder="0" required>
                </div>
                <div class="form-group">
                    <label>Código de Barras</label>
                    <input name="barcode" type="text" value="{{ old('barcode') }}" placeholder="Opcional">
                </div>
            </div>

            <div class="form-group">
                <label>Descrição</label>
                <textarea name="description" rows="3">{{ old('description') }}</textarea>
            </div>

            <div class="row-2">
                <label class="checkbox-box">
                    <input type="checkbox" name="available_restaurant" value="1" {{ old('available_restaurant') ? 'checked' : '' }}>
                    <span>Restaurante</span>
                </label>
                <label class="checkbox-box">
                    <input type="checkbox" name="available_supermarket" value="1" {{ old('available_supermarket') ? 'checked' : '' }}>
                    <span>Supermercado</span>
                </label>
            </div>

            <div class="form-actions">
                <a href="{{ route('admin.products.index') }}" class="btn-cancel">Cancelar</a>
                <button type="submit" class="btn-save">Guardar Produto</button>
            </div>
        </form>
    </div>
</div>

<style>
    /* Reutilizando as classes padronizadas do sistema */
    .form-container { max-width: 600px; margin: 0 auto; padding: 20px; }
    .form-card { background: #0f172a; border: 1px solid #1e293b; border-radius: 20px; padding: 40px; }
    .form-header { margin-bottom: 30px; border-bottom: 1px solid #1e293b; padding-bottom: 20px; }
    .form-header h1 { font-size: 1.5rem; color: #fff; margin: 0; }
    .form-header p { color: #64748b; font-size: 0.9rem; margin-top: 5px; }

    .form-group { margin-bottom: 20px; }
    .form-group label { display: block; font-size: 0.8rem; color: #94a3b8; margin-bottom: 8px; font-weight: 600; }
    .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 12px 16px; background: #020617; border: 1px solid #334155; border-radius: 10px; color: #fff; }
    
    .row-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
    .checkbox-box { display: flex; align-items: center; gap: 10px; background: #020617; padding: 12px 16px; border-radius: 10px; border: 1px solid #334155; cursor: pointer; color: #94a3b8; font-size: 0.9rem; }
    .checkbox-box input[type="checkbox"] { width: 18px; height: 18px; accent-color: #ea580c; cursor: pointer; }

    .error-box { background: #4c0519; border: 1px solid #9f1239; color: #fda4af; padding: 16px; border-radius: 12px; margin-bottom: 20px; font-size: 0.85rem; }
    .error-box ul { margin: 10px 0 0 20px; }

    .form-actions { display: flex; gap: 15px; margin-top: 30px; border-top: 1px solid #1e293b; padding-top: 20px; }
    .btn-cancel { width: 30%; text-align: center; padding: 12px; border-radius: 10px; border: 1px solid #334155; color: #94a3b8; text-decoration: none; }
    .btn-save { width: 70%; padding: 12px; border-radius: 10px; border: none; background: #ea580c; color: #fff; font-weight: bold; cursor: pointer; }
</style>
@endsection
