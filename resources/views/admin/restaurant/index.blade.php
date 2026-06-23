@extends('layouts.admin')

@section('page-title', 'Gerir Mesas')

@section('content')
<div class="main-grid">
    <div class="form-container">
        <div class="form-card">
            <div class="form-header">
                <h1>Criar Mesa</h1>
                <p>Adicione uma nova mesa ao sistema.</p>
            </div>

            <form method="POST" action="{{ route('admin.restaurantMesa.store') }}">
                @csrf
                <div class="form-group">
                    <label>Nome / Número</label>
                    <input name="name" type="text" placeholder="Ex: 01" required>
                </div>
                <div class="form-group">
                    <label>Capacidade</label>
                    <input name="capacity" type="number" placeholder="Ex: 4" required>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn-save" style="width: 100%;">Gravar Mesa</button>
                </div>
            </form>
        </div>
    </div>

    <div class="list-container">
        <h2 class="text-white mb-4 font-semibold text-lg">Mesas Criadas</h2>
        <div class="table-card">
            <table>
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Lugares</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($tables as $table)
                    <tr>
                        <td class="text-white font-medium">{{ $table->name }}</td>
                        <td>{{ $table->capacity }}</td>
                        <td>
                            <span class="status-badge {{ $table->status }}">{{ ucfirst($table->status) }}</span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
    .main-grid { display: grid; grid-template-columns: 350px 1fr; gap: 30px; align-items: start; }
    
    /* Reuso dos estilos do formulário */
    .form-card { background: #0f172a; border: 1px solid #1e293b; border-radius: 20px; padding: 30px; }
    .form-header { margin-bottom: 25px; border-bottom: 1px solid #1e293b; padding-bottom: 15px; }
    .form-header h1 { font-size: 1.25rem; color: #fff; margin: 0; }
    .form-group { margin-bottom: 15px; }
    .form-group label { display: block; font-size: 0.75rem; color: #94a3b8; margin-bottom: 5px; }
    .form-group input { width: 100%; padding: 10px; background: #020617; border: 1px solid #334155; border-radius: 10px; color: #fff; }
    .btn-save { padding: 12px; border-radius: 10px; border: none; background: #ea580c; color: #fff; font-weight: bold; cursor: pointer; }

    /* Estilos da lista */
    .table-card { background: #0f172a; border: 1px solid #1e293b; border-radius: 20px; overflow: hidden; }
    table { width: 100%; border-collapse: collapse; }
    th { text-align: left; padding: 15px; color: #64748b; font-size: 0.75rem; border-bottom: 1px solid #1e293b; }
    td { padding: 15px; border-bottom: 1px solid #1e293b; color: #cbd5e1; font-size: 0.9rem; }
    .status-badge { padding: 4px 10px; border-radius: 20px; font-size: 0.7rem; }
    .livre { background: #064e3b; color: #34d399; }
    .ocupada { background: #7f1d1d; color: #f87171; }
</style>
@endsection