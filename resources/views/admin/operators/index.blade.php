@extends('layouts.admin')

@section('page-title', 'Operadores')

@section('content')
    <style>
        .operators-grid {
            align-items: start;
            display: grid;
            gap: 18px;
            grid-template-columns: 340px minmax(0, 1fr);
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
            margin: 0;
        }

        .operator-panel-head {
            align-items: center;
            display: flex;
            gap: 12px;
            justify-content: space-between;
            margin-bottom: 14px;
        }

        .operator-count {
            color: #94a3b8;
            font-size: 12px;
            font-weight: 800;
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
        .operator-field select,
        .operator-filter input,
        .operator-filter select,
        .operator-modal input,
        .operator-modal select {
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

        .operator-btn,
        .operator-action {
            align-items: center;
            border: 1px solid transparent;
            border-radius: 8px;
            cursor: pointer;
            display: inline-flex;
            font-size: 12px;
            font-weight: 900;
            justify-content: center;
            min-height: 34px;
            padding: 0 12px;
            text-decoration: none;
            white-space: nowrap;
        }

        .operator-btn {
            background: #f97316;
            color: #111827;
            min-height: 40px;
            width: 100%;
        }

        .operator-action {
            background: rgba(255, 255, 255, 0.04);
            border-color: rgba(255, 255, 255, 0.08);
            color: #e2e8f0;
        }

        .operator-action.primary {
            background: rgba(249, 115, 22, 0.14);
            border-color: rgba(249, 115, 22, 0.28);
            color: #fdba74;
        }

        .operator-action:hover {
            border-color: rgba(249, 115, 22, 0.38);
            color: #fdba74;
        }

        .operator-filter {
            align-items: end;
            display: grid;
            gap: 10px;
            grid-template-columns: minmax(180px, 1fr) 150px auto auto;
            margin-bottom: 14px;
        }

        .operator-table-wrap {
            overflow-x: auto;
        }

        .operator-table {
            min-width: 860px;
            width: 100%;
        }

        .operator-name {
            color: #fff;
            font-weight: 900;
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
            font-weight: 900;
            padding: 5px 9px;
        }

        .operator-status.active {
            background: rgba(16, 185, 129, 0.14);
            color: #34d399;
        }

        .operator-status.inactive {
            background: rgba(239, 68, 68, 0.14);
            color: #f87171;
        }

        .operator-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            justify-content: flex-end;
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

        .operator-modal {
            position: fixed;
            inset: 0;
            z-index: 10000;
        }

        .operator-modal-backdrop {
            background: rgba(0, 0, 0, .72);
            inset: 0;
            position: absolute;
        }

        .operator-modal-box {
            background: #0f172a;
            border: 1px solid rgba(255, 255, 255, .08);
            border-radius: 10px;
            left: 50%;
            max-height: calc(100vh - 40px);
            max-width: 520px;
            overflow-y: auto;
            padding: 18px;
            position: absolute;
            top: 50%;
            transform: translate(-50%, -50%);
            width: min(520px, calc(100vw - 28px));
        }

        .operator-modal-head {
            align-items: center;
            display: flex;
            justify-content: space-between;
            margin-bottom: 14px;
        }

        .operator-modal-title {
            color: #fff;
            font-size: 16px;
            font-weight: 900;
            margin: 0;
        }

        .operator-modal-grid {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .operator-modal-grid .wide {
            grid-column: 1 / -1;
        }

        @media (max-width: 1050px) {
            .operators-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 780px) {
            .operator-filter,
            .operator-modal-grid {
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
            Codigo de recuperacao de {{ session('recovery_operator') }}. Guarde este codigo em local seguro; ele nao volta a aparecer.
            <span class="recovery-code">{{ session('recovery_code') }}</span>
        </div>
    @endif

    <div class="operators-grid">
        <section class="operator-panel">
            <h2>Novo operador</h2>
            <form method="POST" action="{{ route('admin.operators.store') }}" style="margin-top:14px;">
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
                    <label>Funcao</label>
                    <select name="role" required>
                        @foreach($roleOptions as $role => $label)
                            <option value="{{ $role }}">{{ $label }}</option>
                        @endforeach
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
            <div class="operator-panel-head">
                <h2>Operadores registados</h2>
                <span class="operator-count">{{ $operators->total() }} no total</span>
            </div>

            <form method="GET" action="{{ route('admin.operators.index') }}" class="operator-filter">
                <div class="operator-field" style="margin-bottom:0;">
                    <label>Pesquisar</label>
                    <input name="search" value="{{ request('search') }}" placeholder="Nome, email ou funcao">
                </div>
                <div class="operator-field" style="margin-bottom:0;">
                    <label>Estado</label>
                    <select name="status">
                        <option value="">Todos</option>
                        <option value="active" @selected(request('status') === 'active')>Ativos</option>
                        <option value="inactive" @selected(request('status') === 'inactive')>Inativos</option>
                    </select>
                </div>
                <button class="operator-action primary" type="submit">Filtrar</button>
                <a class="operator-action" href="{{ route('admin.operators.index') }}">Limpar</a>
            </form>

            <div class="operator-table-wrap">
                <table class="operator-table">
                    <thead>
                        <tr>
                            <th style="width:70px;">#</th>
                            <th>Operador</th>
                            <th>Funcao</th>
                            <th>Estado</th>
                            <th>Registo</th>
                            <th style="text-align:right;">Acoes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($operators as $operator)
                            <tr>
                                <td>{{ $operators->firstItem() + $loop->index }}</td>
                                <td>
                                    <div class="operator-name">{{ $operator->name }}</div>
                                    <div class="operator-muted">{{ $operator->email }}</div>
                                </td>
                                <td>{{ $roleOptions[$operator->role] ?? $operator->role }}</td>
                                <td>
                                    <span class="operator-status {{ $operator->active ? 'active' : 'inactive' }}">
                                        {{ $operator->active ? 'Ativo' : 'Inativo' }}
                                    </span>
                                </td>
                                <td>{{ optional($operator->created_at)->format('d/m/Y H:i') ?? '-' }}</td>
                                <td>
                                    <div class="operator-actions">
                                        <button class="operator-action primary js-edit-operator"
                                            type="button"
                                            data-update-url="{{ route('admin.operators.update', $operator) }}"
                                            data-name="{{ e($operator->name) }}"
                                            data-email="{{ e($operator->email) }}"
                                            data-role="{{ e($operator->role) }}"
                                            data-active="{{ $operator->active ? '1' : '0' }}">
                                            Editar
                                        </button>

                                        <form method="POST" action="{{ route('admin.operators.recovery-code', $operator) }}">
                                            @csrf
                                            <button class="operator-action" type="submit">Recuperacao</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" style="text-align:center; color:#94a3b8; padding:28px;">Nenhum operador encontrado.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div style="margin-top: 12px;">
                {{ $operators->links() }}
            </div>
        </section>
    </div>

    <div id="operator-edit-modal" class="operator-modal hidden" aria-hidden="true">
        <div class="operator-modal-backdrop" data-close-operator-modal></div>
        <div class="operator-modal-box" role="dialog" aria-modal="true" aria-labelledby="operator-edit-title">
            <div class="operator-modal-head">
                <h2 id="operator-edit-title" class="operator-modal-title">Editar operador</h2>
                <button class="operator-action" type="button" data-close-operator-modal>Fechar</button>
            </div>

            <form id="operator-edit-form" method="POST" action="">
                @csrf
                @method('PUT')

                <div class="operator-modal-grid">
                    <div class="operator-field">
                        <label>Nome</label>
                        <input id="edit-operator-name" name="name" required>
                    </div>
                    <div class="operator-field">
                        <label>Email</label>
                        <input id="edit-operator-email" name="email" type="email" required>
                    </div>
                    <div class="operator-field">
                        <label>Funcao</label>
                        <select id="edit-operator-role" name="role" required>
                            @foreach($roleOptions as $role => $label)
                                <option value="{{ $role }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <label class="operator-check" style="margin-top:25px;">
                        <input id="edit-operator-active" type="checkbox" name="active" value="1">
                        Operador ativo
                    </label>
                    <div class="operator-field">
                        <label>Novo PIN</label>
                        <input name="pin" type="password" inputmode="numeric" minlength="8" maxlength="8" placeholder="Opcional">
                    </div>
                    <div class="operator-field">
                        <label>Confirmar PIN</label>
                        <input name="pin_confirmation" type="password" inputmode="numeric" minlength="8" maxlength="8" placeholder="Opcional">
                    </div>
                    <div class="operator-field wide">
                        <label>Nova senha</label>
                        <input name="password" type="password" autocomplete="new-password" placeholder="Opcional">
                    </div>
                    <div class="operator-field wide">
                        <label>Confirmar senha</label>
                        <input name="password_confirmation" type="password" autocomplete="new-password" placeholder="Opcional">
                    </div>
                </div>

                <button class="operator-btn" type="submit" style="margin-top:4px;">Guardar alteracoes</button>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const modal = document.getElementById('operator-edit-modal');
            const form = document.getElementById('operator-edit-form');
            const nameInput = document.getElementById('edit-operator-name');
            const emailInput = document.getElementById('edit-operator-email');
            const roleInput = document.getElementById('edit-operator-role');
            const activeInput = document.getElementById('edit-operator-active');

            function closeModal() {
                modal.classList.add('hidden');
                modal.setAttribute('aria-hidden', 'true');
            }

            document.querySelectorAll('.js-edit-operator').forEach(function (button) {
                button.addEventListener('click', function () {
                    form.action = button.dataset.updateUrl;
                    nameInput.value = button.dataset.name || '';
                    emailInput.value = button.dataset.email || '';
                    roleInput.value = button.dataset.role || '';
                    activeInput.checked = button.dataset.active === '1';
                    form.querySelectorAll('input[type="password"]').forEach(function (input) {
                        input.value = '';
                    });
                    modal.classList.remove('hidden');
                    modal.setAttribute('aria-hidden', 'false');
                    nameInput.focus();
                });
            });

            document.querySelectorAll('[data-close-operator-modal]').forEach(function (button) {
                button.addEventListener('click', closeModal);
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
                    closeModal();
                }
            });
        });
    </script>
@endsection
