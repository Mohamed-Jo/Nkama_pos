@extends('layouts.admin')

@section('page-title', 'Nkama ERP • POS Multi-Módulos')

@section('content')
    <style>
        .bg-slate-950 {
            background-color: #020617 !important;
        }

        .bg-slate-900 {
            background-color: #0f172a !important;
        }

        .border-slate-800 {
            border-color: #1e293b !important;
        }

        .text-slate-200 {
            color: #e2e8f0 !important;
        }

        .text-slate-400 {
            color: #94a3b8 !important;
        }

        .mode-tab {
            border: none;
            cursor: pointer;
        }

        .hidden {
            display: none !important;
        }

        .modal-backdrop {
            background-color: rgba(2, 6, 23, 0.85);
            backdrop-filter: blur(4px);
        }

        .transfer-item-row {
            align-items: center;
            background: #020617;
            border: 1px solid #1e293b;
            border-radius: 8px;
            display: grid;
            gap: 10px;
            grid-template-columns: 26px minmax(0, 1fr) 82px;
            min-height: 58px;
            padding: 9px 10px;
        }

        .transfer-item-check {
            height: 20px !important;
            width: 20px !important;
        }

        .transfer-item-name {
            color: #fff;
            font-size: 12px;
            font-weight: 800;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .transfer-item-meta {
            color: #94a3b8;
            font-size: 11px;
            line-height: 1.35;
            margin-top: 2px;
        }

        .transfer-item-qty {
            background: #0f172a !important;
            border: 1px solid #334155 !important;
            border-radius: 7px !important;
            color: #fff !important;
            margin: 0 !important;
            min-height: 36px;
            padding: 7px 8px !important;
            text-align: center;
            width: 100% !important;
        }
        
        .metodo-pagamento {
            align-items: flex-start;
            background: #020617 !important;
            border: 1px solid #1e293b;
            border-radius: 8px !important;
            color: #e2e8f0 !important;
            cursor: pointer;
            display: flex !important;
            flex-direction: column;
            gap: 4px;
            min-height: 42px;
            padding: 8px 10px !important;
            text-align: left !important;
            transition: all 0.2s ease;
        }

        .metodo-pagamento:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }

        .metodo-pagamento.active {
            background: rgba(16, 185, 129, 0.14) !important;
            border-color: #10b981 !important;
            box-shadow: 0 0 0 1px rgba(16, 185, 129, 0.35);
        }

        .metodo-pagamento::after {
            color: #94a3b8;
            content: attr(data-desc);
            display: none;
            font-size: 10px;
            font-weight: normal;
            line-height: 1.25;
        }

        .payment-icon {
            align-items: center;
            border-radius: 8px;
            display: flex;
            flex: 0 0 34px;
            font-size: 18px;
            height: 34px;
            justify-content: center;
            width: 34px;
        }

        .pos-processing-spinner { animation: pos-processing-spin .75s linear infinite; border: 2px solid rgba(2,6,23,.28); border-left-color: #020617; border-radius: 999px; display: none; flex: 0 0 16px; height: 16px; width: 16px; }
        .pos-processing-button { align-items: center; display: inline-flex; justify-content: center; gap: 8px; }
        .pos-processing-button.is-processing { cursor: wait !important; opacity: .86; }
        .pos-processing-button.is-processing .pos-processing-spinner { display: inline-block; }
        @keyframes pos-processing-spin { to { transform: rotate(360deg); } }
        .payment-chip {
            background: rgba(148, 163, 184, 0.08);
            border: 1px solid rgba(148, 163, 184, 0.18);
            border-radius: 999px;
            color: #94a3b8;
            cursor: pointer;
            font-size: 12px;
            font-weight: bold;
            min-height: 30px;
            padding: 6px 10px;
            touch-action: manipulation;
        }

        .payment-keypad {
            display: grid;
            gap: 6px;
            grid-template-columns: repeat(5, minmax(0, 1fr));
        }

        .payment-key {
            background: rgba(15, 23, 42, 0.95);
            border: 1px solid rgba(148, 163, 184, 0.2);
            border-radius: 7px;
            color: #e2e8f0;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
            min-height: 32px;
            touch-action: manipulation;
        }

        .payment-split-input {
            background: #020617;
            border: 1px solid #1e293b;
            border-radius: 7px;
            color: #fff;
            font-size: 13px;
            font-weight: bold;
            padding: 8px;
            width: 100%;
        }

        #modal-pagamento .payment-panel {
            min-height: 0;
            overflow-x: hidden !important;
            overflow-y: auto !important;
            padding: 12px !important;
            position: relative;
        }

        #modal-pagamento .payment-header {
            margin-bottom: 8px !important;
        }

        #modal-pagamento .payment-form-stack {
            gap: 8px !important;
            max-height: none;
            overflow: visible;
        }

        #modal-pagamento .payment-main-input {
            font-size: 18px !important;
            padding: 9px 10px !important;
        }

        #modal-pagamento .payment-actions {
            background: #0f172a;
            border-top: 1px solid #1e293b;
            margin-top: 0 !important;
            padding: 8px 0 12px;
            position: sticky;
            bottom: -12px;
            z-index: 2;
        }

        #modal-pagamento > div {
            height: auto !important;
            max-height: calc(100dvh - 16px) !important;
            min-height: 0;
            overflow: hidden;
        }

        .payment-split-input.active {
            border-color: #38bdf8;
            box-shadow: 0 0 0 1px rgba(56, 189, 248, 0.35);
        }

        .payment-summary-row {
            align-items: center;
            display: flex;
            font-size: 12px;
            justify-content: space-between;
        }

        .pos-workspace {
            align-items: flex-start;
            display: flex;
            gap: 20px;
        }

        .pos-main-panel {
            display: flex;
            flex: 1 1 auto;
            flex-direction: column;
            gap: 15px;
            min-width: 0;
        }

        .pos-cart-panel {
            background: #0f172a;
            border: 1px solid #1e293b;
            border-radius: 8px;
            display: flex;
            flex: 0 0 360px;
            flex-direction: column;
            max-height: calc(100vh - 190px);
            min-height: 420px;
            overflow: hidden;
            position: sticky;
            top: 12px;
        }

        .pos-cart-header,
        .pos-cart-summary,
        .pos-cart-actions {
            flex: 0 0 auto;
        }

        .pos-cart-header {
            background: rgba(2, 6, 23, 0.4);
            border-bottom: 1px solid #1e293b;
            padding: 12px 14px;
        }

        .pos-cart-list {
            display: flex;
            flex: 1 1 auto;
            flex-direction: column;
            gap: 6px;
            min-height: 0;
            overflow-y: auto;
            padding: 10px;
            scrollbar-color: #334155 #020617;
            scrollbar-width: thin;
        }

        .pos-cart-empty {
            color: #64748b;
            font-size: 13px;
            margin: auto;
            text-align: center;
        }

        .pos-cart-item {
            align-items: center;
            background: rgba(2, 6, 23, 0.45);
            border: 1px solid #1e293b;
            border-radius: 8px;
            display: grid;
            gap: 8px;
            grid-template-columns: minmax(0, 1fr) auto;
            min-height: 48px;
            padding: 8px;
        }

        .pos-cart-item-title {
            color: #fff;
            display: block;
            font-size: 13px;
            font-weight: 700;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .pos-cart-item-meta {
            color: #94a3b8;
            display: block;
            font-size: 11px;
            margin-top: 2px;
        }

        .pos-cart-item-side {
            align-items: flex-end;
            display: flex;
            flex-direction: column;
            gap: 5px;
            min-width: 96px;
        }

        .pos-cart-item-total {
            color: #fff;
            font-size: 12px;
            font-weight: 800;
            text-align: right;
            white-space: nowrap;
        }

        .pos-cart-remove {
            background: rgba(239, 68, 68, 0.12);
            border: 1px solid rgba(239, 68, 68, 0.25);
            border-radius: 6px;
            color: #f87171;
            cursor: pointer;
            font-size: 11px;
            font-weight: 800;
            min-height: 28px;
            padding: 4px 8px;
            touch-action: manipulation;
        }

        .supermarket-toolbar {
            background: #0f172a;
            border: 1px dashed #334155;
            border-radius: 10px;
            display: flex;
            gap: 12px;
            padding: 12px;
            align-items: center;
        }

        .supermarket-categories {
            display: flex;
            gap: 8px;
            overflow-x: auto;
            padding-bottom: 2px;
        }

        .supermarket-category-btn {
            background: rgba(148, 163, 184, 0.08);
            border: 1px solid rgba(148, 163, 184, 0.2);
            border-radius: 8px;
            color: #cbd5e1;
            cursor: pointer;
            flex: 0 0 auto;
            font-size: 12px;
            font-weight: 800;
            padding: 8px 10px;
        }

        .supermarket-category-btn.active {
            background: #38bdf8;
            border-color: #38bdf8;
            color: #020617;
        }

        .supermarket-grid {
            align-content: start;
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(auto-fill, minmax(138px, 1fr));
            max-height: 430px;
            overflow-y: auto;
        }

        .supermarket-product {
            background: rgba(56, 189, 248, 0.1);
            border: 1px solid rgba(56, 189, 248, 0.2);
            border-radius: 10px;
            color: #38bdf8;
            cursor: pointer;
            min-height: 82px;
            padding: 10px;
            text-align: left;
        }

        .supermarket-product-name {
            color: #fff;
            display: block;
            font-size: 12px;
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: 6px;
        }

        .supermarket-product-meta {
            color: #94a3b8;
            display: flex;
            font-size: 11px;
            justify-content: space-between;
            gap: 6px;
        }

        @media (max-width: 1100px) {
            #view-salao-mesas {
                grid-template-columns: repeat(3, minmax(120px, 1fr)) !important;
            }
        }

        @media (max-width: 860px) {
            #view-salao-mesas {
                grid-template-columns: repeat(2, minmax(120px, 1fr)) !important;
            }

            #modal-pagamento > div {
                flex-direction: column;
                height: auto !important;
                max-height: calc(100vh - 16px) !important;
                max-width: 520px !important;
            }

            #modal-pagamento > div > div:first-child {
                border-bottom: 1px solid #1e293b;
                border-right: none !important;
                flex: 0 0 auto !important;
                max-height: 150px;
            }

            #modal-preview-carrinho {
                display: none !important;
            }
        }

        @media (max-height: 620px) {
            #modal-pagamento {
                padding: 8px !important;
            }

            #modal-pagamento > div {
                max-height: calc(100dvh - 12px) !important;
            }

            #modal-pagamento > div > div:first-child {
                display: none !important;
            }

            #modal-pagamento .payment-header {
                margin-bottom: 6px !important;
            }

            #modal-pagamento .payment-header h3 {
                font-size: 14px !important;
            }

            #modal-pagamento .payment-form-stack {
                gap: 6px !important;
            }

            .metodo-pagamento {
                font-size: 12px !important;
                min-height: 36px;
                padding: 7px 8px !important;
            }

            #modal-pagamento .payment-main-input,
            .payment-split-input {
                min-height: 36px;
                padding: 7px 8px !important;
            }


        .payment-chip {
                min-height: 28px;
                padding: 5px 8px;
            }

            .payment-key {
                min-height: 34px;
            }

            #txt-troco-calculado {
                padding: 8px !important;
                font-size: 14px !important;
            }
        }

        @media (min-width: 720px) and (max-height: 620px) {
            #metodo-pagamento-ativo + div {
                grid-template-columns: repeat(4, minmax(0, 1fr)) !important;
            }
        }

        @media (max-width: 980px) {
            .pos-workspace {
                flex-direction: column;
            }

            .pos-cart-panel {
                flex: 0 0 auto;
                max-height: 520px;
                min-height: 360px;
                position: static;
                width: 100%;
            }
        }
    </style>

    <div class="space-y-4 max-w-[1600px] mx-auto px-2 text-slate-200"
        style="background-color: #020617; min-height: 100vh; padding: 20px; font-family: sans-serif;">

        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between bg-slate-950 border border-slate-800 p-4 rounded-2xl shadow-lg"
            style="display: flex; justify-content: space-between; align-items: center; border: 1px solid #1e293b; padding: 15px; border-radius: 12px; margin-bottom: 20px; background: #0f172a;">
            <div class="flex items-center gap-4" style="display: flex; align-items: center; gap: 15px;">
                <div class="font-bold text-base tracking-tight text-white" style="font-weight: bold; color: #fff;">
                    <span style="color: #38bdf8; margin-right: 5px;">●</span> Nkama ERP
                </div>

                <div
                    style="background: #020617; padding: 4px; border-radius: 8px; border: 1px solid #1e293b; display: flex; gap: 5px;">
                    @if($modules['restaurant'] ?? true)
                    <button id="tab-salao" onclick="mudarModoOperacao('salao')"
                        style="background: #38bdf8; color: #020617; font-weight: bold; padding: 6px 12px; border-radius: 6px; border: none; cursor: pointer;">
                        🏨 Gestão de Salão
                    </button>
                    @endif
                    @if($modules['supermarket'] ?? true)
                    <button id="tab-supermercado" onclick="mudarModoOperacao('supermercado')"
                        style="background: {{ ($modules['restaurant'] ?? true) ? 'transparent' : '#38bdf8' }}; color: {{ ($modules['restaurant'] ?? true) ? '#94a3b8' : '#020617' }}; padding: 6px 12px; border-radius: 6px; border: none; cursor: pointer;">
                        🛒 Supermercado / Retalho
                    </button>
                    @endif
                </div>
            </div>

            <div style="display: flex; align-items: center; gap: 20px; font-size: 13px;">
                <div id="metric-livres" style="color: #94a3b8;">Mesas Livres: <strong
                        style="color: #34d399;">{{ $tables->where('status', 'free')->count() }}</strong></div>
                <div id="metric-ocupadas" style="color: #94a3b8;">Mesas Ocupadas: <strong
                        style="color: #fbbf24;">{{ $tables->where('status', 'occupied')->count() }}</strong></div>
                <div style="color: #fff;">Vendas Hoje: <strong style="color: #34d399;">1.250.000,00 Kz</strong></div>

                <button id="btn-top-abrir-caixa" onclick="mostrarModalAberturaCaixa()"
                    style="display: none; background: rgba(16, 185, 129, 0.15); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.4); padding: 6px 12px; border-radius: 6px; font-weight: bold; cursor: pointer;">
                    Abrir Caixa
                </button>

                <button id="btn-top-fecho-caixa" onclick="abrirModalFecho()"
                    style="background: rgba(244, 63, 94, 0.15); color: #f43f5e; border: 1px solid rgba(244, 63, 94, 0.4); padding: 6px 12px; border-radius: 6px; font-weight: bold; cursor: pointer;">
                    🔒 Fecho de Caixa
                </button>
            </div>
        </div>

        <div class="pos-workspace">
            <div class="pos-main-panel">
                @if(!($modules['restaurant'] ?? true) && !($modules['supermarket'] ?? true))
                    <div style="background: rgba(249, 115, 22, 0.1); border: 1px solid rgba(249, 115, 22, 0.35); color: #fed7aa; padding: 14px; border-radius: 10px; font-size: 13px; font-weight: 700;">
                        Nenhum modulo operacional esta ativo. O super-user deve ativar Restaurante ou Supermercado em Seguranca > Modulos.
                    </div>
                @endif

                <div style="font-size: 18px; font-weight: bold; color: #fff;" id="txt-titulo-modulo">{{ ($modules['restaurant'] ?? true) ? 'Salao Principal' : 'Caixa Registadora - Supermercado' }}</div>

                <div id="view-salao-wrapper" style="display: {{ ($modules['restaurant'] ?? true) ? 'flex' : 'none' }}; flex-direction: column; gap: 10px;">
                    <div
                        style="background: #020617; border: 1px solid #1e293b; padding: 10px; border-radius: 12px; display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                        <span style="font-size: 12px; color: #94a3b8; margin-right: 4px;">Filtrar mesas:</span>
                        <button id="filter-mesas-all" onclick="filtrarMesas('all')"
                            style="background: #38bdf8; color: #020617; border: 1px solid rgba(56, 189, 248, 0.5); padding: 7px 12px; border-radius: 8px; font-size: 12px; font-weight: bold; cursor: pointer;">
                            Todas
                        </button>
                        <button id="filter-mesas-free" onclick="filtrarMesas('free')"
                            style="background: transparent; color: #34d399; border: 1px solid rgba(52, 211, 153, 0.35); padding: 7px 12px; border-radius: 8px; font-size: 12px; font-weight: bold; cursor: pointer;">
                            Livres
                        </button>
                        <button id="filter-mesas-occupied" onclick="filtrarMesas('occupied')"
                            style="background: transparent; color: #fbbf24; border: 1px solid rgba(251, 191, 36, 0.35); padding: 7px 12px; border-radius: 8px; font-size: 12px; font-weight: bold; cursor: pointer;">
                            Ocupadas
                        </button>
                    </div>

                    <div id="view-salao-mesas"
                    style="display: grid; grid-template-columns: repeat(4, minmax(130px, 1fr)); gap: 10px; background: #020617; border: 1px solid #1e293b; padding: 15px; border-radius: 12px; max-height: 430px; overflow-y: auto; align-content: start;">
                    @foreach ($tables as $table)
                        @php
                            $styleMesa =
                                'background: rgba(52, 211, 153, 0.1); border: 1px solid rgba(52, 211, 153, 0.2); color: #34d399;';
                            if ($table->status === 'occupied') {
                                $styleMesa =
                                    'background: rgba(251, 191, 36, 0.1); border: 1px solid rgba(251, 191, 36, 0.3); color: #fbbf24;';
                            }
                            if ($table->status === 'waiting_payment') {
                                $styleMesa =
                                    'background: rgba(244, 63, 94, 0.1); border: 1px solid rgba(244, 63, 94, 0.3); color: #f43f5e;';
                            }
                            $mesaStatusFiltro = $table->status === 'free' ? 'free' : 'occupied';
                        @endphp

                        <button class="p-4 rounded-xl border transition text-center gap-1 group relative mesa-card"
                            data-status="{{ $mesaStatusFiltro }}"
                            style="{{ $styleMesa }} padding: 15px; border-radius: 10px; cursor: pointer; height: 72px; overflow: hidden;"
                            id="card-mesa-{{ $table->id }}"
                            onclick="selecionarMesa({{ $table->id }}, '{{ $table->name }}')">
                            <div style="font-weight: bold; color: #fff;">🪑 {{ $table->name }}</div>
                            <div style="font-size: 10px; text-transform: uppercase; margin-top: 5px;"
                                id="status-text-{{ $table->id }}">
                                {{ $table->status === 'free' ? 'Livre' : 'Ocupada' }}
                            </div>
                        </button>
                    @endforeach
                </div>
                </div>

                <div id="restaurant-categories"
                    style="background: #020617; border: 1px solid #1e293b; padding: 15px; border-radius: 12px; display: none; grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); gap: 10px;">
                    <button
                        style="background: rgba(148, 163, 184, 0.08); border: 1px solid rgba(148, 163, 184, 0.2); color: #cbd5e1; padding: 10px 15px; border-radius: 10px; cursor: pointer; min-width: 120px;"
                        onclick="voltarParaMesas()">
                        <div style="font-size: 12px; font-weight: bold; color: #fff;">Voltar</div>
                        <div style="font-size: 11px; margin-top: 2px;">Selecionar mesa</div>
                    </button>
                    @forelse ($restaurantCategories as $category)
                        <button
                            style="background: rgba(56, 189, 248, 0.1); border: 1px solid rgba(56, 189, 248, 0.2); color: #38bdf8; padding: 10px 15px; border-radius: 10px; cursor: pointer; min-width: 120px;"
                            onclick="mostrarCategoriaRestaurante({{ $category->id }})">
                            <div style="font-size: 12px; font-weight: bold; color: #fff;">{{ $category->name }}</div>
                            <div style="font-size: 11px; margin-top: 2px;">{{ $category->products->count() }} artigos</div>
                        </button>
                    @empty
                        <div style="color: #64748b; font-size: 13px;">Nenhuma categoria com artigos de restaurante.</div>
                    @endforelse
                </div>

                <div id="view-supermercado" class="{{ ($modules['restaurant'] ?? true) ? 'hidden' : '' }}"
                    style="background: #020617; border: 1px solid #1e293b; padding: 15px; border-radius: 12px; flex-direction: column; gap: 12px; display: {{ !($modules['restaurant'] ?? true) && ($modules['supermarket'] ?? true) ? 'flex' : 'none' }};">
                    <div class="supermarket-toolbar">
                        <div style="font-family: monospace; color: #475569; font-size: 20px; letter-spacing: -2px;">█║▌│█│║▌
                        </div>
                        <input type="text" id="inputBarcode" oninput="filtrarProdutosSupermercado()" onkeypress="verificarInputBarcode(event)"
                            style="flex: 1; background: #020617; border: 1px solid #1e293b; color: #fff; padding: 10px; border-radius: 8px; font-size: 14px;"
                            placeholder="Passe o leitor de código de barras ou digite o nome do produto...">
                    </div>

                    <div class="supermarket-categories">
                        <button type="button" class="supermarket-category-btn active" data-category="all"
                            onclick="selecionarCategoriaSupermercado('all')">Todos</button>
                        @foreach ($products->groupBy(fn ($product) => $product->category?->name ?? 'Sem categoria') as $categoryName => $categoryProducts)
                            <button type="button" class="supermarket-category-btn" data-category="{{ \Illuminate\Support\Str::slug($categoryName) }}"
                                onclick="selecionarCategoriaSupermercado('{{ \Illuminate\Support\Str::slug($categoryName) }}')">
                                {{ $categoryName }} ({{ $categoryProducts->count() }})
                            </button>
                        @endforeach
                    </div>
                </div>

                <div id="restaurant-products"
                    style="background: #020617; border: 1px solid #1e293b; padding: 15px; border-radius: 12px; display: none; gap: 10px; overflow-x: auto;">
                    @foreach ($restaurantCategories as $category)
                        @foreach ($category->products as $p)
                            <button class="restaurant-product category-{{ $category->id }}"
                                style="background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.2); color: #34d399; padding: 10px 15px; border-radius: 10px; cursor: pointer; min-width: 110px; display: none;"
                                onclick="adicionarItemNoPedido({{ $p->id }}, @js($p->name), {{ $p->selling_price }}, {{ $p->tax_rate ?? 0 }})">
                                <div style="font-size: 12px; font-weight: bold; color: #fff;">{{ $p->name }}</div>
                                <div style="font-size: 11px; margin-top: 2px;">
                                    {{ number_format($p->selling_price, 0, ',', '.') }} Kz</div>
                            </button>
                        @endforeach
                    @endforeach
                </div>

                <div id="supermarket-products" class="supermarket-grid"
                    style="background: #020617; border: 1px solid #1e293b; padding: 15px; border-radius: 12px; display: {{ !($modules['restaurant'] ?? true) && ($modules['supermarket'] ?? true) ? 'grid' : 'none' }};">
                    @foreach ($products as $p)
                        @php
                            $supermarketCategory = \Illuminate\Support\Str::slug($p->category?->name ?? 'Sem categoria');
                        @endphp
                        <button class="supermarket-product"
                            data-category="{{ $supermarketCategory }}"
                            data-search="{{ \Illuminate\Support\Str::lower($p->name . ' ' . ($p->barcode ?? '') . ' ' . ($p->category?->name ?? '')) }}"
                            onclick="adicionarItemNoPedido({{ $p->id }}, @js($p->name), {{ $p->selling_price }}, {{ $p->tax_rate ?? 0 }})">
                            <span class="supermarket-product-name">{{ $p->name }}</span>
                            <span class="supermarket-product-meta">
                                <span>{{ number_format($p->selling_price, 0, ',', '.') }} Kz</span>
                                <span>Stock: {{ (int) $p->stock_quantity }}</span>
                            </span>
                        </button>
                    @endforeach
                </div>
            </div>

            <div class="pos-cart-panel">
                <div class="pos-cart-header">
                    <h3 id="lbl-mesa-ativa" style="margin: 0; color: #fff; font-size: 15px;">Nenhuma Selecionada</h3>
                    <div id="lbl-cliente-tipo" style="font-size: 11px; color: #94a3b8; margin-top: 3px;">Selecione uma mesa
                        no salão</div>
                    <div id="mesa-acoes" style="display:flex; gap:8px; margin-top:10px;">
                        <button type="button" onclick="consultarMesaAtual()"
                            style="flex:1; background:#1e293b; border:1px solid #334155; color:#e2e8f0; padding:8px; border-radius:8px; font-size:12px; font-weight:bold; cursor:pointer;">
                            Consultar
                        </button>
                        <button type="button" onclick="abrirTransferenciaMesa()"
                            style="flex:1; background:#0f766e; border:1px solid rgba(45,212,191,.35); color:#ccfbf1; padding:8px; border-radius:8px; font-size:12px; font-weight:bold; cursor:pointer;">
                            Transferir
                        </button>
                    </div>
                </div>

                <div id="lista-itens-pedido" class="pos-cart-list">
                    <div style="text-align: center; color: #64748b; font-size: 13px; margin-top: 40px;">O carrinho está
                        vazio.</div>
                </div>

                <div class="pos-cart-summary"
                    style="padding: 15px; background: rgba(2, 6, 23, 0.2); border-top: 1px solid #1e293b; font-size: 13px;">
                    <div style="display: flex; justify-content: space-between; color: #94a3b8; margin-bottom: 5px;">
                        <span>Subtotal:</span>
                        <strong id="txt-subtotal" style="color: #fff;">0,00 Kz</strong>
                    </div>
                    <div
                        style="display: flex; justify-content: space-between; font-size: 15px; font-weight: bold; color: #fff; border-top: 1px solid #1e293b; padding-top: 10px;">
                        <span>Total:</span>
                        <span id="txt-total" style="color: #38bdf8;">0,00 Kz</span>
                    </div>
                </div>

                <div class="pos-cart-actions" style="padding: 15px; background: #020617; display: flex; flex-direction: column; gap: 10px;">
                    <button id="btn-finalizar-venda" onclick="processarFechamentoVenda()"
                        style="width: 100%; background: #10b981; color: #020617; font-weight: bold; padding: 12px; border: none; border-radius: 8px; font-size: 14px; cursor: pointer;">
                        Processar Pagamento (F3)
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Campo oculto para guardar o método de pagamento selecionado -->
    <select id="select-metodo-pagamento" style="display: none;">
        <option value="cash">Dinheiro</option>
        <option value="card">Multicaixa</option>
        <option value="transf">Transferência</option>
        <option value="multi">Pagamento Misto</option>
        <option value="customer_card">Cartao Cliente</option>
        @if($modules['current_account'] ?? true)
            <option value="credit">Conta Corrente</option>
        @endif
    </select>

    <div id="modal-pagamento"
        style="position: fixed; inset: 0; background: rgba(2, 6, 23, 0.9); backdrop-filter: blur(4px); display: none; align-items: center; justify-content: center; padding: 12px; z-index: 50;">
        <div
            style="background: #0f172a; border: 1px solid #1e293b; border-radius: 12px; width: 100%; max-width: 820px; padding: 0; margin: 0 auto; display: flex; height: 520px; max-height: calc(100vh - 24px); overflow: hidden;">
            
            <!-- PREVIEW DO CARRINHO (Esquerda/Cima) -->
            <div style="flex: 0 0 260px; display: flex; flex-direction: column; background: #020617; border-right: 1px solid #1e293b; padding: 0; overflow: hidden;">
                <div style="padding: 10px 12px; border-bottom: 1px solid #1e293b; background: rgba(56, 189, 248, 0.1);">
                    <h3 style="color: #38bdf8; margin: 0; font-size: 14px;">🛒 Itens do Carrinho</h3>
                </div>
                
                <div id="modal-preview-carrinho" 
                    style="flex: 1; padding: 10px; overflow: hidden; display: flex; flex-direction: column; gap: 6px;">
                    <div style="text-align: center; color: #64748b; font-size: 13px; margin-top: 40px;">Carregando...</div>
                </div>
                
                <div style="padding: 10px 12px; background: #020617; border-top: 1px solid #1e293b;">
                    <div style="display: flex; justify-content: space-between; font-size: 12px; color: #94a3b8; margin-bottom: 8px;">
                        <span>Subtotal:</span>
                        <strong id="modal-subtotal-preview" style="color: #fff;">0,00 Kz</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 12px; color: #94a3b8; margin-bottom: 8px;">
                        <span>IVA incluído:</span>
                        <strong id="modal-iva-preview" style="color: #38bdf8;">0,00 Kz</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 14px; font-weight: bold; color: #fff; border-top: 1px solid #1e293b; padding-top: 8px;">
                        <span>Total a Pagar:</span>
                        <span id="modal-txt-total" style="color: #34d399;">0,00 Kz</span>
                    </div>
                </div>
            </div>
            
            <!-- FORMULÁRIO DE PAGAMENTO (Direita/Baixo) -->
            <div class="payment-panel" style="flex: 1; display: flex; flex-direction: column; padding: 20px; overflow-y: auto;">
                <div class="payment-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h3 style="color: #fff; margin: 0; font-size: 16px;">💳 Forma de Pagamento</h3>
                    <button onclick="fecharModalPagamento()"
                        style="background: transparent; border: none; color: #94a3b8; font-size: 24px; cursor: pointer; padding: 0;">&times;</button>
                </div>

                <div class="payment-form-stack" style="display: flex; flex-direction: column; gap: 15px;">
                    <div>
                        <label
                            style="display: block; font-size: 11px; color: #94a3b8; text-transform: uppercase; margin-bottom: 8px; font-weight: bold;">Selecionar Método</label>
                        <div id="metodo-pagamento-ativo"
                            style="background: rgba(16,185,129,0.08); border: 1px solid rgba(16,185,129,0.22); color: #34d399; padding: 6px 8px; border-radius: 8px; font-size: 11px; font-weight: bold; margin-bottom: 6px;">
                            Dinheiro selecionado
                        </div>
                        <div style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 6px;">
                            <button type="button" onclick="selecionarMetodoPagamento('cash')" class="metodo-pagamento active" data-metodo="cash" data-cor="#10b981" data-label="Dinheiro" data-desc="Calcula troco automaticamente"
                                style="background: #10b981; color: #020617; padding: 12px; border: 2px solid #10b981; border-radius: 8px; font-weight: bold; cursor: pointer; text-align: center; transition: all 0.3s;">
                                💵 Dinheiro
                            </button>
                            <button type="button" onclick="selecionarMetodoPagamento('card')" class="metodo-pagamento" data-metodo="card" data-cor="#38bdf8" data-label="Multicaixa" data-desc="TPA, cartao ou referencia"
                                style="background: transparent; color: #38bdf8; padding: 12px; border: 2px solid #38bdf8; border-radius: 8px; font-weight: bold; cursor: pointer; text-align: center; transition: all 0.3s;">
                                💳 Multicaixa
                            </button>
                            <button type="button" onclick="selecionarMetodoPagamento('transf')" class="metodo-pagamento" data-metodo="transf" data-cor="#8b5cf6" data-label="Transferencia" data-desc="Pagamento bancario confirmado"
                                style="background: transparent; color: #8b5cf6; padding: 12px; border: 2px solid #8b5cf6; border-radius: 8px; font-weight: bold; cursor: pointer; text-align: center; transition: all 0.3s;">
                                🏦 Transferência
                            </button>
                            <button type="button" onclick="selecionarMetodoPagamento('multi')" class="metodo-pagamento" data-metodo="multi" data-cor="#f59e0b" data-label="Pagamento Misto" data-desc="Combina dinheiro e digital"
                                style="background: transparent; color: #f59e0b; padding: 12px; border: 2px solid #f59e0b; border-radius: 8px; font-weight: bold; cursor: pointer; text-align: center; transition: all 0.3s;">
                                🔀 Pagamento Misto
                            </button>
                            <button type="button" onclick="selecionarMetodoPagamento('customer_card')" class="metodo-pagamento" data-metodo="customer_card" data-cor="#22c55e" data-label="Cartao Cliente" data-desc="Usa bonus de pontos e saldo do cartao"
                                style="background: transparent; color: #22c55e; padding: 12px; border: 2px solid #22c55e; border-radius: 8px; font-weight: bold; cursor: pointer; text-align: center; transition: all 0.3s;">
                                Fidelidade
                            </button>                        </div>
                    </div>


                    @if($modules['current_account'] ?? true)
                    <div id="wrapper-cliente-conta" style="display: none; background: rgba(244,63,94,0.08); border: 1px solid rgba(244,63,94,0.25); border-radius: 8px; padding: 10px;">
                        <label style="display: block; font-size: 11px; color: #fecdd3; text-transform: uppercase; margin-bottom: 6px; font-weight: bold;">Cliente da conta corrente</label>
                        <select id="select-cliente-conta" style="width: 100%; background: #020617; border: 1px solid #7f1d1d; color: #fff; padding: 11px; border-radius: 8px;">
                            <option value="">Selecionar cliente</option>
                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}">{{ $customer->name }}{{ $customer->phone ? ' - ' . $customer->phone : '' }}</option>
                            @endforeach
                        </select>
                        <div id="txt-valor-pendente-conta" style="color:#fecdd3; font-size:12px; margin-top:7px;">Pendente: 0,00 Kz</div>
                    </div>

                    <button type="button" onclick="selecionarMetodoPagamento('credit')" class="metodo-pagamento" data-metodo="credit" data-cor="#f43f5e" data-label="Conta Corrente" data-desc="Lanca o valor em aberto para o cliente"
                        style="background: transparent; color: #f43f5e; padding: 12px; border: 2px solid #f43f5e; border-radius: 8px; font-weight: bold; cursor: pointer; text-align: center; transition: all 0.3s;">
                        Conta Corrente
                    </button>
                    @endif

                    @if($modules['customer_card'] ?? true)
                    <div id="wrapper-cartao-cliente" style="background: rgba(56,189,248,0.08); border: 1px solid rgba(56,189,248,0.24); border-radius: 8px; padding: 10px;">
                        <label style="display: block; font-size: 11px; color: #bae6fd; text-transform: uppercase; margin-bottom: 6px; font-weight: bold;">Cartao cliente</label>
                        <div style="display: flex; gap: 8px;">
                            <input id="input-cartao-cliente" type="text" placeholder="Ler codigo de barras ou QR" style="flex: 1; background: #020617; border: 1px solid #0ea5e9; color: #fff; padding: 10px; border-radius: 8px;">
                            <button type="button" onclick="consultarCartaoCliente()" style="background:#38bdf8; color:#020617; border:0; border-radius:8px; padding:0 12px; font-weight:800;">OK</button>
                            <button type="button" onclick="limparCartaoCliente()" style="background:transparent; color:#bae6fd; border:1px solid rgba(186,230,253,.4); border-radius:8px; padding:0 10px; font-weight:800;">X</button>
                        </div>
                        <div id="painel-cartao-cliente" style="display:none; margin-top:8px; color:#e0f2fe; font-size:12px; line-height:1.5;"></div>
                        <div id="painel-cartao-otp" style="display:none; margin-top:10px; border-top:1px solid rgba(186,230,253,.18); padding-top:10px;">
                            @if($modules['customer_card_otp'] ?? true)
                            <div style="display:flex; gap:8px; flex-wrap:wrap; align-items:end;">
                                <label style="flex:1; min-width:120px; color:#bae6fd; font-size:11px; font-weight:800; text-transform:uppercase;">OTP
                                    <input id="input-cartao-cliente-otp" type="text" inputmode="numeric" maxlength="6" placeholder="Codigo do cliente" style="width:100%; margin-top:5px; background:#020617; border:1px solid #0ea5e9; color:#fff; padding:9px; border-radius:8px;">
                                </label>
                                <button type="button" onclick="solicitarOtpCartaoCliente()" style="background:#facc15; color:#111827; border:0; border-radius:8px; padding:10px 12px; font-weight:900;">Enviar OTP</button>
                            </div>
                            @endif
                            <input id="input-cartao-autorizacao-id" type="hidden">
                            <div style="display:grid; grid-template-columns:1fr auto; gap:8px; margin-top:8px;">
                                <input id="input-cartao-autorizacao-motivo" type="text" maxlength="180" placeholder="Motivo para solicitar autorizacao ao gestor" style="background:#020617; border:1px solid #334155; color:#fff; padding:9px; border-radius:8px;">
                                <button type="button" onclick="solicitarAutorizacaoCartaoCliente()" style="background:#22c55e; color:#052e16; border:0; border-radius:8px; padding:10px 12px; font-weight:900;">Solicitar</button>
                            </div>                            <label style="display:flex; gap:8px; align-items:center; color:#e0f2fe; font-size:12px; margin-top:8px;">
                                <input id="check-cartao-emergencia" type="checkbox" onchange="alternarEmergenciaCartao()">
                                Emergencia offline com supervisor
                            </label>
                            <div id="painel-cartao-emergencia" style="display:none; grid-template-columns:1fr; gap:7px; margin-top:8px;">
                                <input id="input-cartao-supervisor-pin" type="password" inputmode="numeric" maxlength="8" placeholder="PIN do supervisor" style="background:#020617; border:1px solid #f59e0b; color:#fff; padding:9px; border-radius:8px;">
                                <input id="input-cartao-supervisor-motivo" type="text" maxlength="180" placeholder="Motivo da emergencia offline" style="background:#020617; border:1px solid #334155; color:#fff; padding:9px; border-radius:8px;">
                                <div style="color:#fbbf24; font-size:11px;">Limite emergencial: 2.000,00 Kz por venda.</div>
                            </div>
                            <div id="txt-cartao-otp-status" style="color:#bae6fd; font-size:11px; margin-top:7px;"></div>
                            <div id="painel-cartao-autorizacoes" style="display:none; margin-top:10px; background:rgba(34,197,94,.08); border:1px solid rgba(34,197,94,.2); border-radius:8px; padding:8px;">
                                <div style="display:flex; justify-content:space-between; gap:8px; align-items:center; color:#bbf7d0; font-size:11px; font-weight:900; text-transform:uppercase;">
                                    <span>Solicitacoes pendentes</span>
                                    <button type="button" onclick="carregarSolicitacoesCartaoCliente()" style="background:transparent; color:#bbf7d0; border:1px solid rgba(187,247,208,.35); border-radius:7px; padding:4px 8px; font-size:11px; font-weight:900;">Atualizar</button>
                                </div>
                                <div id="lista-cartao-autorizacoes" style="display:grid; gap:7px; margin-top:8px;"></div>
                            </div>
                        </div>
                    </div>
                    @endif
                    <div id="wrapper-valores-recebidos" style="display: flex; flex-direction: column; gap: 7px;">
                        <div style="flex: 1;">
                            <label id="label-valor-pago"
                                style="display: block; font-size: 11px; color: #94a3b8; text-transform: uppercase; margin-bottom: 5px; font-weight: bold;">Valor Recebido em Dinheiro</label>
                            <input type="number" id="input-valor-pago" class="payment-main-input" oninput="calcularTroco()" inputmode="decimal" min="0" step="0.01"
                                style="width: 100%; background: #020617; border: 1px solid #1e293b; color: #fff; padding: 14px; border-radius: 8px; font-size: 20px; font-weight: bold;">
                            <div id="payment-split-wrapper"
                                style="display: none; margin-top: 6px; background: rgba(2, 6, 23, 0.45); border: 1px solid #1e293b; border-radius: 8px; padding: 8px;">
                                <div style="display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 6px;">
                                    <div>
                                        <label style="display: block; color: #94a3b8; font-size: 10px; font-weight: bold; margin-bottom: 4px; text-transform: uppercase;">Dinheiro</label>
                                        <input type="number" id="input-pago-cash" class="payment-split-input" inputmode="decimal" min="0" step="0.01" value="0"
                                            onfocus="selecionarCampoPagamento('cash')" oninput="calcularPagamentoMisto()">
                                    </div>
                                    <div>
                                        <label style="display: block; color: #94a3b8; font-size: 10px; font-weight: bold; margin-bottom: 4px; text-transform: uppercase;">Multicaixa</label>
                                        <input type="number" id="input-pago-card" class="payment-split-input" inputmode="decimal" min="0" step="0.01" value="0"
                                            onfocus="selecionarCampoPagamento('card')" oninput="calcularPagamentoMisto()">
                                    </div>
                                    <div>
                                        <label style="display: block; color: #94a3b8; font-size: 10px; font-weight: bold; margin-bottom: 4px; text-transform: uppercase;">Transfer.</label>
                                        <input type="number" id="input-pago-transf" class="payment-split-input" inputmode="decimal" min="0" step="0.01" value="0"
                                            onfocus="selecionarCampoPagamento('transf')" oninput="calcularPagamentoMisto()">
                                    </div>                                    @if($modules['customer_card'] ?? true)
                                    <div>
                                        <label style="display: block; color: #94a3b8; font-size: 10px; font-weight: bold; margin-bottom: 4px; text-transform: uppercase;">Fidelidade</label>
                                        <input type="number" id="input-pago-customer-card" class="payment-split-input" inputmode="decimal" min="0" step="0.01" value="0"
                                            onfocus="selecionarCampoPagamento('customer-card')" oninput="calcularPagamentoMisto()">
                                    </div>
                                    @endif
                                </div>
                                <div style="display: grid; gap: 4px; margin-top: 7px;">
                                    <div class="payment-summary-row"><span style="color:#94a3b8;">Recebido</span><strong id="txt-total-recebido" style="color:#fff;">0,00 Kz</strong></div>
                                    <div class="payment-summary-row"><span style="color:#94a3b8;">Falta</span><strong id="txt-total-falta" style="color:#f59e0b;">0,00 Kz</strong></div>
                                </div>
                            </div>
                            <div style="display: flex; flex-wrap: wrap; gap: 6px; margin-top: 6px;">
                                <button type="button" class="payment-chip" onclick="preencherValorPago(totalGeralVendaActual)">Valor exato</button>
                                <button type="button" class="payment-chip" onclick="preencherValorPago(arredondarValorPagamento(totalGeralVendaActual, 500))">+ 500</button>
                                <button type="button" class="payment-chip" onclick="preencherValorPago(arredondarValorPagamento(totalGeralVendaActual, 1000))">+ 1.000</button>
                            </div>
                            <div class="payment-keypad" style="margin-top: 7px;">
                                <button type="button" class="payment-key" onclick="digitarValorPagamento('1')">1</button>
                                <button type="button" class="payment-key" onclick="digitarValorPagamento('2')">2</button>
                                <button type="button" class="payment-key" onclick="digitarValorPagamento('3')">3</button>
                                <button type="button" class="payment-key" onclick="limparValorPagamento()">C</button>
                                <button type="button" class="payment-key" onclick="digitarValorPagamento('4')">4</button>
                                <button type="button" class="payment-key" onclick="digitarValorPagamento('5')">5</button>
                                <button type="button" class="payment-key" onclick="digitarValorPagamento('6')">6</button>
                                <button type="button" class="payment-key" onclick="apagarValorPagamento()">DEL</button>
                                <button type="button" class="payment-key" onclick="digitarValorPagamento('7')">7</button>
                                <button type="button" class="payment-key" onclick="digitarValorPagamento('8')">8</button>
                                <button type="button" class="payment-key" onclick="digitarValorPagamento('9')">9</button>
                                <button type="button" class="payment-key" onclick="preencherValorPago(totalGeralVendaActual)">Total</button>
                                <button type="button" class="payment-key" onclick="digitarValorPagamento('0')">0</button>
                                <button type="button" class="payment-key" onclick="digitarValorPagamento('.')">,</button>
                                <button type="button" class="payment-key" onclick="preencherValorPago(arredondarValorPagamento(totalGeralVendaActual, 1000))">+1K</button>
                            </div>
                        </div>
                        <div id="box-troco" style="flex: 1;">
                            <label
                                style="display: block; font-size: 11px; color: #94a3b8; text-transform: uppercase; margin-bottom: 5px; font-weight: bold;">Troco</label>
                            <div id="txt-troco-calculado"
                                style="background: #020617; border: 2px solid #34d399; color: #34d399; font-weight: bold; padding: 10px; border-radius: 8px; text-align: center; font-size: 16px;">
                                0,00 Kz</div>
                        </div>
                    </div>
                </div>

                <div class="payment-actions" style="margin-top: auto; display: flex; gap: 8px;">
                    <button onclick="fecharModalPagamento()"
                        style="flex: 1; background: #020617; border: 1px solid #1e293b; color: #94a3b8; padding: 12px; border-radius: 8px; cursor: pointer; font-weight: bold; transition: all 0.3s;">Cancelar</button>
                    <button id="btn-emitir-fatura-pos" class="pos-processing-button" onclick="submeterVendaFinal()"
                        style="flex: 1; background: #10b981; color: #020617; font-weight: bold; padding: 12px; border-radius: 8px; border: none; cursor: pointer; font-size: 15px; transition: all 0.3s;"><span class="pos-processing-spinner" aria-hidden="true"></span><span class="button-label">Emitir Fatura</span></button>
                </div>
            </div>
        </div>
    </div>

    <div id="modal-sucesso-venda"
        style="position: fixed; inset: 0; background: rgba(2, 6, 23, 0.82); backdrop-filter: blur(4px); display: none; align-items: center; justify-content: center; padding: 20px; z-index: 60;">
        <div
            style="background: #0f172a; border: 1px solid #1e293b; border-radius: 12px; width: 100%; max-width: 360px; padding: 20px;">
            <div style="width: 48px; height: 48px; border-radius: 12px; background: rgba(16,185,129,0.16); color: #34d399; display: flex; align-items: center; justify-content: center; font-size: 16px; font-weight: bold; margin-bottom: 14px;">
                OK
            </div>
            <h3 style="color: #fff; margin: 0; font-size: 18px;">Ticket emitido</h3>
            <div id="sucesso-invoice" style="color: #38bdf8; font-size: 14px; font-weight: bold; margin-top: 4px;">-</div>

            <div style="background: #020617; border: 1px solid #1e293b; border-radius: 10px; padding: 12px; margin-top: 16px; display: grid; gap: 8px;">
                <div style="display: flex; justify-content: space-between; color: #94a3b8; font-size: 12px;">
                    <span>Total</span>
                    <strong id="sucesso-total" style="color: #fff;">0,00 Kz</strong>
                </div>
                <div style="display: flex; justify-content: space-between; color: #94a3b8; font-size: 12px;">
                    <span>Recebido</span>
                    <strong id="sucesso-recebido" style="color: #34d399;">0,00 Kz</strong>
                </div>
                <div style="display: flex; justify-content: space-between; color: #94a3b8; font-size: 12px;">
                    <span>Metodo</span>
                    <strong id="sucesso-metodo" style="color: #e2e8f0;">-</strong>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr; gap: 8px; margin-top: 16px;">
                <button type="button" onclick="abrirTicketVenda()"
                    style="background: #38bdf8; color: #020617; font-weight: bold; padding: 11px; border-radius: 8px; border: none; cursor: pointer;">
                    Imprimir Ticket
                </button>
            </div>

            <button type="button" onclick="fecharModalSucessoVenda()"
                style="width: 100%; background: #10b981; color: #020617; font-weight: bold; padding: 12px; border-radius: 8px; border: none; cursor: pointer; margin-top: 8px;">
                OK
            </button>
        </div>
    </div>

    <div id="modal-consulta-mesa"
        style="position: fixed; inset: 0; background: rgba(2, 6, 23, 0.86); backdrop-filter: blur(4px); display: none; align-items: center; justify-content: center; padding: 16px; z-index: 65;">
        <div style="background:#0f172a; border:1px solid #1e293b; border-radius:12px; width:100%; max-width:420px; max-height:calc(100vh - 32px); overflow:hidden; display:flex; flex-direction:column;">
            <div style="display:flex; justify-content:space-between; align-items:center; padding:14px 16px; border-bottom:1px solid #1e293b;">
                <div>
                    <h3 id="consulta-mesa-titulo" style="color:#fff; margin:0; font-size:16px;">Consulta de Mesa</h3>
                    <div id="consulta-mesa-info" style="color:#94a3b8; font-size:12px; margin-top:3px;">-</div>
                </div>
                <button type="button" onclick="fecharConsultaMesa()" style="background:transparent; border:none; color:#94a3b8; font-size:24px; cursor:pointer;">&times;</button>
            </div>
            <div id="consulta-mesa-itens" style="padding:14px 16px; overflow-y:auto; display:grid; gap:8px;"></div>
            <div style="padding:14px 16px; border-top:1px solid #1e293b; display:grid; gap:6px;">
                <div style="display:flex; justify-content:space-between; color:#94a3b8; font-size:12px;"><span>Subtotal</span><strong id="consulta-subtotal" style="color:#fff;">0,00 Kz</strong></div>
                <div style="display:flex; justify-content:space-between; color:#94a3b8; font-size:12px;"><span>IVA incluído</span><strong id="consulta-iva" style="color:#38bdf8;">0,00 Kz</strong></div>
                <div style="display:flex; justify-content:space-between; color:#fff; font-size:15px; font-weight:bold; border-top:1px solid #1e293b; padding-top:8px;"><span>Total</span><strong id="consulta-total" style="color:#34d399;">0,00 Kz</strong></div>
                <button type="button" onclick="imprimirConsultaMesa()"
                    style="width:100%; background:#38bdf8; border:none; color:#020617; padding:10px; border-radius:8px; font-size:13px; font-weight:bold; cursor:pointer; margin-top:8px;">
                    Imprimir Consulta
                </button>
            </div>
        </div>
    </div>

    <div id="modal-transferencia-mesa"
        style="position: fixed; inset: 0; background: rgba(2, 6, 23, 0.86); backdrop-filter: blur(4px); display: none; align-items: center; justify-content: center; padding: 16px; z-index: 66;">
        <div style="background:#0f172a; border:1px solid #1e293b; border-radius:12px; width:100%; max-width:520px; padding:18px;">
            <h3 style="color:#fff; margin:0; font-size:17px;">Transferir Conta</h3>
            <div id="transferencia-origem" style="color:#94a3b8; font-size:12px; margin-top:5px;">Origem: -</div>

            <label style="display:block; color:#94a3b8; font-size:11px; font-weight:bold; text-transform:uppercase; margin:16px 0 6px;">Mesa de destino</label>
            <select id="transferencia-destino"
                style="width:100%; background:#020617; border:1px solid #334155; color:#fff; border-radius:8px; padding:10px;">
                <option value="">Selecione a mesa livre</option>
                @foreach($tables as $table)
                    <option value="{{ $table->id }}">{{ $table->name }}</option>
                @endforeach
            </select>

            <div style="display:flex; justify-content:space-between; align-items:center; margin:16px 0 6px;">
                <label style="display:block; color:#94a3b8; font-size:11px; font-weight:bold; text-transform:uppercase;">Produtos a transferir</label>
                <button type="button" onclick="selecionarTodosItensTransferencia()"
                    style="background:transparent; border:none; color:#38bdf8; font-size:12px; font-weight:bold; cursor:pointer;">Selecionar todos</button>
            </div>
            <div id="transferencia-itens"
                style="display:grid; gap:8px; max-height:240px; overflow-y:auto; padding-right:4px;"></div>

            <div style="display:flex; gap:8px; margin-top:16px;">
                <button type="button" onclick="fecharTransferenciaMesa()"
                    style="flex:1; background:#020617; border:1px solid #334155; color:#94a3b8; padding:10px; border-radius:8px; cursor:pointer;">Cancelar</button>
                <button type="button" onclick="confirmarTransferenciaMesa()"
                    style="flex:1; background:#0f766e; border:none; color:#ccfbf1; font-weight:bold; padding:10px; border-radius:8px; cursor:pointer;">Transferir</button>
            </div>
        </div>
    </div>

    <div id="modal-fecho" class="hidden"
        style="position: fixed; inset: 0; background: rgba(2, 6, 23, 0.8); backdrop-filter: blur(4px); display: none; align-items: center; justify-content: center; padding: 20px; z-index: 50;">
        <div
            style="background: #0f172a; border: 1px solid #1e293b; border-radius: 12px; width: 100%; max-width: 360px; padding: 20px; margin: 10% auto;">
            <h3 style="color: #fff; margin: 0 0 10px 0; border-bottom: 1px solid #1e293b; padding-bottom: 8px;">Relatório
                de Fecho de Turno</h3>
            <div style="display: flex; flex-direction: column; gap: 8px; font-size: 13px; margin: 15px 0;">
                <div style="display: flex; justify-content: space-between;"><span style="color:#94a3b8;">Faturamento
                        Total:</span><strong style="color:#fff;">1.250.000,00 Kz</strong></div>
                <div style="display: flex; justify-content: space-between;"><span style="color:#94a3b8;">Caixa
                        (Dinheiro):</span><strong style="color:#34d399;">450.000,00 Kz</strong></div>
                <div style="display: flex; justify-content: space-between;"><span style="color:#94a3b8;">TPA
                        (Multicaixa):</span><strong style="color:#38bdf8;">800.000,00 Kz</strong></div>
            </div>
            <div style="display: flex; gap: 10px;">
                <button onclick="document.getElementById('modal-fecho').style.display = 'none'"
                    style="flex: 1; background: #020617; border: 1px solid #1e293b; color: #94a3b8; padding: 8px; border-radius: 8px; cursor: pointer;">Voltar</button>
                <button onclick="nkamaAlert('Caixa fechado com sucesso. Imprimindo relatório Z...', 'success').then(() => window.location.reload());"
                    style="flex: 1; background: #ef4444; color: #fff; font-weight: bold; padding: 8px; border-radius: 8px; border: none; cursor: pointer;">Confirmar
                    Fecho (Z)</button>
            </div>
        </div>
    </div>

    <div id="modal-fecho-real"
        style="position: fixed; inset: 0; background: rgba(2, 6, 23, 0.86); backdrop-filter: blur(4px); display: none; align-items: center; justify-content: center; padding: 20px; z-index: 60;">
        <div
            style="background: #0f172a; border: 1px solid #1e293b; border-radius: 12px; width: 100%; max-width: 440px; padding: 18px;">
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #1e293b; padding-bottom: 10px;">
                <div>
                    <h3 style="color: #fff; margin: 0; font-size: 17px;">Fecho de Caixa</h3>
                    <div id="fecho-shift-id" style="color: #94a3b8; font-size: 12px; margin-top: 3px;">Carregando turno...</div>
                </div>
                <button onclick="fecharModalFecho()"
                    style="background: transparent; border: none; color: #94a3b8; font-size: 24px; cursor: pointer;">&times;</button>
            </div>

            <div style="display: grid; gap: 8px; font-size: 13px; margin: 14px 0;">
                <div style="display: flex; justify-content: space-between;"><span style="color:#94a3b8;">Fundo Inicial</span><strong id="fecho-opening" style="color:#fff;">0,00 Kz</strong></div>
                <div style="display: flex; justify-content: space-between;"><span style="color:#94a3b8;">Vendas Dinheiro</span><strong id="fecho-cash" style="color:#34d399;">0,00 Kz</strong></div>
                <div style="display: flex; justify-content: space-between;"><span style="color:#94a3b8;">Multicaixa</span><strong id="fecho-card" style="color:#38bdf8;">0,00 Kz</strong></div>
                <div style="display: flex; justify-content: space-between;"><span style="color:#94a3b8;">Transferencia</span><strong id="fecho-transf" style="color:#a78bfa;">0,00 Kz</strong></div>
                <div style="display: flex; justify-content: space-between; border-top: 1px solid #1e293b; padding-top: 8px;"><span style="color:#94a3b8;">Total Vendido</span><strong id="fecho-total" style="color:#fff;">0,00 Kz</strong></div>
                <div style="display: flex; justify-content: space-between;"><span style="color:#94a3b8;">Dinheiro Esperado</span><strong id="fecho-expected" style="color:#fbbf24;">0,00 Kz</strong></div>
            </div>

            <label style="display: block; color: #94a3b8; font-size: 11px; font-weight: bold; text-transform: uppercase; margin-bottom: 5px;">Dinheiro contado na gaveta</label>
            <input type="number" id="fecho-counted-cash" inputmode="decimal" min="0" step="0.01" oninput="calcularDiferencaFecho()"
                style="width: 100%; background: #020617; border: 1px solid #1e293b; color: #fff; padding: 12px; border-radius: 8px; font-size: 18px; font-weight: bold;">

            <div style="background: #020617; border: 1px solid #1e293b; border-radius: 8px; padding: 10px; margin-top: 10px; display: flex; justify-content: space-between;">
                <span style="color: #94a3b8;">Diferenca</span>
                <strong id="fecho-difference" style="color: #fbbf24;">0,00 Kz</strong>
            </div>

            <textarea id="fecho-notes" rows="2" placeholder="Observacoes do fecho"
                style="width: 100%; background: #020617; border: 1px solid #1e293b; color: #fff; padding: 10px; border-radius: 8px; margin-top: 10px; resize: none;"></textarea>

            <div style="display: flex; gap: 10px; margin-top: 14px;">
                <button onclick="fecharModalFecho()"
                    style="flex: 1; background: #020617; border: 1px solid #1e293b; color: #94a3b8; padding: 10px; border-radius: 8px; cursor: pointer; font-weight: bold;">Voltar</button>
                <button id="btn-confirmar-fecho" onclick="confirmarFechoCaixa()"
                    style="flex: 1; background: #ef4444; color: #fff; font-weight: bold; padding: 10px; border-radius: 8px; border: none; cursor: pointer;">Confirmar Fecho</button>
            </div>
        </div>
    </div>

    <div id="modal-abertura-caixa"
        style="position: fixed; inset: 0; background: rgba(2, 6, 23, 0.9); backdrop-filter: blur(4px); display: none; align-items: center; justify-content: center; padding: 20px; z-index: 70;">
        <div
            style="background: #0f172a; border: 1px solid #1e293b; border-radius: 12px; width: 100%; max-width: 360px; padding: 20px;">
            <h3 style="color: #fff; margin: 0; font-size: 18px;">Abertura de Caixa</h3>
            <p style="color: #94a3b8; font-size: 13px; margin: 8px 0 16px;">Abra o caixa para poder usar mesas, supermercado e finalizar vendas.</p>

            <label style="display: block; color: #94a3b8; font-size: 11px; font-weight: bold; text-transform: uppercase; margin-bottom: 5px;">Fundo inicial</label>
            <input type="number" id="input-opening-cash" inputmode="decimal" min="0" step="0.01" value="0"
                style="width: 100%; background: #020617; border: 1px solid #1e293b; color: #fff; padding: 12px; border-radius: 8px; font-size: 18px; font-weight: bold;">

            <div style="display: flex; gap: 10px; margin-top: 14px;">
                <button type="button" onclick="fecharModalAberturaCaixa()"
                    style="flex: 1; background: #020617; border: 1px solid #1e293b; color: #94a3b8; font-weight: bold; padding: 12px; border-radius: 8px; cursor: pointer;">
                    Agora nao
                </button>
                <button id="btn-abrir-caixa" type="button" onclick="abrirCaixaOperador()"
                    style="flex: 1; background: #10b981; color: #020617; font-weight: bold; padding: 12px; border-radius: 8px; border: none; cursor: pointer;">
                    Abrir Caixa
                </button>
            </div>
        </div>
    </div>

    <script src="{{ asset('js/pos/payment.js') }}"></script>
    <script>
        // Estado global das mesas
        const estadosMesas = {
            9999: {
                itens: [],
                subtotal: 0,
                status: 'free',
                order_id: null
            }
        };

        // Inicialização via Blade (Laravel)
        @foreach ($tables as $table)
            estadosMesas[{{ $table->id }}] = {
                itens: [],
                subtotal: 0,
                status: '{{ $table->status }}',
                order_id: {{ $table->current_order_id ?? 'null' }}
            };
        @endforeach

        const modulosAtivos = @json($modules ?? ['restaurant' => true, 'supermarket' => true]);
        const customerCardLookupUrl = @json(route('admin.customer-cards.lookup'));
        const customerCardOtpUrl = @json(route('admin.customer-cards.otp'));
        const customerCardAuthorizationRequestUrl = @json(route('admin.customer-cards.authorizations.request'));
        const customerCardAuthorizationPendingUrl = @json(route('admin.customer-cards.authorizations.pending'));
        const customerCardAuthorizationStatusUrlTemplate = @json(route('admin.customer-cards.authorizations.status', ['authorization' => '__ID__']));
        const customerCardAuthorizationApproveUrlTemplate = @json(route('admin.customer-cards.authorizations.approve', ['authorization' => '__ID__']));
        const customerCardAuthorizationRejectUrlTemplate = @json(route('admin.customer-cards.authorizations.reject', ['authorization' => '__ID__']));
        let cartaoClienteSelecionado = null;
        let cartaoAutorizacaoAprovadaId = null;
        let cartaoAutorizacaoPendenteId = null;
        let cartaoAutorizacaoTimer = null;
        const directPrintSaleUrlTemplate = @json(route('admin.print.sales', ['sale' => '__SALE_ID__']));
        const directPrintTableUrlTemplate = @json(route('admin.restaurant.print.table', ['table' => '__TABLE_ID__']));
        let modoAtual = modulosAtivos.restaurant ? 'salao' : 'supermercado';
        let mesaSelecionadaId = modoAtual === 'supermercado' && modulosAtivos.supermarket ? 9999 : null;
        let filtroMesasAtual = 'all';
        let categoriaSupermercadoAtual = 'all';
        let totalGeralVendaActual = 0;
        let ultimaVendaId = null;
        let campoPagamentoAtivo = 'main';
        let caixaAberto = false;
        let posCheckoutProcessing = false;

        // Atalhos de Teclado
        window.addEventListener('keydown', function(event) {
            if (event.key === 'F3') {
                event.preventDefault();
                processarFechamentoVenda();
            }
        });

        function mudarModoOperacao(modo) {
            if (!garantirCaixaAberto()) return;

            if (modo === 'salao' && !modulosAtivos.restaurant) {
                nkamaAlert('Módulo Restaurante desativado pelo super-user.', 'warning');
                return;
            }

            if (modo === 'supermercado' && !modulosAtivos.supermarket) {
                nkamaAlert('Módulo Supermercado desativado pelo super-user.', 'warning');
                return;
            }

            modoAtual = modo;
            const tabSalao = document.getElementById('tab-salao');
            const tabSupermercado = document.getElementById('tab-supermercado');

            if (tabSalao) {
                tabSalao.style.background = "transparent";
                tabSalao.style.color = "#94a3b8";
            }

            if (tabSupermercado) {
                tabSupermercado.style.background = "transparent";
                tabSupermercado.style.color = "#94a3b8";
            }

            if (modo === 'salao') {
                if (tabSalao) {
                    tabSalao.style.background = "#38bdf8";
                    tabSalao.style.color = "#020617";
                }
                const viewSalao = document.getElementById('view-salao-wrapper');
                const viewSupermercado = document.getElementById('view-supermercado');
                if (viewSalao) viewSalao.style.display = 'flex';
                if (viewSupermercado) {
                    viewSupermercado.classList.add('hidden');
                    viewSupermercado.style.display = 'none';
                }
                document.getElementById('restaurant-categories').style.display = 'none';
                document.getElementById('restaurant-products').style.display = 'none';
                document.getElementById('supermarket-products').style.display = 'none';
                document.getElementById('txt-titulo-modulo').innerText = 'Salão Principal';
                document.getElementById('lbl-cliente-tipo').innerText = 'Selecione uma mesa no salão';
                mesaSelecionadaId = null;
                document.getElementById('lbl-mesa-ativa').innerText = 'Nenhuma Selecionada';
                document.getElementById('mesa-acoes').style.display = 'flex';
                document.querySelectorAll('[id^="card-mesa-"]').forEach(c => c.style.outline = 'none');
                filtrarMesas(filtroMesasAtual);
            } else {
                if (tabSupermercado) {
                    tabSupermercado.style.background = "#38bdf8";
                    tabSupermercado.style.color = "#020617";
                }
                const viewSalao = document.getElementById('view-salao-wrapper');
                const viewSupermercado = document.getElementById('view-supermercado');
                if (viewSalao) viewSalao.style.display = 'none';
                if (viewSupermercado) {
                    viewSupermercado.classList.remove('hidden');
                    viewSupermercado.style.display = 'flex';
                }
                document.getElementById('restaurant-categories').style.display = 'none';
                document.getElementById('restaurant-products').style.display = 'none';
                document.getElementById('supermarket-products').style.display = 'grid';
                document.getElementById('txt-titulo-modulo').innerText = 'Caixa Registadora • Supermercado';
                document.getElementById('lbl-cliente-tipo').innerText = 'Cliente Geral • Venda Activa';
                mesaSelecionadaId = 9999;
                document.getElementById('lbl-mesa-ativa').innerText = 'Caixa Aberto 🛒';
                document.getElementById('mesa-acoes').style.display = 'none';

                setTimeout(() => {
                    const inputBc = document.getElementById('inputBarcode');
                    if (inputBc) {
                        inputBc.value = '';
                        inputBc.focus();
                    }
                    filtrarProdutosSupermercado();
                }, 100);
            }
            renderizarCarrinho();
        }

        function filtrarMesas(filtro) {
            filtroMesasAtual = filtro;

            document.querySelectorAll('.mesa-card').forEach(card => {
                const deveMostrar = filtro === 'all' || card.dataset.status === filtro;
                card.style.display = deveMostrar ? 'block' : 'none';
            });

            const filtros = {
                all: document.getElementById('filter-mesas-all'),
                free: document.getElementById('filter-mesas-free'),
                occupied: document.getElementById('filter-mesas-occupied')
            };

            Object.keys(filtros).forEach(tipo => {
                const btn = filtros[tipo];
                if (!btn) return;

                const ativo = tipo === filtro;
                btn.style.background = ativo ? '#38bdf8' : 'transparent';
                btn.style.color = ativo ? '#020617' : (tipo === 'free' ? '#34d399' : tipo === 'occupied' ? '#fbbf24' : '#cbd5e1');
            });
        }

        function selecionarCategoriaSupermercado(category) {
            categoriaSupermercadoAtual = category;

            document.querySelectorAll('.supermarket-category-btn').forEach(btn => {
                btn.classList.toggle('active', btn.dataset.category === category);
            });

            filtrarProdutosSupermercado();
        }

        function filtrarProdutosSupermercado() {
            const termo = (document.getElementById('inputBarcode')?.value || '').toLowerCase().trim();

            document.querySelectorAll('.supermarket-product').forEach(productButton => {
                const matchesCategory = categoriaSupermercadoAtual === 'all' || productButton.dataset.category === categoriaSupermercadoAtual;
                const matchesSearch = !termo || (productButton.dataset.search || '').includes(termo);

                productButton.style.display = matchesCategory && matchesSearch ? 'block' : 'none';
            });
        }

        function voltarParaMesas() {
            mesaSelecionadaId = null;
            document.getElementById('view-salao-wrapper').style.display = 'flex';
            document.getElementById('restaurant-categories').style.display = 'none';
            document.getElementById('restaurant-products').style.display = 'none';
            document.getElementById('txt-titulo-modulo').innerText = 'Salao Principal';
            document.getElementById('lbl-mesa-ativa').innerText = 'Nenhuma Selecionada';
            document.getElementById('lbl-cliente-tipo').innerText = 'Selecione uma mesa no salao';
            document.getElementById('mesa-acoes').style.display = 'flex';
            document.querySelectorAll('[id^="card-mesa-"]').forEach(c => c.style.outline = 'none');
            document.querySelectorAll('.restaurant-product').forEach(productButton => {
                productButton.style.display = 'none';
            });
            filtrarMesas(filtroMesasAtual);
            renderizarCarrinho();
        }

        function mostrarCategoriaRestaurante(categoryId) {
            const productsContainer = document.getElementById('restaurant-products');
            if (!productsContainer) return;

            productsContainer.style.display = 'flex';
            document.querySelectorAll('.restaurant-product').forEach(productButton => {
                productButton.style.display = productButton.classList.contains(`category-${categoryId}`) ? 'block' : 'none';
            });
        }

        function selecionarMesa(id, nome) {
            if (!garantirCaixaAberto()) return;

            mesaSelecionadaId = id;
            document.querySelectorAll('[id^="card-mesa-"]').forEach(c => c.style.outline = 'none');

            const cardMesa = document.getElementById(`card-mesa-${id}`);
            if (cardMesa) {
                cardMesa.style.outline = '2px solid #38bdf8';
            }

            document.getElementById('lbl-mesa-ativa').innerText = nome;
            document.getElementById('lbl-cliente-tipo').innerText = 'Mesa Ativa • Conta Operacional';
            document.getElementById('mesa-acoes').style.display = 'flex';

            document.getElementById('txt-titulo-modulo').innerText = `Mesa ${nome} - Categorias`;
            document.getElementById('view-salao-wrapper').style.display = 'none';
            document.getElementById('restaurant-categories').style.display = 'grid';
            document.getElementById('restaurant-products').style.display = 'none';
            document.querySelectorAll('.restaurant-product').forEach(productButton => {
                productButton.style.display = 'none';
            });

            abrirOuCarregarMesaNaBD(id);
        }

        function abrirOuCarregarMesaNaBD(tableId) {
            return fetch(`/admin/restaurant/order/${tableId}/open`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': '{{ csrf_token() }}'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        estadosMesas[tableId].status = data.table_status;
                        estadosMesas[tableId].order_id = data.order ? data.order.id : null;

                        if (data.items && data.items.length > 0) {
                            estadosMesas[tableId].itens = data.items.map(item => ({
                                id: item.product_id,
                                name: item.product ? item.product.name : 'Produto',
                                price: parseFloat(item.price || 0),
                                taxRate: parseFloat(item.product?.tax_rate || item.taxRate || 0),
                                qty: parseInt(item.qty || item.quantity || 1)
                            }));
                        } else {
                            estadosMesas[tableId].itens = [];
                        }

                        atualizarVisualMesaCard(tableId, data.table_status);
                        atualizarContadoresTop();
                        renderizarCarrinho();
                        return true;
                    } else {
                    nkamaAlert("Erro ao carregar mesa: " + data.message, 'error');
                        return false;
                    }
                })
                .catch(err => {
                    console.error("Erro ao carregar dados do servidor:", err);
                nkamaAlert("Erro ao carregar dados da mesa. Verifique se o caixa esta aberto.", 'error');
                    return false;
                });
        }

        function atualizarVisualMesaCard(tableId, status) {
            const cardMesa = document.getElementById(`card-mesa-${tableId}`);
            const txtStatus = document.getElementById(`status-text-${tableId}`);

            if (cardMesa && txtStatus) {
                if (status === 'occupied' || status === 'busy' || status === 'waiting_payment') {
                    cardMesa.dataset.status = 'occupied';
                    cardMesa.style.background = "rgba(251, 191, 36, 0.1)";
                    cardMesa.style.border = "1px solid rgba(251, 191, 36, 0.3)";
                    cardMesa.style.color = "#fbbf24";
                    txtStatus.innerText = 'Ocupada';
                } else {
                    cardMesa.dataset.status = 'free';
                    cardMesa.style.background = "rgba(16, 185, 129, 0.1)";
                    cardMesa.style.border = "1px solid rgba(16, 185, 129, 0.3)";
                    cardMesa.style.color = "#10b981";
                    txtStatus.innerText = 'Livre';
                }
                filtrarMesas(filtroMesasAtual);
            }
        }

        function atualizarContadoresTop() {
            let livres = 0;
            let ocupadas = 0;
            Object.keys(estadosMesas).forEach(id => {
                if (id != 9999) {
                    if (estadosMesas[id].status === 'free') livres++;
                    else ocupadas++;
                }
            });
            const mLivres = document.getElementById('metric-livres');
            const mOcupadas = document.getElementById('metric-ocupadas');
            if (mLivres) mLivres.innerHTML = `Mesas Livres: <strong style="color: #34d399;">${livres}</strong>`;
            if (mOcupadas) mOcupadas.innerHTML = `Mesas Ocupadas: <strong style="color: #fbbf24;">${ocupadas}</strong>`;
        }

        function adicionarItemNoPedido(idProduto, nomeProduto, preco, taxaIva = 0, tentativaAposAbrirMesa = false) {
            if (!garantirCaixaAberto()) return;

            if (!mesaSelecionadaId && modoAtual === 'salao') {
                nkamaAlert("Por favor, selecione uma mesa no mapa primeiro.", 'warning');
                return;
            }

            if (modoAtual === 'supermercado') {
                mesaSelecionadaId = 9999;
                if (!estadosMesas[9999]) {
                    estadosMesas[9999] = {
                        itens: [],
                        subtotal: 0,
                        status: 'free',
                        order_id: null
                    };
                }
            }

            const mesaCorrente = estadosMesas[mesaSelecionadaId];

            if (!mesaCorrente) {
                nkamaAlert("Nao foi possivel iniciar o carrinho. Atualize a pagina e tente novamente.", 'error');
                return;
            }

            if (modoAtual === 'salao') {
                if (!mesaCorrente.order_id) {
                    if (tentativaAposAbrirMesa) {
                        nkamaAlert("Nao foi possivel abrir a conta da mesa. Tente selecionar a mesa novamente.", 'error');
                        return;
                    }

                    abrirOuCarregarMesaNaBD(mesaSelecionadaId)
                        .then(abriu => {
                            if (abriu && mesaCorrente.order_id) {
                                adicionarItemNoPedido(idProduto, nomeProduto, preco, taxaIva, true);
                            }
                        });
                    return;
                }

                fetch('/admin/restaurant/add-item', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-Token': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            order_id: mesaCorrente.order_id,
                            product_id: idProduto,
                            quantity: 1,
                            price: preco
                        })
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            mesaCorrente.status = 'occupied';
                            atualizarVisualMesaCard(mesaSelecionadaId, 'occupied');
                            atualizarContadoresTop();

                            // Atualização local controlada
                            const itemExistente = mesaCorrente.itens.find(i => i.id === idProduto);
                            if (itemExistente) {
                                itemExistente.qty++;
                            } else {
                                mesaCorrente.itens.push({
                                    id: idProduto,
                                    name: nomeProduto,
                                    price: parseFloat(preco),
                                    taxRate: parseFloat(taxaIva) || 0,
                                    qty: 1
                                });
                            }
                            renderizarCarrinho();
                        } else {
                            nkamaAlert("Erro ao adicionar item no servidor: " + (data.message || "Motivo desconhecido"), 'error');
                        }
                    })
                    .catch(err => {
                        console.error("Erro ao processar item no servidor:", err);
                        nkamaAlert("Erro ao adicionar item. Verifique se o caixa esta aberto e tente novamente.", 'error');
                    });
            } else {
                // Modo supermercado (Apenas local)
                const itemExistente = mesaCorrente.itens.find(i => i.id === idProduto);
                if (itemExistente) {
                    itemExistente.qty++;
                } else {
                    mesaCorrente.itens.push({
                        id: idProduto,
                        name: nomeProduto,
                        price: parseFloat(preco),
                        taxRate: parseFloat(taxaIva) || 0,
                        qty: 1
                    });
                }
                renderizarCarrinho();
            }
        }

        function verificarInputBarcode(event) {
            if (event.key === 'Enter') {
                const barcode = event.target.value.trim();
                if (!barcode) return;

                fetch('/admin/pos/supermercado/find-product', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-Token': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            barcode: barcode
                        })
                    })
                    .then(res => res.json())
                    .then(product => {
                        if (product.success && product.data) {
                            adicionarItemNoPedido(product.data.id, product.data.name, product.data.selling_price, product.data.tax_rate || 0);
                            event.target.value = '';
                        } else {
                            nkamaAlert("Produto não localizado.", 'warning');
                        }
                    })
                    .catch(err => console.error("Erro na leitura:", err));
            }
        }

        function renderizarCarrinho() {
            const container = document.getElementById('lista-itens-pedido');
            if (!container) return;

            if (!mesaSelecionadaId || !estadosMesas[mesaSelecionadaId] || estadosMesas[mesaSelecionadaId].itens.length ===
                0) {
                container.innerHTML =
                    `<div style="text-align: center; color: #64748b; font-size: 13px; margin-top: 40px;">O carrinho está vazio.</div>`;
                document.getElementById('txt-subtotal').innerText = '0,00 Kz';
                document.getElementById('txt-total').innerText = '0,00 Kz';
                totalGeralVendaActual = 0;
                return;
            }

            let html = '';
            let subtotal = 0;

            estadosMesas[mesaSelecionadaId].itens.forEach((item, index) => {
                let totalItem = item.price * item.qty;
                subtotal += totalItem;
                html += `
            <div style="display: flex; justify-content: space-between; align-items: center; background: rgba(2,6,23,0.3); border: 1px solid #1e293b; padding: 8px; border-radius: 8px; font-size: 13px; margin-bottom: 4px;">
                <span><span style="color:#64748b;">${item.qty}x</span> ${item.name}</span>
                <div style="display: flex; align-items: center;">
                    <strong style="color:#fff; margin-right: 10px;">${totalItem.toLocaleString('pt-PT')} Kz</strong>
                    <button style="color:#ef4444; background:none; border:none; font-weight:bold; cursor:pointer; font-size: 16px;" onclick="removerItem(${index})">×</button>
                </div>
            </div>`;
            });

            container.innerHTML = html;
            totalGeralVendaActual = subtotal;
            document.getElementById('txt-subtotal').innerText = subtotal.toLocaleString('pt-PT') + ' Kz';
            document.getElementById('txt-total').innerText = totalGeralVendaActual.toLocaleString('pt-PT') + ' Kz';
        }

        function escaparHtml(valor) {
            const div = document.createElement('div');
            div.textContent = valor ?? '';
            return div.innerHTML;
        }

        function calcularTotaisCarrinho(itens) {
            return (itens || []).reduce((totais, item) => {
                const price = Number(item.price) || 0;
                const qty = Number(item.qty) || 1;
                const taxRate = Math.max(0, Number(item.taxRate) || 0);
                const gross = price * qty;
                const tax = taxRate > 0 ? gross * taxRate / (100 + taxRate) : 0;

                totais.total += gross;
                totais.iva += tax;
                totais.subtotal += gross - tax;

                return totais;
            }, { subtotal: 0, iva: 0, total: 0 });
        }

        function renderizarCarrinho() {
            const container = document.getElementById('lista-itens-pedido');
            if (!container) return;

            const mesaAtual = mesaSelecionadaId ? estadosMesas[mesaSelecionadaId] : null;
            if (!mesaAtual || mesaAtual.itens.length === 0) {
                container.innerHTML = `<div class="pos-cart-empty">O carrinho esta vazio.</div>`;
                document.getElementById('txt-subtotal').innerText = '0,00 Kz';
                document.getElementById('txt-total').innerText = '0,00 Kz';
                totalGeralVendaActual = 0;
                return;
            }

            let subtotal = 0;
            const html = mesaAtual.itens.map((item, index) => {
                const price = Number(item.price) || 0;
                const qty = Number(item.qty) || 1;
                const totalItem = price * qty;
                subtotal += totalItem;

                const safeName = escaparHtml(item.name);

                return `
                    <div class="pos-cart-item" title="${safeName}">
                        <div style="min-width: 0;">
                            <span class="pos-cart-item-title">${safeName}</span>
                            <span class="pos-cart-item-meta">${qty} x ${price.toLocaleString('pt-PT')} Kz</span>
                        </div>
                        <div class="pos-cart-item-side">
                            <strong class="pos-cart-item-total">${totalItem.toLocaleString('pt-PT')} Kz</strong>
                            <button class="pos-cart-remove" onclick="removerItem(${index})">Remover</button>
                        </div>
                    </div>`;
            }).join('');

            const totais = calcularTotaisCarrinho(mesaAtual.itens);
            container.innerHTML = html;
            totalGeralVendaActual = totais.total;
            document.getElementById('txt-subtotal').innerText = totais.subtotal.toLocaleString('pt-PT') + ' Kz';
            document.getElementById('txt-total').innerText = totalGeralVendaActual.toLocaleString('pt-PT') + ' Kz';
        }

        function removerItem(index) {
            if (!mesaSelecionadaId || !estadosMesas[mesaSelecionadaId]) return;

            const mesaCorrente = estadosMesas[mesaSelecionadaId];
            const itemRemovido = mesaCorrente.itens[index];

            // 1. Otimização: Remove do array local imediatamente (Feedback Visual)
            mesaCorrente.itens.splice(index, 1);
            renderizarCarrinho(); // Atualiza a UI imediatamente

            if (modoAtual === 'salao') {
                // 2. Sincronização com o Backend
                fetch(`/admin/restaurant/remove-item`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-Token': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            order_id: mesaCorrente.order_id,
                            product_id: itemRemovido.id
                        })
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            // Se o backend indicou que a mesa ficou vazia (count === 0),
                            // o servidor já a libertou. Atualizamos o estado local para bater certo.
                            if (mesaCorrente.itens.length === 0) {
                                mesaCorrente.status = 'free';
                                mesaCorrente.order_id = null;
                                atualizarVisualMesaCard(mesaSelecionadaId, 'free');
                                atualizarContadoresTop();

                                // Notificação opcional
                                console.log("Mesa libertada na BD com sucesso.");
                            }
                        } else {
                            nkamaAlert("Erro ao sincronizar remoção: " + data.message, 'error');
                            // Opcional: recarregar estado da mesa se ocorrer erro para evitar dessincronização
                            abrirOuCarregarMesaNaBD(mesaSelecionadaId);
                        }
                    })
                    .catch(err => {
                        console.error("Erro de rede ao remover item:", err);
                        nkamaAlert("Erro de conexão. Verifique se a mesa ainda existe.", 'error');
                    });
            }
        }

        function processarFechamentoVenda() {
            if (!garantirCaixaAberto()) return;

            if (!mesaSelecionadaId || !estadosMesas[mesaSelecionadaId] || estadosMesas[mesaSelecionadaId].itens.length ===
                0) {
                nkamaAlert("O carrinho está vazio!", 'warning');
                return;
            }
            
            // Calcular valores
            const mesaCorrente = estadosMesas[mesaSelecionadaId];
            const totais = calcularTotaisCarrinho(mesaCorrente.itens);
            totalGeralVendaActual = totais.total;
            
            // Atualizar modal com preview
            document.getElementById('modal-txt-total').innerText = totalGeralVendaActual.toLocaleString('pt-PT') + ' Kz';
            document.getElementById('modal-subtotal-preview').innerText = totais.subtotal.toLocaleString('pt-PT') + ' Kz';
            document.getElementById('modal-iva-preview').innerText = totais.iva.toLocaleString('pt-PT') + ' Kz';
            
            // Preencher preview do carrinho
            preencherPreviewCarrinho();
            
            // Reset campos de pagamento
            document.getElementById('input-valor-pago').value = NkamaPOSPayment.roundUp(totalGeralVendaActual, 1);
            document.getElementById('input-pago-cash').value = 0;
            document.getElementById('input-pago-card').value = 0;
            document.getElementById('input-pago-transf').value = 0;
            if (document.getElementById('input-pago-customer-card')) {
                document.getElementById('input-pago-customer-card').value = 0;
            }
            selecionarMetodoPagamento('cash');
            
            // Mostrar modal
            const modal = document.getElementById('modal-pagamento');
            modal.style.display = 'flex';
            calcularTroco();
        }
        
        function preencherPreviewCarrinho() {
            const container = document.getElementById('modal-preview-carrinho');
            if (!mesaSelecionadaId || !estadosMesas[mesaSelecionadaId] || estadosMesas[mesaSelecionadaId].itens.length === 0) {
                container.innerHTML = `<div style="text-align: center; color: #64748b; font-size: 13px; margin-top: 40px;">O carrinho está vazio.</div>`;
                return;
            }
            
            let html = '';
            const itensPreview = estadosMesas[mesaSelecionadaId].itens.slice(0, 5);
            itensPreview.forEach((item) => {
                let totalItem = item.price * item.qty;
                html += `
                <div style="display: flex; justify-content: space-between; align-items: center; background: rgba(56,189,248,0.08); border: 1px solid rgba(56,189,248,0.2); padding: 7px; border-radius: 8px; font-size: 11px; min-height: 46px;">
                    <div style="min-width: 0;">
                        <div style="color: #fff; font-weight: bold;">${item.name}</div>
                        <div style="color: #94a3b8; font-size: 10px; margin-top: 2px;">${item.qty}x @ ${item.price.toLocaleString('pt-PT')} Kz</div>
                    </div>
                    <div style="color: #38bdf8; font-weight: bold; text-align: right; font-size: 10px; margin-left: 8px;">${totalItem.toLocaleString('pt-PT')} Kz</div>
                </div>`;
            });

            const itensRestantes = estadosMesas[mesaSelecionadaId].itens.length - itensPreview.length;
            if (itensRestantes > 0) {
                html += `<div style="text-align: center; color: #94a3b8; font-size: 11px; padding: 6px;">+${itensRestantes} item(ns) no carrinho</div>`;
            }
            
            container.innerHTML = html;
        }
        
        function selecionarMetodoPagamento(metodo) {
            if (metodo === 'credit' && !modulosAtivos.current_account) {
                nkamaAlert('Modulo Conta Corrente desativado pelo super-user.', 'warning');
                metodo = 'cash';
            }

            // Encontrar o botão clicado
            document.getElementById('select-metodo-pagamento').value = metodo;
            const botoes = document.querySelectorAll('.metodo-pagamento');
            botoes.forEach(btn => {
                const ativo = btn.dataset.metodo === metodo;
                const cor = btn.dataset.cor || '#1e293b';
                btn.classList.toggle('active', ativo);
                btn.style.setProperty('border-color', cor, 'important');
                btn.style.boxShadow = ativo ? `0 0 0 1px ${hexToRgba(cor, 0.35)}` : 'none';

                if (ativo) {
                    btn.style.setProperty('background', hexToRgba(cor, 0.16), 'important');
                } else {
                    btn.style.removeProperty('background');
                }
            });
            
            // Aplicar estilo ao botão com dados do método
            const botaoAtivo = document.querySelector(`.metodo-pagamento[data-metodo="${metodo}"]`);
            const resumoMetodo = document.getElementById('metodo-pagamento-ativo');
            if (resumoMetodo && botaoAtivo) {
                resumoMetodo.innerText = `${botaoAtivo.dataset.label} selecionado`;
            }

            const labelValorPago = document.getElementById('label-valor-pago');
            const boxTroco = document.getElementById('box-troco');
            const splitWrapper = document.getElementById('payment-split-wrapper');
            const inputPrincipal = document.getElementById('input-valor-pago');
            const labelsValores = {
                cash: 'Valor recebido em dinheiro',
                card: 'Valor confirmado no Multicaixa',
                transf: 'Valor confirmado na transferencia',
                multi: 'Pagamento misto',
                customer_card: 'Bonus de fidelidade e saldo do cartao',
                credit: 'Valor recebido agora'
            };

            if (labelValorPago) {
                labelValorPago.innerText = labelsValores[metodo] || 'Valor recebido';
            }

            if (boxTroco) {
                boxTroco.style.display = (metodo === 'cash' || metodo === 'multi') ? 'block' : 'none';
            }

            if (splitWrapper) {
                splitWrapper.style.display = metodo === 'multi' ? 'block' : 'none';
            }

            if (inputPrincipal) {
                inputPrincipal.style.display = metodo === 'multi' || metodo === 'credit' || metodo === 'customer_card' ? 'none' : 'block';
            }

            if (metodo === 'multi') {
                campoPagamentoAtivo = 'card';
                preencherPagamentoMistoInicial();
                selecionarCampoPagamento('card');
            } else if (metodo === 'credit') {
                campoPagamentoAtivo = 'main';
                document.getElementById('input-valor-pago').value = 0;
                selecionarCampoPagamento('main');
            } else {
                campoPagamentoAtivo = 'main';
                selecionarCampoPagamento('main');
            }

            atualizarClienteContaCorrente();
            calcularTroco();
            
            // Guardar o método selecionado
        }

        function fecharModalPagamento() {
            document.getElementById('modal-pagamento').style.display = 'none';
        }

        function resetarCamposPagamento() {
            document.getElementById('input-valor-pago').value = '';
            document.getElementById('input-pago-cash').value = 0;
            document.getElementById('input-pago-card').value = 0;
            document.getElementById('input-pago-transf').value = 0;
            if (document.getElementById('input-pago-customer-card')) {
                document.getElementById('input-pago-customer-card').value = 0;
            }
            document.getElementById('txt-total-recebido').innerText = '0,00 Kz';
            document.getElementById('txt-total-falta').innerText = '0,00 Kz';
            document.getElementById('txt-troco-calculado').innerText = '0,00 Kz';
            document.getElementById('payment-split-wrapper').style.display = 'none';
            document.getElementById('input-valor-pago').style.display = 'block';
            if (document.getElementById('wrapper-cliente-conta')) {
                document.getElementById('wrapper-cliente-conta').style.display = 'none';
            }
            if (document.getElementById('select-cliente-conta')) {
                document.getElementById('select-cliente-conta').value = '';
            }
            campoPagamentoAtivo = 'main';
            selecionarCampoPagamento('main');
        }

        function abrirModalSucessoVenda(data, payload) {
            const labels = {
                cash: 'Dinheiro',
                card: 'Multicaixa',
                transf: 'Transferencia',
                multi: 'Pagamento Misto',
                mixed: 'Pagamento Misto',
                credit: 'Conta Corrente',
                mixed_credit: 'Misto + Conta',
                customer_card: 'Cartao Cliente'
            };

            document.getElementById('sucesso-invoice').innerText = data.invoice || `Venda #${data.sale_id || '-'}`;
            document.getElementById('sucesso-total').innerText = NkamaPOSPayment.format(payload.total);
            document.getElementById('sucesso-recebido').innerText = NkamaPOSPayment.format(payload.amount_paid);
            document.getElementById('sucesso-metodo').innerText = labels[data.payment_method || payload.payment_method] || payload.payment_method;
            ultimaVendaId = data.sale_id || null;
            document.getElementById('modal-sucesso-venda').style.display = 'flex';
        }

        function abrirTicketVenda() {
            if (!ultimaVendaId) return;
            if (modulosAtivos.view_ticket) {
                window.open(`/admin/sales/${ultimaVendaId}/ticket`, '_blank');
                return;
            }

            window.nkamaPrintTicket(directPrintSaleUrlTemplate.replace('__SALE_ID__', ultimaVendaId));
        }

        function fecharConsultaMesa() {
            document.getElementById('modal-consulta-mesa').style.display = 'none';
        }

        function consultarMesaAtual() {
            if (modoAtual !== 'salao' || !mesaSelecionadaId || mesaSelecionadaId === 9999) {
                nkamaAlert('Selecione uma mesa para consultar.', 'warning');
                return;
            }

            fetch(`/admin/restaurant/table/${mesaSelecionadaId}/summary`)
                .then(res => res.json())
                .then(data => {
                    if (!data.success) {
                        nkamaAlert(data.message || 'Nao foi possivel consultar a mesa.', 'error');
                        return;
                    }

                    const itens = data.items || [];
                    const totais = calcularTotaisCarrinho(itens.map(item => ({
                        price: item.price,
                        qty: item.qty,
                        taxRate: item.taxRate
                    })));

                    document.getElementById('consulta-mesa-titulo').innerText = `Mesa ${data.table?.name || '-'}`;
                    document.getElementById('consulta-mesa-info').innerText = data.order_id ? `Conta #${data.order_id}` : 'Sem conta aberta';
                    document.getElementById('consulta-subtotal').innerText = totais.subtotal.toLocaleString('pt-PT') + ' Kz';
                    document.getElementById('consulta-iva').innerText = totais.iva.toLocaleString('pt-PT') + ' Kz';
                    document.getElementById('consulta-total').innerText = totais.total.toLocaleString('pt-PT') + ' Kz';

                    const container = document.getElementById('consulta-mesa-itens');
                    if (itens.length === 0) {
                        container.innerHTML = '<div style="color:#64748b; text-align:center; padding:20px 0;">Mesa sem itens.</div>';
                    } else {
                        container.innerHTML = itens.map(item => {
                            const total = Number(item.subtotal || 0);
                            const name = escaparHtml(item.name);
                            return `
                                <div style="background:#020617; border:1px solid #1e293b; border-radius:8px; padding:9px;">
                                    <div style="color:#fff; font-weight:bold;">${name}</div>
                                    <div style="display:flex; justify-content:space-between; color:#94a3b8; font-size:12px; margin-top:4px;">
                                        <span>${item.qty} x ${(Number(item.price) || 0).toLocaleString('pt-PT')} Kz</span>
                                        <strong style="color:#e2e8f0;">${total.toLocaleString('pt-PT')} Kz</strong>
                                    </div>
                                </div>`;
                        }).join('');
                    }

                    document.getElementById('modal-consulta-mesa').style.display = 'flex';
                })
                .catch(err => {
                    console.error('Erro ao consultar mesa:', err);
                    nkamaAlert('Erro de conexão ao consultar a mesa.', 'error');
                });
        }

        function imprimirConsultaMesa() {
            if (modoAtual !== 'salao' || !mesaSelecionadaId || mesaSelecionadaId === 9999) {
                nkamaAlert('Selecione uma mesa para imprimir a consulta.', 'warning');
                return;
            }

            if (modulosAtivos.view_ticket) {
                window.open(`/admin/restaurant/table/${mesaSelecionadaId}/ticket`, '_blank');
                return;
            }

            window.nkamaPrintTicket(directPrintTableUrlTemplate.replace('__TABLE_ID__', mesaSelecionadaId));
        }

        function abrirTransferenciaMesa() {
            if (modoAtual !== 'salao' || !mesaSelecionadaId || mesaSelecionadaId === 9999) {
                nkamaAlert('Selecione uma mesa com conta para transferir.', 'warning');
                return;
            }

            const mesaAtual = estadosMesas[mesaSelecionadaId];
            if (!mesaAtual || !mesaAtual.order_id || !mesaAtual.itens || mesaAtual.itens.length === 0) {
                nkamaAlert('Esta mesa nao tem conta com itens para transferir.', 'warning');
                return;
            }

            document.getElementById('transferencia-origem').innerText = `Origem: ${document.getElementById('lbl-mesa-ativa').innerText}`;
            renderizarItensTransferencia(mesaAtual.itens || []);
            const select = document.getElementById('transferencia-destino');
            select.value = '';

            Array.from(select.options).forEach(option => {
                if (!option.value) return;
                const estado = estadosMesas[option.value];
                const ocupada = Number(option.value) === Number(mesaSelecionadaId) || (estado && (estado.order_id || estado.status === 'occupied'));
                option.disabled = ocupada;
                option.hidden = ocupada;
            });

            document.getElementById('modal-transferencia-mesa').style.display = 'flex';
        }

        function fecharTransferenciaMesa() {
            document.getElementById('modal-transferencia-mesa').style.display = 'none';
        }

        function renderizarItensTransferencia(itens) {
            const container = document.getElementById('transferencia-itens');
            if (!container) return;

            container.innerHTML = itens.map((item, index) => {
                const productId = item.product_id || item.id;
                const qty = Number(item.qty || item.quantity || 1);
                const price = Number(item.price || 0);
                const total = qty * price;

                return `
                    <div class="transfer-item-row">
                        <input type="checkbox" class="transfer-item-check" data-index="${index}" data-product-id="${productId}" checked>
                        <div style="min-width:0;">
                            <div class="transfer-item-name">${item.name || 'Produto'}</div>
                            <div class="transfer-item-meta">Disponivel: ${qty} · ${price.toLocaleString('pt-PT')} Kz · ${total.toLocaleString('pt-PT')} Kz</div>
                        </div>
                        <input type="number" class="transfer-item-qty" min="1" max="${qty}" value="${qty}" data-index="${index}" data-product-id="${productId}"
                            aria-label="Quantidade a transferir">
                    </div>
                `;
            }).join('');
        }

        function selecionarTodosItensTransferencia() {
            document.querySelectorAll('.transfer-item-check').forEach((checkbox) => {
                checkbox.checked = true;
            });
        }

        function itensSelecionadosTransferencia() {
            const mesaAtual = estadosMesas[mesaSelecionadaId];
            const itens = mesaAtual?.itens || [];

            return Array.from(document.querySelectorAll('.transfer-item-check:checked')).map((checkbox) => {
                const index = Number(checkbox.dataset.index);
                const item = itens[index];
                const qtyInput = document.querySelector(`.transfer-item-qty[data-index="${index}"]`);
                const available = Number(item?.qty || item?.quantity || 1);
                const quantity = Math.max(1, Math.min(Number(qtyInput?.value || available), available));

                return {
                    product_id: Number(checkbox.dataset.productId),
                    quantity
                };
            });
        }

        function confirmarTransferenciaMesa() {
            const destino = document.getElementById('transferencia-destino').value;
            if (!destino) {
                nkamaAlert('Selecione a mesa de destino.', 'warning');
                return;
            }

            const selectedItems = itensSelecionadosTransferencia();
            if (selectedItems.length === 0) {
                nkamaAlert('Selecione pelo menos um produto para transferir.', 'warning');
                return;
            }

            fetch('/admin/restaurant/transfer-order', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    from_table_id: mesaSelecionadaId,
                    to_table_id: destino,
                    items: selectedItems
                })
            })
                .then(res => res.json())
                .then(data => {
                    if (!data.success) {
                        nkamaAlert(data.message || 'Nao foi possivel transferir a conta.', 'error');
                        return;
                    }

                    const origem = mesaSelecionadaId;
                    const origemAtual = estadosMesas[origem] || {};
                    const itensOrigem = origemAtual.itens || [];
                    const movedByProduct = {};
                    selectedItems.forEach((item) => {
                        movedByProduct[item.product_id] = (movedByProduct[item.product_id] || 0) + item.quantity;
                    });

                    const itensDestino = [];
                    const itensRestantes = [];

                    itensOrigem.forEach((item) => {
                        const productId = Number(item.product_id || item.id);
                        const qty = Number(item.qty || item.quantity || 0);
                        const moveQty = Math.min(movedByProduct[productId] || 0, qty);

                        if (moveQty > 0) {
                            itensDestino.push({
                                ...item,
                                qty: moveQty,
                                quantity: moveQty,
                                total: moveQty * Number(item.price || 0)
                            });
                        }

                        if (qty - moveQty > 0) {
                            itensRestantes.push({
                                ...item,
                                qty: qty - moveQty,
                                quantity: qty - moveQty,
                                total: (qty - moveQty) * Number(item.price || 0)
                            });
                        }
                    });

                    estadosMesas[destino] = {
                        itens: itensDestino,
                        subtotal: itensDestino.reduce((sum, item) => sum + Number(item.total || 0), 0),
                        status: 'occupied',
                        order_id: data.order_id
                    };
                    estadosMesas[origem] = {
                        itens: itensRestantes,
                        subtotal: itensRestantes.reduce((sum, item) => sum + Number(item.total || 0), 0),
                        status: itensRestantes.length ? 'occupied' : 'free',
                        order_id: data.from_order_id
                    };

                    atualizarVisualMesaCard(origem, estadosMesas[origem].status);
                    atualizarVisualMesaCard(destino, 'occupied');
                    atualizarContadoresTop();
                    fecharTransferenciaMesa();
                    if (itensRestantes.length) {
                        renderizarCarrinho();
                    } else {
                        voltarParaMesas();
                    }
                    nkamaAlert(data.message || 'Conta transferida com sucesso.', 'success');
                })
                .catch(err => {
                    console.error('Erro ao transferir conta:', err);
                    nkamaAlert('Erro de conexão ao transferir a conta.', 'error');
                });
        }

        function fecharModalSucessoVenda() {
            document.getElementById('modal-sucesso-venda').style.display = 'none';
        }

        function garantirCaixaAberto() {
            if (caixaAberto) return true;

            nkamaAlert('Abra o caixa antes de iniciar mesas ou vendas.', 'warning');
            mostrarModalAberturaCaixa();
            return false;
        }

        function aplicarEstadoCaixaAberto(aberto) {
            caixaAberto = aberto;
            const opacity = aberto ? '1' : '0.45';
            const pointerEvents = aberto ? 'auto' : 'none';
            const btnAbrir = document.getElementById('btn-top-abrir-caixa');
            const btnFecho = document.getElementById('btn-top-fecho-caixa');

            ['view-salao-wrapper', 'restaurant-categories', 'restaurant-products', 'view-supermercado', 'supermarket-products', 'btn-finalizar-venda']
                .forEach(id => {
                    const el = document.getElementById(id);
                    if (!el) return;
                    el.style.opacity = opacity;
                    el.style.pointerEvents = pointerEvents;
                });

            document.querySelectorAll('.mesa-card').forEach(card => {
                card.style.pointerEvents = pointerEvents;
            });

            if (btnAbrir) btnAbrir.style.display = aberto ? 'none' : 'inline-block';
            if (btnFecho) {
                btnFecho.style.display = aberto ? 'inline-block' : 'none';
                btnFecho.style.opacity = '1';
                btnFecho.style.pointerEvents = 'auto';
            }
        }

        function mostrarModalAberturaCaixa() {
            document.getElementById('modal-abertura-caixa').style.display = 'flex';
            setTimeout(() => document.getElementById('input-opening-cash')?.focus(), 100);
        }

        function fecharModalAberturaCaixa() {
            document.getElementById('modal-abertura-caixa').style.display = 'none';
        }

        function verificarCaixaAbertoInicial() {
            fetch('/admin/current/shift', {
                    headers: {
                        'Accept': 'application/json'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    aplicarEstadoCaixaAberto(!!data.open);
                })
                .catch(() => {
                    aplicarEstadoCaixaAberto(false);
                });
        }

        function abrirCaixaOperador() {
            const btn = document.getElementById('btn-abrir-caixa');
            const openingCash = NkamaPOSPayment.parse(document.getElementById('input-opening-cash').value);

            btn.disabled = true;
            btn.innerText = 'Abrindo...';

            fetch('/admin/shift/open', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        opening_cash: openingCash
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (!data.success) {
                        nkamaAlert(data.message || 'Erro ao abrir caixa.', 'error');
                        return;
                    }

                    aplicarEstadoCaixaAberto(true);
                    fecharModalAberturaCaixa();
                })
                .catch(() => nkamaAlert('Erro de conexão ao abrir caixa.', 'error'))
                .finally(() => {
                    btn.disabled = false;
                    btn.innerText = 'Abrir Caixa';
                });
        }

        let resumoFechoAtual = {
            expected: 0
        };

        function abrirModalFecho() {
            document.getElementById('modal-fecho-real').style.display = 'flex';
            document.getElementById('fecho-shift-id').innerText = 'Carregando turno...';
            document.getElementById('fecho-counted-cash').value = '';
            document.getElementById('fecho-notes').value = '';
            document.getElementById('btn-confirmar-fecho').disabled = true;

            fetch('/admin/shift/summary', {
                    headers: {
                        'Accept': 'application/json'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    if (!data.success) {
                        document.getElementById('fecho-shift-id').innerText = 'Nenhum caixa aberto';
                        nkamaAlert('Nenhum caixa aberto para fechar.', 'warning');
                        fecharModalFecho();
                        return;
                    }

                    resumoFechoAtual = data;
                    document.getElementById('fecho-shift-id').innerText = `Turno #${data.shift_id}`;
                    document.getElementById('fecho-opening').innerText = NkamaPOSPayment.format(data.opening_cash);
                    document.getElementById('fecho-cash').innerText = NkamaPOSPayment.format(data.cash_sales_total);
                    document.getElementById('fecho-card').innerText = NkamaPOSPayment.format(data.card_sales_total);
                    document.getElementById('fecho-transf').innerText = NkamaPOSPayment.format(data.transf_sales_total);
                    document.getElementById('fecho-total').innerText = NkamaPOSPayment.format(data.total_sales);
                    document.getElementById('fecho-expected').innerText = NkamaPOSPayment.format(data.expected);
                    document.getElementById('fecho-counted-cash').value = NkamaPOSPayment.roundUp(data.expected, 1);
                    document.getElementById('btn-confirmar-fecho').disabled = false;
                    calcularDiferencaFecho();
                })
                .catch(() => {
                    nkamaAlert('Erro ao carregar resumo do caixa.', 'error');
                    fecharModalFecho();
                });
        }

        function fecharModalFecho() {
            document.getElementById('modal-fecho-real').style.display = 'none';
        }

        function calcularDiferencaFecho() {
            const counted = NkamaPOSPayment.parse(document.getElementById('fecho-counted-cash').value);
            const expected = NkamaPOSPayment.parse(resumoFechoAtual.expected);
            const diff = counted - expected;
            const diffEl = document.getElementById('fecho-difference');

            diffEl.innerText = NkamaPOSPayment.format(diff);
            diffEl.style.color = diff > 0 ? '#34d399' : (diff < 0 ? '#ef4444' : '#fbbf24');
        }

        async function confirmarFechoCaixa() {
            const counted = NkamaPOSPayment.parse(document.getElementById('fecho-counted-cash').value);
            const btn = document.getElementById('btn-confirmar-fecho');

            const confirmado = await nkamaConfirm('Confirmar fecho de caixa? Depois disso o turno fica encerrado.', 'Fechar caixa');
            if (!confirmado) {
                return;
            }

            btn.disabled = true;
            btn.innerText = 'Fechando...';

            fetch('/admin/close-shift', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        counted_cash: counted,
                        notes: document.getElementById('fecho-notes').value
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (!data.success) {
                        nkamaAlert(data.message || 'Erro ao fechar caixa.', 'error');
                        return;
                    }

                    nkamaAlert(`Diferença: ${NkamaPOSPayment.format(data.difference)}`, 'success', 'Caixa fechado com sucesso')
                        .then(() => window.location.reload());
                })
                .catch(() => nkamaAlert('Erro de conexão ao fechar caixa.', 'error'))
                .finally(() => {
                    btn.disabled = false;
                    btn.innerText = 'Confirmar Fecho';
                });
        }

        function arredondarValorPagamento(valor, incremento) {
            return NkamaPOSPayment.roundUp(valor, incremento);
        }

        function preencherValorPago(valor) {
            obterCampoPagamentoAtivo().value = NkamaPOSPayment.roundUp(valor, 1);
            calcularPagamentoMisto();
        }

        function digitarValorPagamento(valor) {
            const input = obterCampoPagamentoAtivo();
            const atual = input.value || '';

            if (valor === '.' && atual.includes('.')) {
                return;
            }

            input.value = atual === '0' && valor !== '.' ? valor : atual + valor;
            calcularPagamentoMisto();
        }

        function apagarValorPagamento() {
            const input = obterCampoPagamentoAtivo();
            input.value = (input.value || '').slice(0, -1);
            calcularPagamentoMisto();
        }

        function limparValorPagamento() {
            obterCampoPagamentoAtivo().value = '';
            calcularPagamentoMisto();
        }

        function obterCampoPagamentoAtivo() {
            const campos = {
                main: 'input-valor-pago',
                cash: 'input-pago-cash',
                card: 'input-pago-card',
                transf: 'input-pago-transf',
                'customer-card': 'input-pago-customer-card'
            };

            return document.getElementById(campos[campoPagamentoAtivo] || campos.main);
        }

        function selecionarCampoPagamento(campo) {
            campoPagamentoAtivo = campo;
            document.querySelectorAll('.payment-split-input').forEach(input => {
                input.classList.toggle('active', input.id === `input-pago-${campo}`);
            });
        }

        function lerValorPagamento(id) {
            const input = document.getElementById(id);
            return NkamaPOSPayment.parse(input?.value);
        }

        function obterTotalPago() {
            const metodo = document.getElementById('select-metodo-pagamento').value;

            if (metodo === 'credit') {
                return 0;
            }

            if (metodo !== 'multi') {
                return lerValorPagamento('input-valor-pago');
            }

            return NkamaPOSPayment.sumBreakdown({
                cash: lerValorPagamento('input-pago-cash'),
                card: lerValorPagamento('input-pago-card'),
                transfer: lerValorPagamento('input-pago-transf'),
                customer_card: lerValorPagamento('input-pago-customer-card')
            });
        }

        function preencherPagamentoMistoInicial() {
            document.getElementById('input-pago-cash').value = 0;
            document.getElementById('input-pago-card').value = NkamaPOSPayment.roundUp(totalGeralVendaActual, 1);
            document.getElementById('input-pago-transf').value = 0;
            if (document.getElementById('input-pago-customer-card')) {
                document.getElementById('input-pago-customer-card').value = 0;
            }
            calcularPagamentoMisto();
        }

        function calcularPagamentoMisto() {
            const totalPago = obterTotalPago();
            const falta = NkamaPOSPayment.missing(totalGeralVendaActual, totalPago);
            const recebidoEl = document.getElementById('txt-total-recebido');
            const faltaEl = document.getElementById('txt-total-falta');

            if (recebidoEl) {
                recebidoEl.innerText = NkamaPOSPayment.format(totalPago);
            }

            if (faltaEl) {
                faltaEl.innerText = NkamaPOSPayment.format(falta);
                faltaEl.style.color = falta > 0 ? '#f59e0b' : '#34d399';
            }

            calcularTroco();
            atualizarClienteContaCorrente();
        }

        function hexToRgba(hex, alpha) {
            const normalized = hex.replace('#', '');
            const bigint = parseInt(normalized, 16);
            const r = (bigint >> 16) & 255;
            const g = (bigint >> 8) & 255;
            const b = bigint & 255;

            return `rgba(${r}, ${g}, ${b}, ${alpha})`;
        }

        function calcularTroco() {
            let valorDigitado = document.getElementById('input-valor-pago').value;

            // Substitui eventuais vírgulas de digitação por pontos antes do parse
            if (typeof valorDigitado === 'string') {
                valorDigitado = valorDigitado.replace(',', '.');
            }

            const metodo = document.getElementById('select-metodo-pagamento').value;
            if (metodo === 'credit') {
                document.getElementById('txt-troco-calculado').innerText = '0,00 Kz';
                atualizarClienteContaCorrente();
                return;
            }

            const entregue = metodo === 'multi' ? obterTotalPago() : NkamaPOSPayment.parse(valorDigitado);
            const dinheiro = metodo === 'multi' ? lerValorPagamento('input-pago-cash') : entregue;
            const troco = metodo === 'multi' ?
                NkamaPOSPayment.change(totalGeralVendaActual, entregue, dinheiro) :
                NkamaPOSPayment.change(totalGeralVendaActual, entregue);
            document.getElementById('txt-troco-calculado').innerText = troco > 0 ?
                NkamaPOSPayment.format(troco) :
                '0,00 Kz';
            atualizarClienteContaCorrente();
        }

        function consultarCartaoCliente() {
            const input = document.getElementById('input-cartao-cliente');
            const numero = (input?.value || '').trim();

            if (!numero) {
                nkamaAlert('Informe ou leia o cartao cliente.', 'warning');
                return;
            }

            const amount = Math.max(Number(totalGeralVendaActual || 0), valorFidelidadeAtual());
            fetch(`${customerCardLookupUrl}?card_number=${encodeURIComponent(numero)}&amount=${encodeURIComponent(amount)}`, {
                headers: { 'Accept': 'application/json' }
            })
                .then(res => res.json())
                .then(data => {
                    if (!data.success) {
                        limparCartaoCliente(false);
                        nkamaAlert(data.message || 'Cartao cliente nao encontrado.', 'warning');
                        return;
                    }

                    cartaoClienteSelecionado = data.card;
                    resetarAutorizacaoCartao();
                    renderizarCartaoCliente();

                    const selectConta = document.getElementById('select-cliente-conta');
                    if (selectConta) {
                        selectConta.value = String(data.card.customer_id);
                    }
                })
                .catch(() => nkamaAlert('Nao foi possivel consultar o cartao cliente.', 'error'));
        }

        function limparCartaoCliente(clearInput = true) {
            cartaoClienteSelecionado = null;
            resetarAutorizacaoCartao();
            const painel = document.getElementById('painel-cartao-cliente');
            const input = document.getElementById('input-cartao-cliente');

            if (clearInput && input) {
                input.value = '';
            }

            if (painel) {
                painel.style.display = 'none';
                const painelOtp = document.getElementById('painel-cartao-otp');
                if (painelOtp) painelOtp.style.display = 'none';
                painel.innerHTML = '';
            }
        }

        function renderizarCartaoCliente() {
            const painel = document.getElementById('painel-cartao-cliente');
            if (!painel || !cartaoClienteSelecionado) return;

            painel.style.display = 'block';
            const painelOtp = document.getElementById('painel-cartao-otp');
            if (painelOtp) painelOtp.style.display = 'block';
            const enoughText = cartaoClienteSelecionado.has_enough_for_sale === null
                ? 'A validar na finalizacao'
                : (cartaoClienteSelecionado.has_enough_for_sale ? 'Suficiente para esta venda' : 'Insuficiente para esta venda');

            painel.innerHTML = `
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:6px;">
                    <div><strong>Cliente</strong><br>${cartaoClienteSelecionado.customer_name || '-'}</div>
                    <div><strong>Cartao</strong><br>${cartaoClienteSelecionado.card_number}</div>
                    <div><strong>Nivel</strong><br>${cartaoClienteSelecionado.level || '-'}</div>
                    <div><strong>Validade</strong><br>${cartaoClienteSelecionado.expires_at || '-'}</div>
                    <div><strong>Disponivel nesta venda</strong><br>${NkamaPOSPayment.format(Number(cartaoClienteSelecionado.available_for_sale || 0))}</div>
                    <div><strong>Estado</strong><br>${enoughText}</div>
                </div>`;
        }
        function valorFidelidadeAtual() {
            const metodo = document.getElementById('select-metodo-pagamento').value;

            if (metodo === 'customer_card') {
                return Number(totalGeralVendaActual || 0);
            }

            if (metodo === 'multi') {
                return lerValorPagamento('input-pago-customer-card');
            }

            return 0;
        }

        function alternarEmergenciaCartao() {
            const painel = document.getElementById('painel-cartao-emergencia');
            const ativo = document.getElementById('check-cartao-emergencia')?.checked;
            if (painel) painel.style.display = ativo ? 'grid' : 'none';
        }

        function resetarAutorizacaoCartao() {
            ['input-cartao-cliente-otp', 'input-cartao-supervisor-pin', 'input-cartao-supervisor-motivo', 'input-cartao-autorizacao-motivo'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.value = '';
            });
            cartaoAutorizacaoAprovadaId = null;
            cartaoAutorizacaoPendenteId = null;
            if (cartaoAutorizacaoTimer) clearInterval(cartaoAutorizacaoTimer);
            cartaoAutorizacaoTimer = null;
            const authorizationInput = document.getElementById('input-cartao-autorizacao-id');
            if (authorizationInput) authorizationInput.value = '';
            const emergency = document.getElementById('check-cartao-emergencia');
            if (emergency) emergency.checked = false;
            alternarEmergenciaCartao();
            const status = document.getElementById('txt-cartao-otp-status');
            if (status) status.innerText = '';
        }

        function solicitarOtpCartaoCliente() {
            if (modulosAtivos.customer_card_otp === false) {
                nkamaAlert('OTP do Cartao Cliente desativado. Solicite autorizacao do gestor.', 'warning');
                return;
            }

            if (!cartaoClienteSelecionado) {
                nkamaAlert('Leia o cartao cliente antes de solicitar OTP.', 'warning');
                return;
            }

            const amount = valorFidelidadeAtual();
            if (amount <= 0) {
                nkamaAlert('Informe primeiro o valor de Fidelidade a usar.', 'warning');
                return;
            }

            const status = document.getElementById('txt-cartao-otp-status');
            if (status) status.innerText = 'A enviar OTP...';

            fetch(customerCardOtpUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    card_number: cartaoClienteSelecionado.card_number,
                    amount: amount
                })
            })
                .then(async response => {
                    const data = await response.json().catch(() => ({}));
                    if (!response.ok || !data.success) {
                        throw new Error(data.message || 'Nao foi possivel enviar o OTP.');
                    }
                    return data;
                })
                .then(data => {
                    if (status) status.innerText = `${data.message} Destino: ${data.sent_to || '-'}`;
                    nkamaAlert(data.message, 'success');
                })
                .catch(error => {
                    if (status) status.innerText = error.message;
                    nkamaAlert(error.message, 'warning');
                });
        }

        function urlAutorizacaoCartao(template, id) {
            return template.replace('__ID__', encodeURIComponent(id));
        }

        function atualizarEstadoAutorizacaoCartao(id, statusText) {
            cartaoAutorizacaoAprovadaId = id || null;
            const input = document.getElementById('input-cartao-autorizacao-id');
            if (input) input.value = id || '';
            const status = document.getElementById('txt-cartao-otp-status');
            if (status && statusText) status.innerText = statusText;
        }

        function solicitarAutorizacaoCartaoCliente() {
            if (!cartaoClienteSelecionado) {
                nkamaAlert('Leia o cartao cliente antes de solicitar autorizacao.', 'warning');
                return;
            }

            const amount = valorFidelidadeAtual();
            if (amount <= 0) {
                nkamaAlert('Informe primeiro o valor de Fidelidade a usar.', 'warning');
                return;
            }

            atualizarEstadoAutorizacaoCartao(null, 'A enviar solicitacao ao gestor...');

            fetch(customerCardAuthorizationRequestUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    card_number: cartaoClienteSelecionado.card_number,
                    amount: amount,
                    reason: document.getElementById('input-cartao-autorizacao-motivo')?.value || null,
                    context: {
                        payment_method: document.getElementById('select-metodo-pagamento')?.value || null,
                        table_id: mesaSelecionadaId,
                        total: totalGeralVendaActual
                    }
                })
            })
                .then(async response => {
                    const data = await response.json().catch(() => ({}));
                    if (!response.ok || !data.success) {
                        throw new Error(data.message || 'Nao foi possivel solicitar autorizacao.');
                    }
                    return data;
                })
                .then(data => {
                    cartaoAutorizacaoPendenteId = data.authorization.id;
                    atualizarEstadoAutorizacaoCartao(null, `Solicitacao #${data.authorization.id} enviada. Aguardando gestor...`);
                    if (cartaoAutorizacaoTimer) clearInterval(cartaoAutorizacaoTimer);
                    cartaoAutorizacaoTimer = setInterval(() => consultarAutorizacaoCartaoCliente(cartaoAutorizacaoPendenteId, true), 5000);
                    consultarAutorizacaoCartaoCliente(cartaoAutorizacaoPendenteId, true);
                    nkamaAlert(data.message, 'success');
                })
                .catch(error => {
                    atualizarEstadoAutorizacaoCartao(null, error.message);
                    nkamaAlert(error.message, 'warning');
                });
        }

        function consultarAutorizacaoCartaoCliente(id, silent = false) {
            if (!id) return;

            fetch(urlAutorizacaoCartao(customerCardAuthorizationStatusUrlTemplate, id), {
                headers: { 'Accept': 'application/json' }
            })
                .then(async response => {
                    const data = await response.json().catch(() => ({}));
                    if (!response.ok || !data.success) {
                        throw new Error(data.message || 'Nao foi possivel consultar a autorizacao.');
                    }
                    return data.authorization;
                })
                .then(authorization => {
                    if (authorization.status === 'approved') {
                        if (cartaoAutorizacaoTimer) clearInterval(cartaoAutorizacaoTimer);
                        cartaoAutorizacaoPendenteId = null;
                        atualizarEstadoAutorizacaoCartao(authorization.id, `Autorizacao #${authorization.id} aprovada por ${authorization.supervisor_name || 'gestor'}.`);
                        nkamaAlert('Autorizacao do gestor aprovada.', 'success');
                        return;
                    }

                    if (['rejected', 'expired', 'used'].includes(authorization.status)) {
                        if (cartaoAutorizacaoTimer) clearInterval(cartaoAutorizacaoTimer);
                        cartaoAutorizacaoPendenteId = null;
                        atualizarEstadoAutorizacaoCartao(null, `Solicitacao #${authorization.id}: ${authorization.status}.`);
                        if (!silent) nkamaAlert('Solicitacao nao aprovada.', 'warning');
                        return;
                    }

                    atualizarEstadoAutorizacaoCartao(null, `Solicitacao #${authorization.id} pendente. Aguardando gestor...`);
                })
                .catch(error => {
                    if (!silent) nkamaAlert(error.message, 'warning');
                });
        }

        function carregarSolicitacoesCartaoCliente() {
            const painel = document.getElementById('painel-cartao-autorizacoes');
            const lista = document.getElementById('lista-cartao-autorizacoes');
            if (!painel || !lista) return;

            fetch(customerCardAuthorizationPendingUrl, { headers: { 'Accept': 'application/json' } })
                .then(async response => {
                    const data = await response.json().catch(() => ({}));
                    if (!response.ok || !data.success) {
                        throw new Error(data.message || 'sem permissao');
                    }
                    return data.authorizations || [];
                })
                .then(authorizations => {
                    painel.style.display = 'block';
                    if (!authorizations.length) {
                        lista.innerHTML = '<div style="color:#94a3b8; font-size:11px;">Sem solicitacoes pendentes.</div>';
                        return;
                    }

                    lista.innerHTML = authorizations.map(item => `
                        <div style="border:1px solid rgba(187,247,208,.18); border-radius:8px; padding:8px; color:#dcfce7; font-size:12px;">
                            <div><strong>#${item.id}</strong> ${item.customer_name || '-'} | ${item.card_number || '-'}</div>
                            <div>Operador: ${item.operator_name || '-'} | Valor: ${NkamaPOSPayment.format(Number(item.amount || 0))}</div>
                            <div style="color:#bbf7d0;">Motivo: ${item.reason || '-'}</div>
                            <div style="display:flex; gap:6px; margin-top:7px;">
                                <button type="button" onclick="processarSolicitacaoCartao(${item.id}, 'approve')" style="background:#22c55e; color:#052e16; border:0; border-radius:7px; padding:6px 9px; font-weight:900;">Aprovar</button>
                                <button type="button" onclick="processarSolicitacaoCartao(${item.id}, 'reject')" style="background:#ef4444; color:#fff; border:0; border-radius:7px; padding:6px 9px; font-weight:900;">Rejeitar</button>
                            </div>
                        </div>`).join('');
                })
                .catch(() => {
                    painel.style.display = 'none';
                });
        }

        function processarSolicitacaoCartao(id, action) {
            const template = action === 'approve' ? customerCardAuthorizationApproveUrlTemplate : customerCardAuthorizationRejectUrlTemplate;

            fetch(urlAutorizacaoCartao(template, id), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ note: null })
            })
                .then(async response => {
                    const data = await response.json().catch(() => ({}));
                    if (!response.ok || !data.success) {
                        throw new Error(data.message || 'Nao foi possivel processar a solicitacao.');
                    }
                    return data;
                })
                .then(data => {
                    nkamaAlert(data.message, 'success');
                    carregarSolicitacoesCartaoCliente();
                })
                .catch(error => nkamaAlert(error.message, 'warning'));
        }

        setTimeout(carregarSolicitacoesCartaoCliente, 1200);
        setInterval(carregarSolicitacoesCartaoCliente, 15000);
        function atualizarClienteContaCorrente() {
            const metodo = document.getElementById('select-metodo-pagamento').value;
            const totalPago = obterTotalPago();
            const pendente = Math.max(Number(totalGeralVendaActual || 0) - totalPago, 0);
            const wrapper = document.getElementById('wrapper-cliente-conta');
            const pendenteEl = document.getElementById('txt-valor-pendente-conta');

            if (!wrapper) return;

            wrapper.style.display = (metodo === 'credit' || pendente > 0) ? 'block' : 'none';

            if (pendenteEl) {
                pendenteEl.innerText = `Pendente: ${NkamaPOSPayment.format(pendente)}`;
            }
        }

        function setPosProcessingButton(button, processing, label) {
            if (!button) return;
            const labelEl = button.querySelector('.button-label');
            if (labelEl && label) labelEl.textContent = label;
            button.classList.toggle('is-processing', processing);
            button.disabled = processing;
            button.setAttribute('aria-busy', processing ? 'true' : 'false');
        }

        function submeterVendaFinal() {
            if (posCheckoutProcessing) return;
            const botaoEmitir = document.getElementById('btn-emitir-fatura-pos');
            const metodo = document.getElementById('select-metodo-pagamento').value;
            const valorPago = obterTotalPago();
            const clienteId = document.getElementById('select-cliente-conta')?.value || '';
            const clienteCartaoId = cartaoClienteSelecionado ? String(cartaoClienteSelecionado.customer_id) : '';
            const pendente = Math.max(Number(totalGeralVendaActual || 0) - valorPago, 0);

            if (metodo === 'multi' && lerValorPagamento('input-pago-customer-card') > 0 && !cartaoClienteSelecionado) {
                nkamaAlert('Leia o cartao cliente para usar fidelidade no pagamento misto.', 'warning');
                return;
            }

            if (metodo === 'multi' && lerValorPagamento('input-pago-customer-card') > Number(cartaoClienteSelecionado?.available_for_sale || 0) + 0.0001) {
                nkamaAlert('Valor de fidelidade superior ao disponivel para esta venda.', 'warning');
                return;
            }
            if (metodo === 'customer_card' && !cartaoClienteSelecionado) {
                nkamaAlert('Leia o cartao cliente antes de pagar por fidelidade.', 'warning');
                return;
            }

            if (metodo === 'customer_card' && Number(cartaoClienteSelecionado?.available_for_sale || 0) + 0.0001 < Number(totalGeralVendaActual || 0)) {
                nkamaAlert('Valor disponivel para esta venda insuficiente.', 'warning');
                return;
            }

            const valorFidelidade = valorFidelidadeAtual();
            const emergenciaFidelidade = document.getElementById('check-cartao-emergencia')?.checked || false;
            if (valorFidelidade > 0 && !emergenciaFidelidade && !document.getElementById('input-cartao-cliente-otp')?.value.trim() && !cartaoAutorizacaoAprovadaId) {
                nkamaAlert('Informe o OTP enviado ao cliente ou solicite autorizacao do gestor para usar Fidelidade.', 'warning');
                return;
            }
            if (valorFidelidade > 0 && emergenciaFidelidade && !document.getElementById('input-cartao-supervisor-pin')?.value.trim()) {
                nkamaAlert('Informe o PIN do supervisor para emergencia offline.', 'warning');
                return;
            }
            if (pendente > 0 && !modulosAtivos.current_account) {
                nkamaAlert('Modulo Conta Corrente desativado pelo super-user.', 'warning');
                return;
            }

            if (pendente > 0 && !clienteId && !clienteCartaoId) {
                nkamaAlert('Selecione o cliente para lancar o valor em conta corrente.', 'warning');
                return;
            }

            posCheckoutProcessing = true;
            setPosProcessingButton(botaoEmitir, true, 'A processar...');

            const payload = {
                total: totalGeralVendaActual,
                amount_paid: valorPago,
                payment_breakdown: metodo === 'multi' ? NkamaPOSPayment.buildBreakdown(
                    lerValorPagamento('input-pago-cash'),
                    lerValorPagamento('input-pago-card'),
                    lerValorPagamento('input-pago-transf'),
                    lerValorPagamento('input-pago-customer-card')
                ) : null,
                items: estadosMesas[mesaSelecionadaId].itens,
                payment_method: metodo,
                customer_id: clienteId || clienteCartaoId || null,
                customer_card_number: cartaoClienteSelecionado?.card_number || null,
                customer_card_otp: document.getElementById('input-cartao-cliente-otp')?.value || null,
                customer_card_authorization_id: cartaoAutorizacaoAprovadaId || document.getElementById('input-cartao-autorizacao-id')?.value || null,
                customer_card_offline_emergency: document.getElementById('check-cartao-emergencia')?.checked || false,
                supervisor_pin: document.getElementById('input-cartao-supervisor-pin')?.value || null,
                supervisor_reason: document.getElementById('input-cartao-supervisor-motivo')?.value || null,
                table_id: modoAtual === 'salao' ? mesaSelecionadaId : null
            };

            fetch('/admin/pos/checkout', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(payload)
                })
                .then(res => res.json())
                .then(data => {
                    if (!data.success) {
                        posCheckoutProcessing = false;
                        setPosProcessingButton(botaoEmitir, false, 'Emitir Fatura');
                        nkamaAlert("Erro interno: " + data.message, 'error');
                        return;
                    }

                    abrirModalSucessoVenda(data, payload);
                    posCheckoutProcessing = false;
                    setPosProcessingButton(botaoEmitir, false, 'Emitir Fatura');
                    fecharModalPagamento();
                    resetarCamposPagamento();

                    if (modoAtual === 'salao' && mesaSelecionadaId && mesaSelecionadaId !== 9999) {
                        fetch(`/admin/restaurant/order/${mesaSelecionadaId}/close`, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-Token': '{{ csrf_token() }}'
                                }
                            })
                            .then(() => {
                                atualizarVisualMesaCard(mesaSelecionadaId, 'free');
                                estadosMesas[mesaSelecionadaId] = {
                                    itens: [],
                                    subtotal: 0,
                                    status: 'free',
                                    order_id: null
                                };
                                atualizarContadoresTop();
                                mesaSelecionadaId = null;
                                document.getElementById('lbl-mesa-ativa').innerText = 'Nenhuma Selecionada';
                                document.getElementById('view-salao-wrapper').style.display = 'flex';
                                document.getElementById('restaurant-categories').style.display = 'none';
                                document.getElementById('restaurant-products').style.display = 'none';
                                document.getElementById('txt-titulo-modulo').innerText = 'Salao Principal';
                                filtrarMesas(filtroMesasAtual);
                                renderizarCarrinho();
                            });
                    } else {
                        estadosMesas[9999].itens = [];
                        renderizarCarrinho();
                    }
                })
                .catch(err => {
                    posCheckoutProcessing = false;
                    setPosProcessingButton(botaoEmitir, false, 'Emitir Fatura');
                    console.error("Erro ao finalizar venda:", err);
                    nkamaAlert('Erro de conexao ao finalizar venda.', 'error');
                });
        }

        document.addEventListener('DOMContentLoaded', function() {
            verificarCaixaAbertoInicial();
            if (modulosAtivos.restaurant) {
                carregarEstadoInicialMesas();
            } else if (modulosAtivos.supermarket) {
                mesaSelecionadaId = 9999;
                document.getElementById('lbl-cliente-tipo').innerText = 'Cliente Geral - Venda Activa';
                document.getElementById('lbl-mesa-ativa').innerText = 'Caixa Aberto';
                document.getElementById('mesa-acoes').style.display = 'none';
                selecionarCategoriaSupermercado('all');
                renderizarCarrinho();
            }
        });

        function carregarEstadoInicialMesas() {
            fetch('/admin/restaurant/tables-state')
                .then(res => res.json())
                .then(data => {
                    Object.keys(data).forEach(mesaId => {
                        const infoMesa = data[mesaId];
                        estadosMesas[mesaId] = {
                            status: infoMesa.status,
                            order_id: infoMesa.order_id,
                            itens: infoMesa.itens || []
                        };
                        atualizarVisualMesaCard(mesaId, infoMesa.status);
                    });
                    atualizarContadoresTop();
                })
                .catch(err => console.error("Erro ao sincronizar o salão com a Base de Dados:", err));
        }
    </script>
@endsection
