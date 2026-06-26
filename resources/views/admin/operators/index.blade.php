@extends('layouts.admin')

@section('page-title', 'Operadores')

@section('content')
    <style>
        .operators-grid {
            display: grid;
            grid-template-columns: 360px 1fr;
            gap: 18px;
            align-items: start;
        }

        .operator-panel {
            background: rgba(15, 23, 42, 0.72);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 8px;
            padding: 18px;
        }

        .operator-panel h2 {
            color: #fff;
            font-size: 16px;
            margin: 0 0 14px;
        }

        .operator-field {
            margin-bottom: 12px;
        }

        .operator-field label {
            color: #94a3b8;
            display: block;
            font-size: 11px;
            font-weight: 800;
            margin-bottom: 5px;
            text-transform: uppercase;
        }

        .operator-field input,
        .operator-field select {
            background: #070a12;
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 8px;
            box-sizing: border-box;
            color: #fff;
            margin: 0;
            padding: 10px 12px;
            width: 100%;
        }

        .operator-check {
            align-items: center;
            color: #cbd5e1;
            display: flex;
            gap: 8px;
            font-size: 13px;
            margin-bottom: 12px;
        }

        .operator-check input {
            width: auto;
        }

        .operator-btn {
            background: #f97316;
            border: none;
            border-radius: 8px;
            color: #111827;
            cursor: pointer;
            font-weight: 800;
            min-height: 40px;
            padding: 0 14px;
        }

        .operator-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        .operator-table th,
        .operator-table td {
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            padding: 12px;
            text-align: left;
            vertical-align: top;
        }

        .operator-table th {
            color: #94a3b8;
            font-size: 11px;
            text-transform: uppercase;
        }

        .operator-muted {
            color: #94a3b8;
            font-size: 12px;
            margin-top: 3px;
        }

        .operator-status {
            border-radius: 999px;
            display: inline-flex;
            font-size: 11px;
            font-weight: 800;
            padding: 4px 8px;
        }

        .operator-status.active {
            background: rgba(16, 185, 129, 0.14);
            color: #34d399;
        }

        .operator-status.inactive {
            background: rgba(239, 68, 68, 0.14);
            color: #f87171;
        }

        .operator-inline-form {
            display: grid;
            gap: 8px;
            grid-template-columns: repeat(2, minmax(130px, 1fr));
        }

        .operator-inline-form .wide {
            grid-column: 1 / -1;
        }

        .operator-alert {
            background: rgba(16, 185, 129, 0.12);
            border: 1px solid rgba(16, 185, 129, 0.24);
            border-radius: 8px;
            color: #86efac;
            margin-bottom: 14px;
            padding: 10px 12px;
        }

        .operator-error {
            background: rgba(239, 68, 68, 0.12);
            border: 1px solid rgba(239, 68, 68, 0.24);
            border-radius: 8px;
            color: #fecaca;
            margin-bottom: 14px;
            padding: 10px 12px;
        }

        .recovery-box {
            background: rgba(249, 115, 22, 0.12);
            border: 1px solid rgba(249, 115, 22, 0.28);
            border-radius: 8px;
            color: #fed7aa;
            margin-bottom: 14px;
            padding: 12px;
        }

        .recovery-code {
            color: #fff;
            display: block;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
            font-size: 18px;
            font-weight: 900;
            letter-spacing: 1px;
            margin-top: 6px;
        }

        @media (max-width: 1050px) {
            .operators-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    @if(session('success'))
        <div class="operator-alert">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="operator-error">{{ $errors->first() }}</div>
    @endif

    @if(session('recovery_code'))
        <div class="recovery-box">
            Código de recuperação de {{ session('recovery_operator') }}. Guarde este código em local seguro; ele não volta a aparecer.
            <span class="recovery-code">{{ session('recovery_code') }}</span>
        </div>
    @endif

    <div class="operators-grid">
        <section class="operator-panel">
            <h2>Novo operador</h2>
            <form method="POST" action="{{ route('admin.operators.store') }}">
                @csrf

                <div class="operator-field">
                    <label>Nome</label>
                    <input name="name" value="{{ old('name') }}" required>
                </div>

                <div class="operator-field">
                    <label>Email</label>
                    <input name="email" type="email" value="{{ old('email') }}" required>
                </div>

                <div class="operator-field">
                    <label>PIN de acesso</label>
                    <input name="pin" type="password" inputmode="numeric" minlength="8" maxlength="8" required>
                </div>

                <div class="operator-field">
                    <label>Confirmar PIN</label>
                    <input name="pin_confirmation" type="password" inputmode="numeric" minlength="8" maxlength="8" required>
                </div>

                <div class="operator-field">
                    <label>Senha opcional</label>
                    <input name="password" type="password" autocomplete="new-password">
                </div>

                <div class="operator-field">
                    <label>Confirmar senha</label>
                    <input name="password_confirmation" type="password" autocomplete="new-password">
                </div>

                <div class="operator-field">
                    <label>Função</label>
                    <select name="role" required>
                        <option value="super_user">Super-user</option>
                        <option value="cashier">Caixa</option>
                        <option value="manager">Gestor</option>
                        <option value="admin">Administrador</option>
                    </select>
                </div>

                <label class="operator-check">
                    <input type="checkbox" name="active" value="1" checked>
                    Operador ativo
                </label>

                <button class="operator-btn" type="submit">Registar operador</button>
            </form>
        </section>

        <section class="operator-panel">
            <h2>Operadores registados</h2>

            <table class="operator-table">
                <thead>
                    <tr>
                        <th>Operador</th>
                        <th>Estado</th>
                        <th>Atualizar</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($operators as $operator)
                        <tr>
                            <td>
                                <strong style="color:#fff;">{{ $operator->name }}</strong>
                                <div class="operator-muted">{{ $operator->email }}</div>
                                <div class="operator-muted">Função: {{ $operator->role }}</div>
                            </td>
                            <td>
                                <span class="operator-status {{ $operator->active ? 'active' : 'inactive' }}">
                                    {{ $operator->active ? 'Ativo' : 'Inativo' }}
                                </span>
                            </td>
                            <td>
                                <form method="POST" action="{{ route('admin.operators.update', $operator) }}" class="operator-inline-form">
                                    @csrf
                                    @method('PUT')

                                    <input name="name" value="{{ $operator->name }}" required>
                                    <input name="email" type="email" value="{{ $operator->email }}" required>
                                    <select name="role">
                                        <option value="super_user" @selected($operator->role === 'super_user')>Super-user</option>
                                        <option value="cashier" @selected($operator->role === 'cashier')>Caixa</option>
                                        <option value="manager" @selected($operator->role === 'manager')>Gestor</option>
                                        <option value="admin" @selected($operator->role === 'admin')>Administrador</option>
                                    </select>
                                    <label class="operator-check">
                                        <input type="checkbox" name="active" value="1" @checked($operator->active)>
                                        Ativo
                                    </label>
                                    <input name="pin" type="password" inputmode="numeric" minlength="8" maxlength="8" placeholder="Novo PIN">
                                    <input name="pin_confirmation" type="password" inputmode="numeric" minlength="8" maxlength="8" placeholder="Confirmar PIN">
                                    <input name="password" class="wide" type="password" placeholder="Nova senha opcional">
                                    <input name="password_confirmation" class="wide" type="password" placeholder="Confirmar senha">
                                    <button class="operator-btn wide" type="submit">Guardar alterações</button>
                                </form>

                                <form method="POST" action="{{ route('admin.operators.recovery-code', $operator) }}" style="margin-top: 8px;">
                                    @csrf
                                    <button class="operator-btn" type="submit" style="background: rgba(249, 115, 22, .14); color: #fdba74; border: 1px solid rgba(249, 115, 22, .3);">
                                        Gerar código de recuperação
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="operator-muted">Nenhum operador registado.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div style="margin-top: 12px;">
                {{ $operators->links() }}
            </div>
        </section>
    </div>
@endsection
