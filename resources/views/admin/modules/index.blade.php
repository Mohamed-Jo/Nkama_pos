@extends('layouts.admin')

@section('page-title', 'Módulos')

@section('content')
    <style>
        :root {
            --primary: #f97316;
            --primary-dark: #ea580c;
            --bg-panel: rgba(15, 23, 42, 0.85);
            --border-color: rgba(255, 255, 255, 0.1);
            --text-main: #fff;
            --text-muted: #94a3b8;
            --success: #10b981;
            --success-light: #86efac;
            --success-bg: rgba(16, 185, 129, 0.15);
        }

        .modules-wrap {
            max-width: 900px;
            margin: 0 auto;
            font-family: 'Inter', sans-serif; /* Recomendado usar uma fonte moderna */
        }

        .modules-alert {
            background: var(--success-bg);
            border: 1px solid var(--success);
            border-radius: 12px;
            color: var(--success-light);
            margin-bottom: 20px;
            padding: 14px 18px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 500;
        }

        .modules-panel {
            background: var(--bg-panel);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            backdrop-filter: blur(10px);
        }

        .module-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 20px;
        }

        .module-row {
            align-items: flex-start;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            display: flex;
            justify-content: space-between;
            gap: 20px;
            padding: 20px;
            transition: background 0.2s ease, border-color 0.2s ease;
        }

        .module-row:hover {
            background: rgba(255, 255, 255, 0.03);
            border-color: rgba(255, 255, 255, 0.15);
        }

        .module-info {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .module-title {
            color: var(--text-main);
            font-size: 16px;
            font-weight: 700;
        }

        .module-text {
            color: var(--text-muted);
            font-size: 14px;
            line-height: 1.5;
        }

        .module-toggle {
            align-items: center;
            cursor: pointer;
            display: inline-flex;
            gap: 12px;
            margin-top: 5px;
        }

        .module-toggle input {
            height: 1px;
            opacity: 0;
            position: absolute;
            width: 1px;
        }

        .switch-track {
            align-items: center;
            background: #475569;
            border-radius: 999px;
            display: inline-flex;
            height: 28px;
            padding: 3px;
            transition: background .3s ease;
            width: 52px;
            position: relative;
        }

        .switch-knob {
            background: var(--text-main);
            border-radius: 999px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            display: block;
            height: 22px;
            transition: transform .3s ease, background .3s ease;
            width: 22px;
            z-index: 2;
        }

        .module-toggle input:checked + .switch-track {
            background: var(--success);
        }

        .module-toggle input:checked + .switch-track .switch-knob {
            transform: translateX(24px);
        }

        .module-actions {
            display: flex;
            justify-content: flex-end;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid var(--border-color);
        }

        .module-btn {
            background: var(--primary);
            border: none;
            border-radius: 10px;
            color: #fff;
            cursor: pointer;
            font-weight: 700;
            font-size: 15px;
            height: 48px;
            padding: 0 24px;
            transition: background .2s ease, transform 0.1s ease;
        }

        .module-btn:hover {
            background: var(--primary-dark);
        }

        .module-btn:active {
            transform: scale(0.98);
        }
    </style>

    <div class="modules-wrap">
        @if(session('success'))
            <div class="modules-alert">
                <span style="font-size: 1.2em;">✓</span> 
                {{ session('success') }}
            </div>
        @endif

        @php
            $availableModules = [
                'restaurant'      => ['title' => 'Restaurante', 'desc' => 'Ativa salão, mesas, categorias de restaurante e pedidos por mesa.'],
                'supermarket'     => ['title' => 'Supermercado', 'desc' => 'Ativa caixa de retalho, leitura por código de barras e grelha de produtos.'],
                'sales'           => ['title' => 'Vendas', 'desc' => 'Ativa emissão de vendas, histórico de faturas, tickets e relatórios de vendas.'],
                'stock'           => ['title' => 'Stocks', 'desc' => 'Ativa validação, entrada, saída e relatórios de stock.'],
                'transfers'       => ['title' => 'Transferências', 'desc' => 'Ativa transferência de contas e produtos entre mesas do restaurante.'],
                'current_account' => ['title' => 'Conta Corrente', 'desc' => 'Ativa FT em conta corrente, recebimentos, pagamentos a fornecedores, extratos e relatórios de saldo.'],
                'customer_card'   => ['title' => 'Cartão Cliente', 'desc' => 'Ativa cadastro, consulta e seleção de clientes para vendas e conta corrente.'],
                'customer_card_otp' => ['title' => 'OTP Cartao Cliente', 'desc' => 'Permite enviar OTP ao cliente para autorizar pagamento por fidelidade. Se desligado, use autorizacao do gestor.'],
                'view_ticket'     => ['title' => 'Ver Ticket', 'desc' => 'Quando ativo, abre o ticket para conferir. Quando inativo, envia direto para impressão.'],
                'audit'           => ['title' => 'Auditoria', 'desc' => 'Ativa trilha de auditoria, relatório de auditoria e fecho diário pela auditoria.'],
                'purchases'       => ['title' => 'Compras', 'desc' => 'Ativa registo de compras, entrada de mercadoria e atualização de stock.']
            ];
        @endphp

        <form method="POST" action="{{ route('admin.modules.update') }}" class="modules-panel">
            @csrf
            @method('PUT')

            <div class="module-grid">
                @foreach($availableModules as $key => $module)
                    @php $isActive = $modules[$key] ?? false; @endphp
                    <div class="module-row">
                        <div class="module-info">
                            <div class="module-title">{{ $module['title'] }}</div>
                            <div class="module-text">{{ $module['desc'] }}</div>
                        </div>
                        <label class="module-toggle">
                            <input type="checkbox" name="modules[{{ $key }}]" value="1" @checked($isActive)>
                            <span class="switch-track">
                                <span class="switch-knob"></span>
                            </span>
                        </label>
                    </div>
                @endforeach
            </div>

            <div class="module-actions">
                <button type="submit" class="module-btn">Guardar módulos</button>
            </div>
        </form>
    </div>
@endsection
