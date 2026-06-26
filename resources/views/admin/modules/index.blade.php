@extends('layouts.admin')

@section('page-title', 'Módulos')

@section('content')
    <style>
        .modules-wrap {
            max-width: 860px;
        }

        .modules-alert {
            background: rgba(16, 185, 129, 0.12);
            border: 1px solid rgba(16, 185, 129, 0.24);
            border-radius: 8px;
            color: #86efac;
            margin-bottom: 14px;
            padding: 10px 12px;
        }

        .modules-panel {
            background: rgba(15, 23, 42, 0.72);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 8px;
            padding: 18px;
        }

        .module-row {
            align-items: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
            display: flex;
            justify-content: space-between;
            gap: 18px;
            padding: 16px 0;
        }

        .module-row:last-child {
            border-bottom: none;
        }

        .module-title {
            color: #fff;
            font-size: 15px;
            font-weight: 800;
            margin-bottom: 4px;
        }

        .module-text {
            color: #94a3b8;
            font-size: 13px;
            line-height: 1.45;
        }

        .module-toggle {
            align-items: center;
            color: #cbd5e1;
            display: inline-flex;
            font-size: 13px;
            font-weight: 800;
            gap: 8px;
            white-space: nowrap;
        }

        .module-toggle input {
            height: 18px;
            width: 18px;
        }

        .module-actions {
            display: flex;
            justify-content: flex-end;
            margin-top: 18px;
        }

        .module-btn {
            background: #f97316;
            border: none;
            border-radius: 8px;
            color: #111827;
            cursor: pointer;
            font-weight: 900;
            min-height: 42px;
            padding: 0 16px;
        }
    </style>

    <div class="modules-wrap">
        @if(session('success'))
            <div class="modules-alert">{{ session('success') }}</div>
        @endif

        <form method="POST" action="{{ route('admin.modules.update') }}" class="modules-panel">
            @csrf
            @method('PUT')

            <div class="module-row">
                <div>
                    <div class="module-title">Restaurante</div>
                    <div class="module-text">Ativa salão, mesas, categorias de restaurante e pedidos por mesa.</div>
                </div>
                <label class="module-toggle">
                    <input type="checkbox" name="modules[restaurant]" value="1" @checked($modules['restaurant'] ?? false)>
                    Ativo
                </label>
            </div>

            <div class="module-row">
                <div>
                    <div class="module-title">Supermercado</div>
                    <div class="module-text">Ativa caixa de retalho, leitura por código de barras e grelha de produtos.</div>
                </div>
                <label class="module-toggle">
                    <input type="checkbox" name="modules[supermarket]" value="1" @checked($modules['supermarket'] ?? false)>
                    Ativo
                </label>
            </div>

            <div class="module-actions">
                <button type="submit" class="module-btn">Guardar módulos</button>
            </div>
        </form>
    </div>
@endsection
