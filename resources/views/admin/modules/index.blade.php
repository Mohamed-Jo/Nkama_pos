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
            cursor: pointer;
            display: inline-flex;
            gap: 10px;
            white-space: nowrap;
        }

        .module-toggle input {
            height: 1px;
            opacity: 0;
            position: absolute;
            width: 1px;
        }

        .switch-track {
            align-items: center;
            background: #334155;
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 999px;
            display: inline-flex;
            height: 30px;
            padding: 3px;
            transition: background .18s ease, border-color .18s ease;
            width: 56px;
        }

        .switch-knob {
            background: #e2e8f0;
            border-radius: 999px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, .26);
            display: block;
            height: 22px;
            transition: transform .18s ease, background .18s ease;
            width: 22px;
        }

        .switch-text {
            color: #94a3b8;
            font-size: 12px;
            font-weight: 900;
            letter-spacing: .04em;
            min-width: 32px;
            text-align: left;
        }

        .module-toggle input:checked + .switch-track {
            background: #10b981;
            border-color: rgba(16, 185, 129, .55);
        }

        .module-toggle input:checked + .switch-track .switch-knob {
            background: #ecfdf5;
            transform: translateX(26px);
        }

        .module-toggle input:checked ~ .switch-text {
            color: #86efac;
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
                    <span class="switch-track"><span class="switch-knob"></span></span>
                    <span class="switch-text">{{ ($modules['restaurant'] ?? false) ? 'ON' : 'OFF' }}</span>
                </label>
            </div>

            <div class="module-row">
                <div>
                    <div class="module-title">Supermercado</div>
                    <div class="module-text">Ativa caixa de retalho, leitura por código de barras e grelha de produtos.</div>
                </div>
                <label class="module-toggle">
                    <input type="checkbox" name="modules[supermarket]" value="1" @checked($modules['supermarket'] ?? false)>
                    <span class="switch-track"><span class="switch-knob"></span></span>
                    <span class="switch-text">{{ ($modules['supermarket'] ?? false) ? 'ON' : 'OFF' }}</span>
                </label>
            </div>

            <div class="module-row">
                <div>
                    <div class="module-title">Conta Corrente</div>
                    <div class="module-text">Ativa FT em conta corrente, recebimentos, pagamentos a fornecedores, extratos e relatórios de saldo.</div>
                </div>
                <label class="module-toggle">
                    <input type="checkbox" name="modules[current_account]" value="1" @checked($modules['current_account'] ?? false)>
                    <span class="switch-track"><span class="switch-knob"></span></span>
                    <span class="switch-text">{{ ($modules['current_account'] ?? false) ? 'ON' : 'OFF' }}</span>
                </label>
            </div>

            <div class="module-row">
                <div>
                    <div class="module-title">Ver Ticket</div>
                    <div class="module-text">Quando ativo, abre o ticket para conferir. Quando inativo, envia direto para impressao.</div>
                </div>
                <label class="module-toggle">
                    <input type="checkbox" name="modules[view_ticket]" value="1" @checked($modules['view_ticket'] ?? false)>
                    <span class="switch-track"><span class="switch-knob"></span></span>
                    <span class="switch-text">{{ ($modules['view_ticket'] ?? false) ? 'ON' : 'OFF' }}</span>
                </label>
            </div>

            <div class="module-row">
                <div>
                    <div class="module-title">Auditoria</div>
                    <div class="module-text">Ativa trilha de auditoria, relatorio de auditoria e fecho diario pela auditoria.</div>
                </div>
                <label class="module-toggle">
                    <input type="checkbox" name="modules[audit]" value="1" @checked($modules['audit'] ?? false)>
                    <span class="switch-track"><span class="switch-knob"></span></span>
                    <span class="switch-text">{{ ($modules['audit'] ?? false) ? 'ON' : 'OFF' }}</span>
                </label>
            </div>

            <div class="module-row">
                <div>
                    <div class="module-title">Compras</div>
                    <div class="module-text">Ativa registo de compras, entrada de mercadoria e atualizacao de stock.</div>
                </div>
                <label class="module-toggle">
                    <input type="checkbox" name="modules[purchases]" value="1" @checked($modules['purchases'] ?? false)>
                    <span class="switch-track"><span class="switch-knob"></span></span>
                    <span class="switch-text">{{ ($modules['purchases'] ?? false) ? 'ON' : 'OFF' }}</span>
                </label>
            </div>

            <div class="module-actions">
                <button type="submit" class="module-btn">Guardar módulos</button>
            </div>
        </form>
    </div>

    <script>
        document.querySelectorAll('.module-toggle input').forEach((input) => {
            const text = input.closest('.module-toggle')?.querySelector('.switch-text');

            function syncSwitchText() {
                if (text) {
                    text.textContent = input.checked ? 'ON' : 'OFF';
                }
            }

            input.addEventListener('change', syncSwitchText);
            syncSwitchText();
        });
    </script>
@endsection
