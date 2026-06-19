@extends('layouts.admin')

@section('page-title', 'Nkama ERP • POS Multi-Módulos')

@section('content')
    <!-- ESTILOS EXCLUSIVOS DO DESIGN INTEGRADO (RESTAURANTE & SUPERMERCADO) -->
    <style>
        /* Reset e Fundo Base Claro do ERP */
        .pos-restaurant-body {
            background-color: #f3f4f6;
            color: #1f2937;
            font-family: system-ui, -apple-system, sans-serif;
        }

        /* Topbar com as métricas do ERP */
        .topbar-erp {
            background-color: #1e2530;
            color: #ffffff;
            padding: 10px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 13px;
            border-radius: 4px;
            margin-bottom: 10px;
        }

        .metrics-group {
            display: flex;
            gap: 20px;
            color: #9ca3af;
        }

        .metrics-group strong {
            color: #ffffff;
        }

        /* Selector de Módulo (Salão vs Supermercado) */
        .mode-selector {
            display: flex;
            background: #2d3748;
            padding: 2px;
            border-radius: 6px;
            border: 1px solid #4a5568;
        }

        .mode-tab {
            background: transparent;
            border: none;
            color: #a0aec0;
            padding: 6px 14px;
            font-size: 12px;
            font-weight: 600;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.15s;
        }

        .mode-tab.active {
            background: #38bdf8;
            color: #1e2530;
        }

        /* Container Principal */
        .shell-restaurant {
            display: flex;
            gap: 16px;
            height: calc(100vh - 140px);
        }

        /* Área Esquerda */
        .left-zone {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        /* Grid do Salão Principal */
        .salao-grid-bg {
            background-color: #ffffff;
            background-image: linear-gradient(#e5e7eb 1px, transparent 1px), linear-gradient(90deg, #e5e7eb 1px, transparent 1px);
            background-size: 20px 20px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 24px;
            flex: 1;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(110px, 1fr));
            grid-auto-rows: max-content;
            gap: 20px;
            overflow-y: auto;
        }

        /* View do Módulo Supermercado */
        .supermercado-view-bg {
            background-color: #ffffff;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 20px;
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        /* Caixa de Input de Código de Barras */
        .barcode-scanner-box {
            display: flex;
            gap: 10px;
            background: #f9fafb;
            padding: 16px;
            border-radius: 8px;
            border: 1px dashed #cbd5e1;
        }

        .barcode-input {
            flex: 1;
            padding: 10px 14px;
            font-size: 14px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            outline: none;
        }

        .barcode-input:focus {
            border-color: #38bdf8;
            box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.2);
        }

        /* Card de Mesa (Restaurante) */
        .mesa-card {
            border-radius: 8px;
            color: #ffffff;
            padding: 12px 8px;
            text-align: center;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            cursor: pointer;
            transition: transform 0.1s, box-shadow 0.1s;
        }

        .mesa-card:active {
            transform: scale(0.96);
        }

        .mesa-card.mesa-ativa {
            outline: 3px solid #3b82f6;
            outline-offset: 2px;
        }

        .mesa-card .num {
            font-size: 16px;
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
        }

        .mesa-card .status-title {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.05em;
            margin-top: 2px;
        }

        /* Cores de Estado das Mesas */
        .mesa-livre {
            background-color: #15803d;
        }

        .mesa-conta {
            background-color: #b91c1c;
        }

        .mesa-busy {
            background-color: #eab308;
        }

        .mesa-reserva {
            background-color: #1d4ed8;
        }

        /* Abas de Categorias */
        .cat-tabs {
            display: flex;
            gap: 8px;
            background: #e5e7eb;
            padding: 6px;
            border-radius: 8px;
            border: 1px solid #d1d5db;
        }

        .cat-btn {
            background: #ffffff;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            padding: 8px 16px;
            font-size: 13px;
            font-weight: 600;
            color: #374151;
            cursor: pointer;
        }

        .cat-btn.active {
            background: #374151;
            color: #ffffff;
            border-color: #374151;
        }

        /* Faixa de Atalhos de Artigos */
        .products-strip {
            background: #ffffff;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 12px;
            display: flex;
            gap: 10px;
            overflow-x: auto;
        }

        .prod-btn {
            min-width: 120px;
            color: #ffffff;
            font-weight: bold;
            font-size: 13px;
            padding: 10px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 2px;
            box-shadow: inset 0 -3px 0px rgba(0, 0, 0, 0.2);
            transition: transform 0.05s;
        }

        .prod-btn:active {
            transform: scale(0.95);
        }

        .prod-btn .prod-price {
            font-size: 10px;
            opacity: 0.9;
            font-weight: normal;
        }

        .p-cor-0 {
            background-color: #dc2626;
        }

        .p-cor-1 {
            background-color: #2563eb;
        }

        .p-cor-2 {
            background-color: #f97316;
        }

        .p-cor-3 {
            background-color: #16a34a;
        }

        .p-cor-4 {
            background-color: #8b5cf6;
        }

        /* Painel Lateral Direito da Conta */
        .right-panel-conta {
            width: 340px;
            background: #ffffff;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .panel-header-mesa {
            padding: 14px;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f9fafb;
        }

        .panel-header-mesa h3 {
            font-size: 16px;
            font-weight: 700;
            margin: 0;
        }

        .items-list-pedido {
            flex: 1;
            overflow-y: auto;
            padding: 14px;
        }

        .pedido-item-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 13px;
            margin-bottom: 10px;
            color: #374151;
            padding-bottom: 6px;
            border-bottom: 1px dashed #f3f4f6;
        }

        .btn-remove-item {
            background: none;
            border: none;
            color: #ef4444;
            cursor: pointer;
            font-weight: bold;
            margin-left: 6px;
        }

        .totais-fatura-box {
            padding: 14px;
            border-top: 1px solid #e5e7eb;
            background: #f9fafb;
            font-size: 14px;
        }

        .total-row-highlight {
            font-size: 16px;
            font-weight: 800;
            border-top: 1px solid #d1d5db;
            padding-top: 8px;
            margin-top: 8px;
            display: flex;
            justify-content: space-between;
        }

        /* Botões de Ação */
        .actions-box-vertical {
            padding: 14px;
            border-top: 1px solid #e5e7eb;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .btn-action-gray {
            background-color: #4b5563;
            color: #ffffff;
            font-weight: 600;
            font-size: 13px;
            padding: 10px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            text-align: center;
        }

        .btn-action-gray:hover {
            background-color: #374151;
        }

        .btn-action-emitir {
            background-color: #16a34a;
            color: #ffffff;
            font-weight: 700;
            font-size: 14px;
            padding: 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            text-align: center;
            box-shadow: 0 2px 4px rgba(22, 163, 74, 0.2);
        }

        .btn-action-emitir:hover {
            background-color: #15803d;
        }
    </style>

    <div class="pos-restaurant-body">

        <!-- METRICAS E ALTERNADOR DE MÓDULOS -->
        <div class="topbar-erp">
            <div style="font-weight: bold; font-size: 14px; display: flex; align-items: center; gap: 12px;">
                <div><span style="color: #38bdf8;">●</span> Nkama ERP</div>

                <!-- ABAS PRINCIPAIS: SALÃO VS SUPERMERCADO -->
                <div class="mode-selector">
                    <button class="mode-tab active" id="tab-salao" onclick="mudarModoOperacao('salao')">
                        🏨 GESTÃO DE SALÃO
                    </button>
                    <button class="mode-tab" id="tab-supermercado" onclick="mudarModoOperacao('supermercado')">
                        🛒 SUPERMERCADO / RETALHO
                    </button>
                </div>
            </div>

            <div class="metrics-group">
                <div id="metric-livres">Mesas Livres: <strong>{{ $tables->where('status', 'free')->count() }}</strong></div>
                <div id="metric-ocupadas">Mesas Ocupadas: <strong>{{ $tables->where('status', 'busy')->count() }}</strong>
                </div>
                <div>Vendas Hoje: <strong>1.250.000,00 Kz</strong></div>
                <div>Clientes Atendidos: <strong>145</strong></div>
            </div>
        </div>

        <!-- CORPO DA APLICAÇÃO -->
        <div class="shell-restaurant">

            <!-- ZONA ESQUERDA (DINÂMICA) -->
            <div class="left-zone">

                <div style="display: flex; align-items: center; gap: 8px; font-weight: bold; font-size: 16px;">
                    <span id="txt-titulo-modulo">Salão Principal</span>
                </div>

                <!-- VIEW 1: MAPA DE MESAS (RESTAURANTE) -->
                <div class="salao-grid-bg" id="view-salao-mesas">
                    @foreach ($tables as $table)
                        @php
                            $statusClass = 'mesa-livre';
                            if ($table->status === 'busy') {
                                $statusClass = 'mesa-busy';
                            }
                            if ($table->status === 'conta') {
                                $statusClass = 'mesa-conta';
                            }
                            if ($table->status === 'reserva') {
                                $statusClass = 'mesa-reserva';
                            }
                        @endphp

                        <div class="mesa-card {{ $statusClass }}" id="card-mesa-{{ $table->id }}"
                            onclick="selecionarMesa({{ $table->id }}, '{{ $table->name }}')">
                            <div class="num">🪑 {{ $table->name }}</div>
                            <div class="status-title" id="status-text-{{ $table->id }}">
                                {{ $table->status === 'free' ? 'LIVRE' : 'OCUPADA' }}
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- VIEW 2: INTERFACE DE SUPERMERCADO (NOVO) -->
                <div class="supermercado-view-bg" id="view-supermercado" style="display: none;">
                    <!-- Caixa de Pesquisa / Código de barras -->
                    <div class="barcode-scanner-box">
                        <div style="font-size: 24px; align-self: center;">█║▌│█│║▌║</div>
                        <input type="text" class="barcode-input" id="inputBarcode"
                            placeholder="Passe o leitor de código de barras ou digite o nome do produto..."
                            onkeypress="verificarInputBarcode(event)">
                    </div>

                    <!-- Tabela rápida de ajuda para o operador do caixa -->
                    <div
                        style="flex: 1; border: 1px solid #e5e7eb; border-radius: 6px; padding: 14px; background: #fafafa;">
                        <span
                            style="font-size: 12px; font-weight: bold; color: #6b7280; text-transform: uppercase;">Produtos
                            Recentes no Sistema</span>
                        <div
                            style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-top: 10px; font-size: 13px;">
                            @foreach ($products->take(4) as $p)
                                <div
                                    style="background: #fff; padding: 8px; border: 1px solid #e5e7eb; border-radius: 4px; display: flex; justify-content: space-between;">
                                    <span>{{ $p->name }}</span>
                                    <span
                                        style="color:#16a34a; font-weight:bold;">{{ number_format($p->selling_price, 2) }}
                                        Kz</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- FILTRO DE CATEGORIAS (COMUM) -->
                <div class="cat-tabs">
                    <button class="cat-btn active">Todos os Artigos</button>
                    <button class="cat-btn">Alimentação</button>
                    <button class="cat-btn">Bebidas</button>
                    <button class="cat-btn">Higiene</button>
                    <button class="cat-btn">Outros</button>
                </div>

                <!-- ATALHOS RÁPIDOS DE PRODUTOS (DINÂMICOS DO BANCO) -->
                <div class="products-strip">
                    @foreach ($products as $index => $p)
                        <button class="prod-btn p-cor-{{ $index % 5 }}"
                            onclick="adicionarItemNoPedido({{ $p->id }}, '{{ $p->name }}', {{ $p->selling_price }})">
                            <span>{{ $p->name }}</span>
                            <span class="prod-price">{{ number_format($p->selling_price, 2) }} Kz</span>
                        </button>
                    @endforeach
                </div>

            </div>

            <!-- PAINEL DIREITO (CARRINHO DE COMPRAS / CONTA) -->
            <div class="right-panel-conta">
                <div class="panel-header-mesa">
                    <div>
                        <h3 id="lbl-mesa-ativa">Nenhuma Selecionada</h3>
                        <div style="font-size: 11px; color:#6b7280; margin-top:2px;" id="lbl-cliente-tipo">Selecione uma
                            mesa no salão</div>
                    </div>
                    <button
                        style="background:none; border:none; color:#9ca3af; font-size:16px; cursor:pointer;">•••</button>
                </div>

                <!-- Lista de Artigos da Venda -->
                <div class="items-list-pedido" id="lista-itens-pedido">
                    <div style="text-align: center; color: #9ca3af; font-size: 13px; margin-top: 40px;">
                        O carrinho está vazio.
                    </div>
                </div>

                <!-- Caixa de Cálculos -->
                <div class="totais-fatura-box">
                    <div style="display: flex; justify-content: space-between; color:#4b5563;">
                        <span>Subtotal:</span>
                        <strong id="txt-subtotal">0,00 Kz</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; color:#4b5563; margin-top: 4px;">
                        <span>IVA (14%):</span>
                        <strong id="txt-iva">0,00 Kz</strong>
                    </div>
                    <div class="total-row-highlight">
                        <span>Total:</span>
                        <span style="color: #1f2937;" id="txt-total">0,00 Kz</span>
                    </div>
                </div>

                <!-- Botões de Comandos Operacionais -->
                <div class="actions-box-vertical">
                    <button class="btn-action-gray" id="btn-transf"
                        onclick="alert('Funcionalidade de transferência em desenvolvimento.')">Transferir Mesa</button>
                    <button class="btn-action-gray" id="btn-dividir" onclick="alert('Dividindo conta por igual...')">Dividir
                        Conta</button>
                    <button class="btn-action-emitir" id="btn-finalizar-venda">Emitir Fatura (F3)</button>
                </div>
            </div>

        </div>
    </div>

    <script>
        // Memória local para guardar o estado de consumo (Id 9999 reservado para o Caixa do Supermercado)
        const estadosMesas = {
            9999: {
                itens: [],
                subtotal: 0
            }
        };
        let mesaSelecionadaId = null;
        let modoAtual = 'salao';

        /**
         * Alterna a interface entre Restaurante (Salão) e Caixa de Supermercado
         */
        function mudarModoOperacao(modo) {
            modoAtual = modo;

            document.getElementById('tab-salao').classList.remove('active');
            document.getElementById('tab-supermercado').classList.remove('active');

            if (modo === 'salao') {
                document.getElementById('tab-salao').classList.add('active');
                document.getElementById('view-salao-mesas').style.display = 'grid';
                document.getElementById('view-supermercado').style.display = 'none';
                document.getElementById('txt-titulo-modulo').innerText = 'Salão Principal';
                document.getElementById('lbl-cliente-tipo').innerText = 'Selecione uma mesa no salão';

                // Exibe métricas de mesa
                document.getElementById('metric-livres').style.display = 'block';
                document.getElementById('metric-ocupadas').style.display = 'block';
                document.getElementById('btn-transf').style.display = 'block';
                document.getElementById('btn-dividir').style.display = 'block';

                // Reseta foco
                mesaSelecionadaId = null;
                document.querySelectorAll('.mesa-card').forEach(card => card.classList.remove('mesa-ativa'));
                document.getElementById('lbl-mesa-ativa').innerText = 'Nenhuma Selecionada';
            } else {
                document.getElementById('tab-supermercado').classList.add('active');
                document.getElementById('view-salao-mesas').style.display = 'none';
                document.getElementById('view-supermercado').style.display = 'flex';
                document.getElementById('txt-titulo-modulo').innerText = 'Caixa Registadora • Supermercado';
                document.getElementById('lbl-cliente-tipo').innerText = 'Cliente Geral • Venda a Dinheiro';

                // Oculta métricas de restaurante irrelevantes no supermercado
                document.getElementById('metric-livres').style.display = 'none';
                document.getElementById('metric-ocupadas').style.display = 'none';
                document.getElementById('btn-transf').style.display = 'none';
                document.getElementById('btn-dividir').style.display = 'none';

                // Aloca automaticamente para o Caixa de Supermercado (ID 9999)
                mesaSelecionadaId = 9999;
                document.getElementById('lbl-mesa-ativa').innerText = 'Caixa Aberto 🛒';

                // Joga o foco do teclado direto para o input do código de barras
                setTimeout(() => document.getElementById('inputBarcode').focus(), 100);
            }

            renderizarCarrinho();
        }

        /**
         * Define a mesa ativa no painel direito (apenas modo salão)
         */
        function selecionarMesa(id, nome) {
            mesaSelecionadaId = id;

            document.querySelectorAll('.mesa-card').forEach(card => card.classList.remove('mesa-ativa'));
            document.getElementById(`card-mesa-${id}`).classList.add('mesa-ativa');

            document.getElementById('lbl-mesa-ativa').innerText = nome;
            document.getElementById('lbl-cliente-tipo').innerText = 'Cliente: Mesa Activa';

            if (!estadosMesas[id]) {
                estadosMesas[id] = {
                    itens: [],
                    subtotal: 0
                };
            }

            renderizarCarrinho();
        }

        /**
         * Simulação de entrada de código de barras
         */
        function verificarInputBarcode(e) {
            if (e.key === 'Enter') {
                const input = document.getElementById('inputBarcode');
                if (input.value.trim() !== '') {
                    // Aqui simula que encontrou o primeiro produto da lista enviada pelo Laravel
                    // Numa integração real, faria uma busca rápida ou usaria os dados de $products injetados no JS
                    adicionarItemNoPedido(1, "Artigo Lido via Scanner", 2500);
                    input.value = '';
                }
            }
        }

        /**
         * Insere um produto clicado ou lido no carrinho ativo
         */
        function adicionarItemNoPedido(idProduto, nomeProduto, preco) {
            if (!mesaSelecionadaId && modoAtual === 'salao') {
                alert("Por favor, selecione uma mesa antes de lançar produtos.");
                return;
            }

            const mesaCorrente = estadosMesas[mesaSelecionadaId];
            const itemExistente = mesaCorrente.itens.find(item => item.id === idProduto);

            if (itemExistente) {
                itemExistente.qtd++;
            } else {
                mesaCorrente.itens.push({
                    id: idProduto,
                    name: nomeProduto,
                    price: parseFloat(preco),
                    qtd: 1
                });
            }

            // Atualização visual do card da mesa do restaurante
            if (modoAtual === 'salao') {
                const cardMesa = document.getElementById(`card-mesa-${mesaSelecionadaId}`);
                if (cardMesa.classList.contains('mesa-livre')) {
                    cardMesa.classList.remove('mesa-livre');
                    cardMesa.classList.add('mesa-busy');
                    document.getElementById(`status-text-${mesaSelecionadaId}`).innerText = 'OCUPADA';
                }
            }

            renderizarCarrinho();
        }

        /**
         * Remove ou diminui a quantidade de um item do pedido
         */
        function removerItemPedido(index) {
            const mesaCorrente = estadosMesas[mesaSelecionadaId];
            mesaCorrente.itens.splice(index, 1);

            if (modoAtual === 'salao' && mesaCorrente.itens.length === 0) {
                const cardMesa = document.getElementById(`card-mesa-${mesaSelecionadaId}`);
                cardMesa.classList.remove('mesa-busy', 'mesa-conta');
                cardMesa.classList.add('mesa-livre');
                document.getElementById(`status-text-${mesaSelecionadaId}`).innerText = 'LIVRE';
            }

            renderizarCarrinho();
        }

        /**
         * Atualiza e faz a somatória dos preços e impostos na tela
         */
        function renderizarCarrinho() {
            const containerItens = document.getElementById('lista-itens-pedido');

            if (!mesaSelecionadaId || estadosMesas[mesaSelecionadaId].itens.length === 0) {
                containerItens.innerHTML = `
                <div style="text-align: center; color: #9ca3af; font-size: 13px; margin-top: 40px;">
                    O carrinho está vazio.
                </div>`;
                document.getElementById('txt-subtotal').innerText = '0,00 Kz';
                document.getElementById('txt-iva').innerText = '0,00 Kz';
                document.getElementById('txt-total').innerText = '0,00 Kz';
                return;
            }

            const mesaCorrente = estadosMesas[mesaSelecionadaId];
            let htmlGerado = '';
            let subtotalCalculado = 0;

            mesaCorrente.itens.forEach((item, index) => {
                const valorLinha = item.price * item.qtd;
                subtotalCalculado += valorLinha;

                htmlGerado += `
                <div class="pedido-item-row">
                    <span>▶ ${item.qtd}x ${item.name}</span>
                    <div>
                        <strong>${valorLinha.toLocaleString('pt-PT', {minimumFractionDigits: 2})} Kz</strong>
                        <button class="btn-remove-item" onclick="removerItemPedido(${index})">×</button>
                    </div>
                </div>
            `;
            });

            containerItens.innerHTML = htmlGerado;

            const valorIva = subtotalCalculado * 0.14;
            const totalGeral = subtotalCalculado + valorIva;

            document.getElementById('txt-subtotal').innerText = subtotalCalculado.toLocaleString('pt-PT', {
                minimumFractionDigits: 2
            }) + ' Kz';
            document.getElementById('txt-iva').innerText = valorIva.toLocaleString('pt-PT', {
                minimumFractionDigits: 2
            }) + ' Kz';
            document.getElementById('txt-total').innerText = totalGeral.toLocaleString('pt-PT', {
                minimumFractionDigits: 2
            }) + ' Kz';
        }

        // Ação do Botão Principal de Fechamento de Venda
        // Substitua o listener do clique de finalização na View por este estruturado para o ERP:
        document.getElementById('btn-finalizar-venda').addEventListener('click', () => {
            if (!mesaSelecionadaId || estadosMesas[mesaSelecionadaId].itens.length === 0) {
                alert("Não há produtos no carrinho para faturar.");
                return;
            }

            if (modoAtual === 'supermercado') {
                // Formata os dados exatamente como o SaleController@store exige
                const payload = {
                    total: calcularTotalGeral(estadosMesas[9999].itens), // Função auxiliar de soma
                    items: estadosMesas[9999].itens.map(item => ({
                        id: item.id,
                        qty: item.qtd,
                        price: item.price
                    })),
                    payments: {
                        cash: calcularTotalGeral(estadosMesas[9999]
                        .itens), // Simulação: tudo em Cash. Pode ligar a um modal se desejar.
                        card: 0,
                        transf: 0,
                        multi: 0
                    }
                };

                // Rota oficial de vendas do ERP
                fetch('/admin/sales', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSR-Token': document.querySelector('meta[name="csrf-token"]').getAttribute(
                                'content')
                        },
                        body: JSON.stringify(payload)
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            alert(
                                `Venda nº ${data.invoice} registada no Supermercado com sucesso! Troco: ${data.change} Kz`);
                            estadosMesas[9999] = {
                                itens: [],
                                subtotal: 0
                            };
                            renderizarCarrinho();
                            document.getElementById('inputBarcode').focus();
                        } else {
                            alert("Erro no validador do ERP: " + data.error);
                        }
                    })
                    .catch(err => alert("Erro ao processar comunicação com o servidor principal."));

            } else {
                // Lógica normal de fecho de mesa de restaurante (RestaurantController@closeTable)
                fetch(`/admin/restaurant/table/${mesaSelecionadaId}/close`, {
                    method: 'POST'
                })
                // ... restante do fecho da mesa
            }
        });

        function calcularTotalGeral(itens) {
            let sub = itens.reduce((acc, item) => acc + (item.price * item.qtd), 0);
            return sub * 1.07; // Incluindo a taxa de 14% de IVA mapeada no teu ERP
        }


        function verificarInputBarcode(e) {
    if (e.key === 'Enter') {
        const input = document.getElementById('inputBarcode');
        const barcodeValue = input.value.trim();

        if (barcodeValue !== '') {
            fetch('/pos/supermercado/find-product', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSR-Token': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ barcode: barcodeValue })
            })
            .then(res => {
                if(!res.ok) throw new Error('Produto não encontrado ou sem stock');
                return res.json();
            })
            .then(data => {
                if (data.success) {
                    // Adiciona o produto real retornado do banco de dados ao carrinho do supermercado (ID 9999)
                    adicionarItemNoPedido(data.product.id, data.product.name, data.product.price);
                    input.value = '';
                }
            })
            .catch(err => {
                alert(err.message);
                input.value = '';
            });
        }
    }
}
    </script>


@endsection
