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
                    <button id="tab-salao" onclick="mudarModoOperacao('salao')"
                        style="background: #38bdf8; color: #020617; font-weight: bold; padding: 6px 12px; border-radius: 6px; border: none; cursor: pointer;">
                        🏨 Gestão de Salão
                    </button>
                    <button id="tab-supermercado" onclick="mudarModoOperacao('supermercado')"
                        style="background: transparent; color: #94a3b8; padding: 6px 12px; border-radius: 6px; border: none; cursor: pointer;">
                        🛒 Supermercado / Retalho
                    </button>
                </div>
            </div>

            <div style="display: flex; align-items: center; gap: 20px; font-size: 13px;">
                <div id="metric-livres" style="color: #94a3b8;">Mesas Livres: <strong
                        style="color: #34d399;">{{ $tables->where('status', 'free')->count() }}</strong></div>
                <div id="metric-ocupadas" style="color: #94a3b8;">Mesas Ocupadas: <strong
                        style="color: #fbbf24;">{{ $tables->where('status', 'occupied')->count() }}</strong></div>
                <div style="color: #fff;">Vendas Hoje: <strong style="color: #34d399;">1.250.000,00 Kz</strong></div>

                <button onclick="abrirModalFecho()"
                    style="background: rgba(244, 63, 94, 0.15); color: #f43f5e; border: 1px solid rgba(244, 63, 94, 0.4); padding: 6px 12px; border-radius: 6px; font-weight: bold; cursor: pointer;">
                    🔒 Fecho de Caixa
                </button>
            </div>
        </div>

        <div style="display: flex; gap: 20px; align-items: flex-start;">
            <div style="flex: 1; display: flex; flex-direction: column; gap: 15px;">
                <div style="font-size: 18px; font-weight: bold; color: #fff;" id="txt-titulo-modulo">Salão Principal</div>

                <div id="view-salao-wrapper" style="display: flex; flex-direction: column; gap: 10px;">
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
                    style="display: grid; grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); gap: 10px; background: #020617; border: 1px solid #1e293b; padding: 15px; border-radius: 12px; max-height: 430px; overflow-y: auto; align-content: start;">
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
                            style="{{ $styleMesa }} padding: 15px; border-radius: 10px; cursor: pointer;"
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

                <div id="view-supermercado" class="hidden"
                    style="background: #020617; border: 1px solid #1e293b; padding: 20px; border-radius: 12px; flex-direction: column; gap: 15px;">
                    <div
                        style="background: #0f172a; border: 1px dashed #334155; padding: 15px; border-radius: 10px; display: flex; gap: 15px; align-items: center;">
                        <div style="font-family: monospace; color: #475569; font-size: 20px; letter-spacing: -2px;">█║▌│█│║▌
                        </div>
                        <input type="text" id="inputBarcode" onkeypress="verificarInputBarcode(event)"
                            style="flex: 1; background: #020617; border: 1px solid #1e293b; color: #fff; padding: 10px; border-radius: 8px; font-size: 14px;"
                            placeholder="Passe o leitor de código de barras ou digite o nome do produto...">
                    </div>
                </div>

                <div id="restaurant-products"
                    style="background: #020617; border: 1px solid #1e293b; padding: 15px; border-radius: 12px; display: none; gap: 10px; overflow-x: auto;">
                    @foreach ($restaurantCategories as $category)
                        @foreach ($category->products as $p)
                            <button class="restaurant-product category-{{ $category->id }}"
                                style="background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.2); color: #34d399; padding: 10px 15px; border-radius: 10px; cursor: pointer; min-width: 110px; display: none;"
                                onclick="adicionarItemNoPedido({{ $p->id }}, @js($p->name), {{ $p->selling_price }})">
                                <div style="font-size: 12px; font-weight: bold; color: #fff;">{{ $p->name }}</div>
                                <div style="font-size: 11px; margin-top: 2px;">
                                    {{ number_format($p->selling_price, 0, ',', '.') }} Kz</div>
                            </button>
                        @endforeach
                    @endforeach
                </div>

                <div id="supermarket-products"
                    style="background: #020617; border: 1px solid #1e293b; padding: 15px; border-radius: 12px; display: none; gap: 10px; overflow-x: auto;">
                    @foreach ($products as $p)
                        <button
                            style="background: rgba(56, 189, 248, 0.1); border: 1px solid rgba(56, 189, 248, 0.2); color: #38bdf8; padding: 10px 15px; border-radius: 10px; cursor: pointer; min-width: 110px;"
                            onclick="adicionarItemNoPedido({{ $p->id }}, @js($p->name), {{ $p->selling_price }})">
                            <div style="font-size: 12px; font-weight: bold; color: #fff;">{{ $p->name }}</div>
                            <div style="font-size: 11px; margin-top: 2px;">
                                {{ number_format($p->selling_price, 0, ',', '.') }} Kz</div>
                        </button>
                    @endforeach
                </div>
            </div>

            <div
                style="width: 360px; background: #0f172a; border: 1px solid #1e293b; border-radius: 12px; display: flex; flex-direction: column; overflow: hidden;">
                <div style="padding: 15px; border-bottom: 1px solid #1e293b; background: rgba(2, 6, 23, 0.4);">
                    <h3 id="lbl-mesa-ativa" style="margin: 0; color: #fff; font-size: 15px;">Nenhuma Selecionada</h3>
                    <div id="lbl-cliente-tipo" style="font-size: 11px; color: #94a3b8; margin-top: 3px;">Selecione uma mesa
                        no salão</div>
                </div>

                <div id="lista-itens-pedido"
                    style="flex: 1; min-height: 200px; padding: 15px; display: flex; flex-direction: column; gap: 8px;">
                    <div style="text-align: center; color: #64748b; font-size: 13px; margin-top: 40px;">O carrinho está
                        vazio.</div>
                </div>

                <div
                    style="padding: 15px; background: rgba(2, 6, 23, 0.2); border-top: 1px solid #1e293b; font-size: 13px;">
                    <div style="display: flex; justify-content: space-between; color: #94a3b8; margin-bottom: 5px;">
                        <span>Subtotal:</span>
                        <strong id="txt-subtotal" style="color: #fff;">0,00 Kz</strong>
                    </div>
                    <div
                        style="display: flex; justify-content: space-between; font-size: 15px; font-weight: bold; color: #fff; border-top: 1px solid #1e293b; padding-top: 10px;">
                        <span>Total (com IVA 14%):</span>
                        <span id="txt-total" style="color: #38bdf8;">0,00 Kz</span>
                    </div>
                </div>

                <div style="padding: 15px; background: #020617; display: flex; flex-direction: column; gap: 10px;">
                    <button id="btn-finalizar-venda" onclick="processarFechamentoVenda()"
                        style="width: 100%; background: #10b981; color: #020617; font-weight: bold; padding: 12px; border: none; border-radius: 8px; font-size: 14px; cursor: pointer;">
                        Processar Pagamento (F3)
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div id="modal-pagamento" class="hidden"
        style="position: fixed; inset: 0; background: rgba(2, 6, 23, 0.8); backdrop-filter: blur(4px); display: none; align-items: center; justify-content: center; padding: 20px; z-index: 50;">
        <div
            style="background: #0f172a; border: 1px solid #1e293b; border-radius: 12px; width: 100%; max-width: 400px; padding: 20px; margin: 10% auto;">
            <div
                style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #1e293b; padding-bottom: 10px; margin-bottom: 15px;">
                <h3 style="color: #fff; margin: 0; font-size: 16px;">Finalizar Venda • Forma de Pagamento</h3>
                <button onclick="fecharModalPagamento()"
                    style="background: transparent; border: none; color: #94a3b8; font-size: 20px; cursor: pointer;">&times;</button>
            </div>

            <div
                style="background: #020617; padding: 15px; border-radius: 10px; text-align: center; border: 1px solid #1e293b; margin-bottom: 15px;">
                <span style="font-size: 12px; color: #94a3b8; display: block; text-transform: uppercase;">Total a
                    Pagar</span>
                <span id="modal-txt-total" style="font-size: 22px; font-weight: bold; color: #38bdf8;">0,00 Kz</span>
            </div>

            <div style="display: flex; flex-direction: column; gap: 12px;">
                <div>
                    <label
                        style="display: block; font-size: 11px; color: #94a3b8; text-transform: uppercase; margin-bottom: 5px;">Forma
                        de Pagamento</label>
                    <select id="select-metodo-pagamento" onchange="ajustarCamposTroco()"
                        style="width: 100%; background: #020617; border: 1px solid #1e293b; color: #fff; padding: 10px; border-radius: 8px;">
                        <option value="cash">💵 Numerário (Dinheiro)</option>
                        <option value="card">💳 Multicaixa (TPA)</option>
                        <option value="transf">🏦 Transferência Bancária</option>
                        <option value="multi">🔀 Pagamento Misto</option>
                    </select>
                </div>

                <div id="wrapper-valores-recebidos" style="display: flex; gap: 10px;">
                    <div style="flex: 1;">
                        <label
                            style="display: block; font-size: 11px; color: #94a3b8; text-transform: uppercase; margin-bottom: 5px;">Valor
                            Recebido</label>
                        <input type="number" id="input-valor-pago" oninput="calcularTroco()"
                            style="width: 100%; background: #020617; border: 1px solid #1e293b; color: #fff; padding: 10px; border-radius: 8px;">
                    </div>
                    <div style="flex: 1;">
                        <label
                            style="display: block; font-size: 11px; color: #94a3b8; text-transform: uppercase; margin-bottom: 5px;">Troco</label>
                        <div id="txt-troco-calculado"
                            style="background: #020617; border: 1px solid #1e293b; color: #34d399; font-weight: bold; padding: 10px; border-radius: 8px; text-align: center;">
                            0,00 Kz</div>
                    </div>
                </div>
            </div>

            <div style="margin-top: 20px; display: flex; gap: 10px;">
                <button onclick="fecharModalPagamento()"
                    style="flex: 1; background: #020617; border: 1px solid #1e293b; color: #94a3b8; padding: 10px; border-radius: 8px; cursor: pointer;">Cancelar</button>
                <button onclick="submeterVendaFinal()"
                    style="flex: 1; background: #10b981; color: #020617; font-weight: bold; padding: 10px; border-radius: 8px; border: none; cursor: pointer;">Emitir
                    Fatura</button>
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
                <button onclick="alert('Caixa Fechado com Sucesso! Imprimindo Relatório Z...'); window.location.reload();"
                    style="flex: 1; background: #ef4444; color: #fff; font-weight: bold; padding: 8px; border-radius: 8px; border: none; cursor: pointer;">Confirmar
                    Fecho (Z)</button>
            </div>
        </div>
    </div>

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

        let mesaSelecionadaId = null;
        let modoAtual = 'salao';
        let filtroMesasAtual = 'all';
        let totalGeralVendaActual = 0;

        // Atalhos de Teclado
        window.addEventListener('keydown', function(event) {
            if (event.key === 'F3') {
                event.preventDefault();
                processarFechamentoVenda();
            }
        });

        function mudarModoOperacao(modo) {
            modoAtual = modo;
            const tabSalao = document.getElementById('tab-salao');
            const tabSupermercado = document.getElementById('tab-supermercado');

            if (!tabSalao || !tabSupermercado) return;

            tabSalao.style.background = "transparent";
            tabSalao.style.color = "#94a3b8";
            tabSupermercado.style.background = "transparent";
            tabSupermercado.style.color = "#94a3b8";

            if (modo === 'salao') {
                tabSalao.style.background = "#38bdf8";
                tabSalao.style.color = "#020617";
                document.getElementById('view-salao-wrapper').style.display = 'flex';
                document.getElementById('view-supermercado').style.display = 'none';
                document.getElementById('restaurant-categories').style.display = 'none';
                document.getElementById('restaurant-products').style.display = 'none';
                document.getElementById('supermarket-products').style.display = 'none';
                document.getElementById('txt-titulo-modulo').innerText = 'Salão Principal';
                document.getElementById('lbl-cliente-tipo').innerText = 'Selecione uma mesa no salão';
                mesaSelecionadaId = null;
                document.getElementById('lbl-mesa-ativa').innerText = 'Nenhuma Selecionada';
                document.querySelectorAll('[id^="card-mesa-"]').forEach(c => c.style.outline = 'none');
                filtrarMesas(filtroMesasAtual);
            } else {
                tabSupermercado.style.background = "#38bdf8";
                tabSupermercado.style.color = "#020617";
                document.getElementById('view-salao-wrapper').style.display = 'none';
                document.getElementById('view-supermercado').style.display = 'flex';
                document.getElementById('restaurant-categories').style.display = 'none';
                document.getElementById('restaurant-products').style.display = 'none';
                document.getElementById('supermarket-products').style.display = 'flex';
                document.getElementById('txt-titulo-modulo').innerText = 'Caixa Registadora • Supermercado';
                document.getElementById('lbl-cliente-tipo').innerText = 'Cliente Geral • Venda Activa';
                mesaSelecionadaId = 9999;
                document.getElementById('lbl-mesa-ativa').innerText = 'Caixa Aberto 🛒';

                setTimeout(() => {
                    const inputBc = document.getElementById('inputBarcode');
                    if (inputBc) {
                        inputBc.value = '';
                        inputBc.focus();
                    }
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

        function voltarParaMesas() {
            mesaSelecionadaId = null;
            document.getElementById('view-salao-wrapper').style.display = 'flex';
            document.getElementById('restaurant-categories').style.display = 'none';
            document.getElementById('restaurant-products').style.display = 'none';
            document.getElementById('txt-titulo-modulo').innerText = 'Salao Principal';
            document.getElementById('lbl-mesa-ativa').innerText = 'Nenhuma Selecionada';
            document.getElementById('lbl-cliente-tipo').innerText = 'Selecione uma mesa no salao';
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
            mesaSelecionadaId = id;
            document.querySelectorAll('[id^="card-mesa-"]').forEach(c => c.style.outline = 'none');

            const cardMesa = document.getElementById(`card-mesa-${id}`);
            if (cardMesa) {
                cardMesa.style.outline = '2px solid #38bdf8';
            }

            document.getElementById('lbl-mesa-ativa').innerText = nome;
            document.getElementById('lbl-cliente-tipo').innerText = 'Mesa Ativa • Conta Operacional';

            document.getElementById('txt-titulo-modulo').innerText = `Mesa ${nome} - Categorias`;
            document.getElementById('view-salao-mesas').style.display = 'none';
            document.getElementById('restaurant-categories').style.display = 'grid';
            document.getElementById('restaurant-products').style.display = 'none';
            document.querySelectorAll('.restaurant-product').forEach(productButton => {
                productButton.style.display = 'none';
            });

            abrirOuCarregarMesaNaBD(id);
        }

        function abrirOuCarregarMesaNaBD(tableId) {
            fetch(`/admin/restaurant/order/${tableId}/open`, {
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
                                qty: parseInt(item.quantity || 1)
                            }));
                        } else {
                            estadosMesas[tableId].itens = [];
                        }

                        atualizarVisualMesaCard(tableId, data.table_status);
                        atualizarContadoresTop();
                        renderizarCarrinho();
                    } else {
                        alert("Erro ao carregar mesa: " + data.message);
                    }
                })
                .catch(err => console.error("Erro ao carregar dados do servidor:", err));
        }

        function atualizarVisualMesaCard(tableId, status) {
            const cardMesa = document.getElementById(`card-mesa-${tableId}`);
            const txtStatus = document.getElementById(`status-text-${tableId}`);

            if (cardMesa && txtStatus) {
                if (status === 'occupied' || status === 'busy') {
                    cardMesa.style.background = "rgba(251, 191, 36, 0.1)";
                    cardMesa.style.border = "1px solid rgba(251, 191, 36, 0.3)";
                    cardMesa.style.color = "#fbbf24";
                    txtStatus.innerText = 'Ocupada';
                } else {
                    cardMesa.style.background = "rgba(16, 185, 129, 0.1)";
                    cardMesa.style.border = "1px solid rgba(16, 185, 129, 0.3)";
                    cardMesa.style.color = "#10b981";
                    txtStatus.innerText = 'Livre';
                }
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

        function adicionarItemNoPedido(idProduto, nomeProduto, preco) {
            if (!mesaSelecionadaId && modoAtual === 'salao') {
                alert("Por favor, selecione uma mesa no mapa primeiro.");
                return;
            }

            const mesaCorrente = estadosMesas[mesaSelecionadaId];

            if (modoAtual === 'salao') {
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
                                    qty: 1
                                });
                            }
                            renderizarCarrinho();
                        } else {
                            alert("Erro ao adicionar item no servidor: " + (data.message || "Motivo desconhecido"));
                        }
                    })
                    .catch(err => console.error("Erro ao processar item no servidor:", err));
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
                            adicionarItemNoPedido(product.data.id, product.data.name, product.data.selling_price);
                            event.target.value = '';
                        } else {
                            alert("Produto não localizado.");
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
            totalGeralVendaActual = subtotal * 1.14; // IVA 14%
            document.getElementById('txt-subtotal').innerText = subtotal.toLocaleString('pt-PT') + ' Kz';
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
                            alert("Erro ao sincronizar remoção: " + data.message);
                            // Opcional: recarregar estado da mesa se ocorrer erro para evitar dessincronização
                            abrirOuCarregarMesaNaBD(mesaSelecionadaId);
                        }
                    })
                    .catch(err => {
                        console.error("Erro de rede ao remover item:", err);
                        alert("Erro de conexão. Verifique se a mesa ainda existe.");
                    });
            }
        }

        function processarFechamentoVenda() {
            if (!mesaSelecionadaId || !estadosMesas[mesaSelecionadaId] || estadosMesas[mesaSelecionadaId].itens.length ===
                0) {
                alert("O carrinho está vazio!");
                return;
            }
            document.getElementById('modal-txt-total').innerText = totalGeralVendaActual.toLocaleString('pt-PT') + ' Kz';

            // Conversão limpa para o input numérico
            document.getElementById('input-valor-pago').value = Math.ceil(totalGeralVendaActual);
            document.getElementById('modal-pagamento').style.display = 'flex';
            calcularTroco();
        }

        function fecharModalPagamento() {
            document.getElementById('modal-pagamento').style.display = 'none';
        }

        function ajustarCamposTroco() {
            const metodo = document.getElementById('select-metodo-pagamento').value;
            const wrapper = document.getElementById('wrapper-valores-recebidos');
            if (metodo !== 'cash') {
                wrapper.style.opacity = '0.3';
                wrapper.style.pointerEvents = 'none';
                document.getElementById('txt-troco-calculado').innerText = '0,00 Kz';
            } else {
                wrapper.style.opacity = '1';
                wrapper.style.pointerEvents = 'auto';
                calcularTroco();
            }
        }

        function calcularTroco() {
            let valorDigitado = document.getElementById('input-valor-pago').value;

            // Substitui eventuais vírgulas de digitação por pontos antes do parse
            if (typeof valorDigitado === 'string') {
                valorDigitado = valorDigitado.replace(',', '.');
            }

            const entregue = parseFloat(valorDigitado) || 0;
            const troco = entregue - totalGeralVendaActual;
            document.getElementById('txt-troco-calculado').innerText = (troco > 0 ? troco.toLocaleString('pt-PT') :
                '0,00') + ' Kz';
        }

        function submeterVendaFinal() {
            const metodo = document.getElementById('select-metodo-pagamento').value;
            const payload = {
                total: totalGeralVendaActual,
                items: estadosMesas[mesaSelecionadaId].itens,
                method: metodo,
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
                        alert("Erro interno: " + data.message);
                        return;
                    }

                    alert(`Fatura emitida com sucesso!`);
                    fecharModalPagamento();

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
                                document.getElementById('view-salao-mesas').style.display = 'grid';
                                document.getElementById('restaurant-categories').style.display = 'none';
                                document.getElementById('restaurant-products').style.display = 'none';
                                document.getElementById('txt-titulo-modulo').innerText = 'Salao Principal';
                                renderizarCarrinho();
                            });
                    } else {
                        estadosMesas[9999].itens = [];
                        renderizarCarrinho();
                    }
                })
                .catch(err => console.error("Erro ao finalizar venda:", err));
        }

        document.addEventListener('DOMContentLoaded', function() {
            carregarEstadoInicialMesas();
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
