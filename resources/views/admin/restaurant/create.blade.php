@extends('layouts.admin')

@section('page-title', 'Nova Mesa')

@section('content')
<div class="form-container">
    <div class="form-card">
        <div class="form-header">
            <h1>Criar Nova Mesa</h1>
            <p>Adicione uma nova mesa ao plano do restaurante.</p>
        </div>

        <form method="POST" action="{{ route('admin.restaurantMesa.store') }}" class="form-grid">
            @csrf

            <div class="form-group">
                <label>Número da Mesa</label>
                <input name="number" type="text" placeholder="Ex: 01" required>
            </div>

            <div class="form-group">
                <label>Capacidade (Lugares)</label>
                <input name="capacity" type="number" placeholder="Ex: 4" required>
            </div>

            <div class="form-actions">
                <a href="{{ route('admin.restaurantMesa.index') }}" class="btn-cancel">Cancelar</a>
                <button type="submit" class="btn-save">Criar Mesa</button>
            </div>
        </form>
    </div>
</div>

<style>
    /* Reutilizando o padrão do formulário de produtos para manter a coesão */
    .form-container { max-width: 500px; margin: 0 auto; }
    .form-card { background: #0f172a; border: 1px solid #1e293b; border-radius: 20px; padding: 40px; }
    .form-header { margin-bottom: 30px; border-bottom: 1px solid #1e293b; padding-bottom: 20px; }
    .form-header h1 { font-size: 1.5rem; color: #fff; margin: 0; }
    .form-header p { color: #64748b; font-size: 0.9rem; margin-top: 5px; }

    .form-group { margin-bottom: 20px; }
    .form-group label { display: block; font-size: 0.8rem; color: #94a3b8; margin-bottom: 8px; font-weight: 600; }
    .form-group input { 
        width: 100%; padding: 12px 16px; background: #020617; border: 1px solid #334155; 
        border-radius: 10px; color: #fff; 
    }
    .form-actions { display: flex; gap: 15px; margin-top: 30px; border-top: 1px solid #1e293b; padding-top: 20px; }
    .btn-cancel { width: 30%; text-align: center; padding: 12px; border-radius: 10px; border: 1px solid #334155; color: #94a3b8; text-decoration: none; }
    .btn-save { width: 70%; padding: 12px; border-radius: 10px; border: none; background: #ea580c; color: #fff; font-weight: bold; cursor: pointer; }
</style>
@endsection