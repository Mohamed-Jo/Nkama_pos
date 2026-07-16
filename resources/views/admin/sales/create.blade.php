@extends('layouts.admin')

@section('page-title', 'Nova Venda')

@section('content')
    <style>
        .erp-sale { display: grid; gap: 14px; }
        .erp-toolbar { align-items: center; display: flex; gap: 10px; justify-content: space-between; }
        .erp-title { color: #f8fafc; font-size: 18px; font-weight: 900; margin: 0; }
        .erp-panel { background: rgba(15, 23, 42, .72); border: 1px solid rgba(255,255,255,.07); border-radius: 8px; padding: 16px; }
        .erp-grid { display: grid; gap: 12px; grid-template-columns: 2fr 1fr 1fr; }
        .erp-field label { color: #94a3b8; display: block; font-size: 11px; font-weight: 900; margin-bottom: 6px; text-transform: uppercase; }
        .erp-field select, .erp-field input { background: #020617; border: 1px solid #1e293b; border-radius: 8px; color: #fff; min-height: 42px; padding: 10px; width: 100%; }
        .erp-table-wrap { overflow-x: auto; }
        .erp-table { border-collapse: collapse; min-width: 860px; width: 100%; }
        .erp-table th, .erp-table td { border-bottom: 1px solid rgba(255,255,255,.06); padding: 9px; }
        .erp-table th { color: #94a3b8; font-size: 11px; text-align: left; text-transform: uppercase; }
        .erp-table td { color: #e2e8f0; vertical-align: middle; }
        .erp-table input { background: #020617; border: 1px solid #1e293b; border-radius: 8px; color: #fff; min-height: 36px; padding: 8px; width: 100%; }
        .erp-number { text-align: right; }
        .erp-actions { display: flex; gap: 10px; justify-content: flex-end; }
        .erp-btn { align-items: center; border: 1px solid transparent; border-radius: 8px; cursor: pointer; display: inline-flex; font-weight: 900; gap: 8px; min-height: 38px; padding: 0 13px; text-decoration: none; white-space: nowrap; }
        .processing-spinner { animation: processing-spin .75s linear infinite; border: 2px solid rgba(17,24,39,.28); border-left-color: #111827; border-radius: 999px; display: none; flex: 0 0 16px; height: 16px; width: 16px; }
        .erp-btn.is-processing .processing-spinner { display: inline-block; }
        .erp-btn.is-processing { cursor: wait; opacity: .86; }
        @keyframes processing-spin { to { transform: rotate(360deg); } }
        .erp-btn-primary { background: #f97316; color: #111827; }
        .erp-btn-secondary { background: rgba(56, 189, 248, .12); border-color: rgba(56, 189, 248, .28); color: #7dd3fc; }
        .erp-btn-ghost { background: rgba(255,255,255,.04); border-color: rgba(255,255,255,.08); color: #cbd5e1; }
        .erp-btn-danger { background: rgba(239,68,68,.12); border-color: rgba(239,68,68,.28); color: #fca5a5; }
        .erp-summary-grid { align-items: start; display: grid; gap: 14px; grid-template-columns: minmax(0, 1fr) 360px; }
        .total-box { background: #020617; border: 1px solid #1e293b; border-radius: 8px; padding: 14px; }
        .total-line { color: #cbd5e1; display: flex; justify-content: space-between; padding: 5px 0; }
        .total-line strong { color: #fff; }
        .total-grand { border-top: 1px solid rgba(255,255,255,.08); font-size: 18px; font-weight: 900; margin-top: 8px; padding-top: 11px; }
        .erp-status { border-radius: 8px; display: none; font-weight: 800; padding: 12px; }
        .erp-status.ok { background: rgba(34,197,94,.12); border: 1px solid rgba(34,197,94,.28); color: #86efac; display: block; }
        .erp-status.error { background: rgba(239,68,68,.12); border: 1px solid rgba(239,68,68,.28); color: #fca5a5; display: block; }
        .empty-lines { color: #94a3b8; padding: 24px; text-align: center; }
        .erp-modal-backdrop { align-items: center; background: rgba(2, 6, 23, .78); display: none; inset: 0; justify-content: center; padding: 20px; position: fixed; z-index: 10020; }
        .erp-modal-backdrop.open { display: flex; }
        .erp-modal { background: #0f172a; border: 1px solid rgba(255,255,255,.12); border-radius: 8px; box-shadow: 0 24px 80px rgba(0,0,0,.45); display: grid; gap: 12px; max-height: calc(100vh - 40px); overflow: hidden; padding: 16px; width: min(1120px, 100%); }
        .erp-modal.small { width: min(720px, 100%); }
        .modal-head { align-items: center; display: flex; gap: 12px; justify-content: space-between; }
        .modal-tools { align-items: center; display: flex; gap: 10px; }
        .modal-search { background: #020617; border: 1px solid #1e293b; border-radius: 8px; color: #fff; min-height: 40px; padding: 9px 11px; width: min(460px, 100%); }
        .modal-scroll { overflow: auto; }
        .product-table { border-collapse: collapse; min-width: 840px; width: 100%; }
        .product-table th, .product-table td { border-bottom: 1px solid rgba(255,255,255,.06); padding: 8px; }
        .product-table th { color: #94a3b8; font-size: 11px; text-align: left; text-transform: uppercase; }
        .product-table td { color: #e2e8f0; }
        .product-table input[type="number"] { background: #020617; border: 1px solid #1e293b; border-radius: 8px; color: #fff; min-height: 34px; padding: 7px; width: 90px; }
        .selected-count { color: #94a3b8; font-size: 12px; font-weight: 800; }
        .payment-grid { display: grid; gap: 10px; grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .customer-picker { align-items: center; background: linear-gradient(135deg, rgba(15,23,42,.92), rgba(2,6,23,.78)); border: 1px solid rgba(148,163,184,.18); border-radius: 8px; display: flex; gap: 12px; justify-content: space-between; min-height: 72px; padding: 12px; }
        .customer-main { min-width: 0; }
        .customer-label { color: #94a3b8; font-size: 11px; font-weight: 900; text-transform: uppercase; }
        .customer-name { color: #fff; font-size: 16px; font-weight: 900; margin-top: 3px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .customer-meta { color: #94a3b8; font-size: 12px; margin-top: 3px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .customer-actions { display: flex; flex: 0 0 auto; gap: 8px; }
        .erp-modal-backdrop { backdrop-filter: blur(10px); }
        .erp-modal { background: linear-gradient(145deg, rgba(15,23,42,.98), rgba(2,6,23,.98)); border-color: rgba(148,163,184,.22); box-shadow: 0 28px 90px rgba(0,0,0,.56), inset 0 1px 0 rgba(255,255,255,.05); }
        .modal-head { border-bottom: 1px solid rgba(148,163,184,.12); padding-bottom: 12px; }
        .modal-title-block { display: grid; gap: 3px; }
        .modal-kicker { color: #38bdf8; font-size: 11px; font-weight: 900; letter-spacing: .08em; text-transform: uppercase; }
        .modal-subtitle { color: #94a3b8; font-size: 12px; }
        .customer-table { border-collapse: separate; border-spacing: 0 6px; min-width: 760px; width: 100%; }
        .customer-table th { color: #94a3b8; font-size: 11px; padding: 7px 10px; text-align: left; text-transform: uppercase; }
        .customer-table td { background: rgba(15,23,42,.62); border-bottom: 1px solid rgba(148,163,184,.08); border-top: 1px solid rgba(148,163,184,.08); color: #e2e8f0; padding: 10px; }
        .customer-table td:first-child { border-left: 1px solid rgba(148,163,184,.08); border-radius: 8px 0 0 8px; }
        .customer-table td:last-child { border-right: 1px solid rgba(148,163,184,.08); border-radius: 0 8px 8px 0; }
        .customer-table tr:hover td { background: rgba(249,115,22,.08); border-color: rgba(249,115,22,.22); }
        .customer-code { color: #94a3b8; font-size: 12px; margin-top: 2px; }
        .shift-panel { align-items: center; background: linear-gradient(135deg, rgba(5,150,105,.14), rgba(15,23,42,.82)); border: 1px solid rgba(52,211,153,.18); border-radius: 8px; display: flex; gap: 12px; justify-content: space-between; padding: 14px; }
        .shift-panel.closed { background: linear-gradient(135deg, rgba(249,115,22,.13), rgba(15,23,42,.82)); border-color: rgba(249,115,22,.24); }
        .shift-status { display: grid; gap: 3px; min-width: 0; }
        .shift-kicker { color: #94a3b8; font-size: 11px; font-weight: 900; letter-spacing: .08em; text-transform: uppercase; }
        .shift-title { color: #fff; font-size: 16px; font-weight: 900; }
        .shift-note { color: #94a3b8; font-size: 12px; }
        .shift-dot { border-radius: 999px; display: inline-block; height: 9px; margin-right: 6px; width: 9px; }
        .shift-dot.open { background: #22c55e; box-shadow: 0 0 0 4px rgba(34,197,94,.14); }
        .shift-dot.closed { background: #f97316; box-shadow: 0 0 0 4px rgba(249,115,22,.14); }
        @media (max-width: 980px) { .erp-grid, .erp-summary-grid { grid-template-columns: 1fr; } .payment-grid { grid-template-columns: 1fr; } .erp-toolbar, .modal-head { align-items: stretch; flex-direction: column; } .modal-tools { align-items: stretch; flex-direction: column; } }
    </style>

    <form class="erp-sale" id="manual-sale-form">
        @csrf
        <div class="erp-toolbar">
            <h2 class="erp-title">Nova venda</h2>
            <div class="erp-actions">
                <a class="erp-btn erp-btn-ghost" href="{{ route('admin.sales.index') }}">Voltar</a>
                <button class="erp-btn erp-btn-primary" id="submit-sale" type="submit">Gravar / Pagar</button>
            </div>
        </div>

        <div id="sale-status" class="erp-status"></div>

        <div class="erp-panel shift-panel {{ $shift ? '' : 'closed' }}" id="manual-shift-panel">
            <div class="shift-status">
                <span class="shift-kicker">Caixa</span>
                <span class="shift-title" id="manual-shift-title">
                    <span class="shift-dot {{ $shift ? 'open' : 'closed' }}" id="manual-shift-dot"></span>
                    {{ $shift ? 'Caixa aberto' : 'Caixa fechado' }}
                </span>
                <span class="shift-note" id="manual-shift-note">
                    {{ $shift ? 'Pagamentos desta venda entram no fecho atual.' : 'Vendas pagas precisam de caixa aberto; FT 100% a credito pode ser emitida sem caixa.' }}
                </span>
            </div>
            @if($canOpenShift)
                <button class="erp-btn erp-btn-secondary" id="manual-open-shift-button" type="button" onclick="openShiftModal()" @if($shift) style="display:none;" @endif>Abrir caixa</button>
            @endif
        </div>
        <div class="erp-panel">
            <div class="erp-grid">
                <div class="erp-field">
                    <input id="customer-id" name="customer_id" type="hidden" value="">
                    <div class="customer-picker">
                        <div class="customer-main">
                            <div class="customer-label">Cliente</div>
                            <div class="customer-name" id="selected-customer-name">Consumidor Final</div>
                            <div class="customer-meta" id="selected-customer-meta">Sem cliente associado ao documento.</div>
                        </div>
                        <div class="customer-actions">
                            <button class="erp-btn erp-btn-secondary" type="button" onclick="openCustomerModal()">Selecionar cliente</button>
                            <button class="erp-btn erp-btn-ghost" type="button" onclick="setFinalConsumer()">Consumidor Final</button>
                        </div>
                    </div>
                </div>
                <div class="erp-field">
                    <label>Data</label>
                    <input type="date" value="{{ now()->toDateString() }}" disabled>
                </div>
                <div class="erp-field">
                    <label>Operador</label>
                    <input type="text" value="{{ session('operator_name', 'Operador') }}" disabled>
                </div>
            </div>
        </div>

        <div class="erp-panel">
            <div class="erp-grid">
                <div class="erp-field">
                    <label>Moeda</label>
                    <input id="invoice-currency" type="text" maxlength="12" value="{{ $invoiceSettings['currency'] ?? 'AOA' }}">
                </div>
                <div class="erp-field">
                    <label>Cambio</label>
                    <input id="invoice-exchange-rate" class="erp-number" type="number" min="0.000001" step="0.000001" value="{{ $invoiceSettings['exchange_rate'] ?? 1 }}">
                </div>
                <div class="erp-field">
                    <label>Desconto comercial (%)</label>
                    <input id="commercial-discount" class="erp-number" type="number" min="0" max="100" step="0.01" value="{{ $invoiceSettings['commercial_discount'] ?? 0 }}">
                </div>
                <div class="erp-field">
                    <label>Condicao de pagamento</label>
                    <input id="payment-condition" type="text" maxlength="120" value="{{ $invoiceSettings['payment_condition'] ?? 'Pronto pagamento' }}">
                </div>
                <div class="erp-field">
                    <label>Vencimento</label>
                    <input id="due-date" type="date" value="{{ $invoiceDueDate }}">
                </div>
                <div class="erp-field">
                    <label>Motivo de isencao</label>
                    <input id="exemption-reason" type="text" maxlength="255" value="{{ $invoiceSettings['exemption_reason'] ?? '' }}">
                </div>
            </div>
        </div>
        <div class="erp-panel">
            <div class="erp-toolbar" style="margin-bottom:12px;">
                <h3 class="erp-title" style="font-size:15px;">Linhas do documento</h3>
                <button class="erp-btn erp-btn-secondary" type="button" onclick="openProductModal()">Selecionar artigos</button>
            </div>
            <div class="erp-table-wrap">
                <table class="erp-table">
                    <thead>
                        <tr>
                            <th style="width:34%;">Artigo</th>
                            <th>Stock</th>
                            <th>Qtd</th>
                            <th class="erp-number">Preco</th>
                            <th class="erp-number">IVA</th>
                            <th class="erp-number">Total</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="sale-items"></tbody>
                </table>
                <div class="empty-lines" id="empty-lines">Sem artigos selecionados.</div>
            </div>
        </div>

        <div class="erp-summary-grid">
            <div></div>
            <div class="total-box">
                <div class="total-line"><span>Incidencia</span><strong id="net-total">AOA 0,00</strong></div>
                <div class="total-line"><span>IVA</span><strong id="tax-total">AOA 0,00</strong></div>
                <div class="total-line"><span>Desconto</span><strong id="discount-total">AOA 0,00</strong></div>
                <div class="total-line total-grand"><span>Total</span><strong id="gross-total">AOA 0,00</strong></div>
                <div class="total-line"><span>Pago</span><strong id="paid-total">AOA 0,00</strong></div>
                <div class="total-line"><span>Troco</span><strong id="change-total">AOA 0,00</strong></div>
                <div class="total-line"><span>Pendente</span><strong id="due-total">AOA 0,00</strong></div>
            </div>
        </div>
    </form>

    <div class="erp-modal-backdrop" id="shift-modal" aria-hidden="true">
        <div class="erp-modal small">
            <div class="modal-head">
                <div class="modal-title-block">
                    <span class="modal-kicker">Caixa</span>
                    <h3 class="erp-title" style="font-size:16px;">Abertura de caixa</h3>
                    <span class="modal-subtitle">Informe o fundo inicial para receber pagamentos nesta venda.</span>
                </div>
                <button class="erp-btn erp-btn-ghost" type="button" onclick="closeShiftModal()">Fechar</button>
            </div>
            <div class="erp-field">
                <label>Fundo inicial</label>
                <input class="erp-number" id="manual-opening-cash" type="number" min="0" step="0.01" value="0">
            </div>
            <div class="erp-actions">
                <button class="erp-btn erp-btn-ghost" type="button" onclick="closeShiftModal()">Cancelar</button>
                <button class="erp-btn erp-btn-primary" id="manual-confirm-open-shift" type="button" onclick="openManualShift()">Abrir caixa</button>
            </div>
        </div>
    </div>
    <div class="erp-modal-backdrop" id="customer-modal" aria-hidden="true">
        <div class="erp-modal">
            <div class="modal-head">
                <div class="modal-title-block">
                    <span class="modal-kicker">Documento</span>
                    <h3 class="erp-title" style="font-size:16px;">Selecionar cliente</h3>
                    <span class="modal-subtitle">Se nenhum cliente for escolhido, a venda fica como Consumidor Final.</span>
                </div>
                <div class="modal-tools">
                    <input class="modal-search" id="customer-search" type="search" placeholder="Pesquisar por nome, telefone ou email">
                    <button class="erp-btn erp-btn-ghost" type="button" onclick="setFinalConsumer(); closeCustomerModal();">Consumidor Final</button>
                    <button class="erp-btn erp-btn-ghost" type="button" onclick="closeCustomerModal()">Fechar</button>
                </div>
            </div>
            <div class="modal-scroll">
                <table class="customer-table">
                    <thead>
                        <tr>
                            <th>Cliente</th>
                            <th>Telefone</th>
                            <th>Email</th>
                            <th style="text-align:right;">Acao</th>
                        </tr>
                    </thead>
                    <tbody id="customer-list"></tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="erp-modal-backdrop" id="product-modal" aria-hidden="true">
        <div class="erp-modal">
            <div class="modal-head">
                <div class="modal-title-block">
                    <span class="modal-kicker">Catalogo</span>
                    <h3 class="erp-title" style="font-size:16px;">Selecionar artigos</h3>
                    <span class="modal-subtitle">Pesquise, marque os artigos e indique a quantidade antes de inserir no documento.</span>
                </div>
                <div class="modal-tools">
                    <input class="modal-search" id="product-search" type="search" placeholder="Pesquisar por artigo ou codigo">
                    <span class="selected-count" id="selected-count">0 selecionados</span>
                    <button class="erp-btn erp-btn-ghost" type="button" onclick="closeProductModal()">Fechar</button>
                    <button class="erp-btn erp-btn-primary" type="button" onclick="applySelectedProducts()">Inserir</button>
                </div>
            </div>
            <div class="modal-scroll">
                <table class="product-table">
                    <thead>
                        <tr>
                            <th style="width:42px;"></th>
                            <th>Artigo</th>
                            <th>Codigo</th>
                            <th class="erp-number">Stock</th>
                            <th class="erp-number">Preco</th>
                            <th class="erp-number">IVA</th>
                            <th class="erp-number">Qtd</th>
                        </tr>
                    </thead>
                    <tbody id="product-list"></tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="erp-modal-backdrop" id="payment-modal" aria-hidden="true">
        <div class="erp-modal small">
            <div class="modal-head">
                <div class="modal-title-block">
                    <span class="modal-kicker">Finalizacao</span>
                    <h3 class="erp-title" style="font-size:16px;">Pagamento</h3>
                    <span class="modal-subtitle">Confirme os valores recebidos para emitir a fatura A4.</span>
                </div>
                <button class="erp-btn erp-btn-ghost" type="button" onclick="closePaymentModal()">Fechar</button>
            </div>
            <div class="total-box">
                <div class="total-line"><span>Desconto</span><strong id="payment-discount-total">AOA 0,00</strong></div>
                <div class="total-line total-grand"><span>Total do documento</span><strong id="payment-gross-total">AOA 0,00</strong></div>
                <div class="total-line"><span>Pago</span><strong id="payment-paid-total">AOA 0,00</strong></div>
                <div class="total-line"><span>Troco</span><strong id="payment-change-total">AOA 0,00</strong></div>
                <div class="total-line"><span>Pendente</span><strong id="payment-due-total">AOA 0,00</strong></div>
            </div>
            <div class="payment-grid">
                <div class="erp-field"><label>Numerario</label><input class="payment-input erp-number" id="pay-cash" type="number" min="0" step="0.01" value="0"></div>
                <div class="erp-field"><label>Cartao</label><input class="payment-input erp-number" id="pay-card" type="number" min="0" step="0.01" value="0"></div>
                <div class="erp-field"><label>Multicaixa</label><input class="payment-input erp-number" id="pay-multi" type="number" min="0" step="0.01" value="0"></div>
                <div class="erp-field"><label>Transferencia</label><input class="payment-input erp-number" id="pay-transf" type="number" min="0" step="0.01" value="0"></div>
            </div>
            <div class="erp-actions">
                <button class="erp-btn erp-btn-ghost" type="button" onclick="closePaymentModal()">Cancelar</button>
                <button class="erp-btn erp-btn-primary" id="confirm-payment" type="button" onclick="emitSale()"><span class="processing-spinner" aria-hidden="true"></span><span class="button-label">Confirmar pagamento</span></button>
            </div>
        </div>
    </div>

    @php
        $customerPayload = $customers->map(function ($customer) {
            return [
                'id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->phone,
                'email' => $customer->email,
                'address' => $customer->address,
            ];
        })->values();

        $productPayload = $products->map(function ($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'barcode' => $product->barcode,
                'price' => (float) $product->selling_price,
                'tax' => (float) ($product->tax_rate ?? 0),
                'stock' => (float) $product->stock_quantity,
                'unit' => $product->unit,
            ];
        })->values();
    @endphp

    <script>
        const customers = @json($customerPayload);
        const products = @json($productPayload);
        const storeUrl = @json(route('admin.sales.store'));
        const showUrlTemplate = @json(route('admin.sales.show', ['sale' => '__SALE_ID__']));
        const invoiceA4UrlTemplate = @json(route('admin.sales.invoice-a4', ['sale' => '__SALE_ID__']));
        const shiftOpenUrl = @json(route('admin.shift.open'));
        const canOpenShift = @json($canOpenShift);
        let manualShiftOpen = @json((bool) $shift);
        let documentLines = [];
        let modalSelections = {};
        let selectedCustomerId = null;

        function currentCurrency() {
            return (document.getElementById('invoice-currency')?.value || 'AOA').trim().toUpperCase() || 'AOA';
        }

        function money(value) {
            return currentCurrency() + ' ' + Number(value || 0).toLocaleString('pt-PT', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function readCommercialDiscount() {
            return Math.min(Math.max(Number(document.getElementById('commercial-discount')?.value || 0), 0), 100);
        }

        function escapeHtml(value) {
            return String(value ?? '').replace(/[&<>"]/g, (char) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[char]));
        }

        function splitGross(gross, taxRate) {
            const divisor = 1 + (Number(taxRate || 0) / 100);
            const net = divisor > 0 ? gross / divisor : gross;
            return { net, tax: gross - net };
        }

        function findProduct(productId) {
            return products.find((product) => Number(product.id) === Number(productId));
        }
        function findCustomer(customerId) {
            return customers.find((customer) => Number(customer.id) === Number(customerId));
        }

        function customerMeta(customer) {
            if (!customer) return 'Sem cliente associado ao documento.';
            return [customer.phone, customer.email, customer.address].filter(Boolean).join(' | ') || 'Cliente sem contactos registados.';
        }

        function renderSelectedCustomer() {
            const customer = selectedCustomerId ? findCustomer(selectedCustomerId) : null;
            document.getElementById('customer-id').value = customer ? customer.id : '';
            document.getElementById('selected-customer-name').textContent = customer ? customer.name : 'Consumidor Final';
            document.getElementById('selected-customer-meta').textContent = customerMeta(customer);
        }

        function setFinalConsumer() {
            selectedCustomerId = null;
            renderSelectedCustomer();
        }

        function selectCustomer(customerId) {
            selectedCustomerId = Number(customerId) || null;
            renderSelectedCustomer();
            closeCustomerModal();
        }

        function renderCustomerList() {
            const search = document.getElementById('customer-search').value.trim().toLowerCase();
            const body = document.getElementById('customer-list');
            const filtered = customers.filter((customer) => {
                const haystack = `${customer.name || ''} ${customer.phone || ''} ${customer.email || ''} ${customer.address || ''}`.toLowerCase();
                return !search || haystack.includes(search);
            });

            body.innerHTML = filtered.map((customer) => `
                <tr>
                    <td><strong>${escapeHtml(customer.name)}</strong><div class="customer-code">${escapeHtml(customer.address || 'Sem endereco')}</div></td>
                    <td>${escapeHtml(customer.phone || '-')}</td>
                    <td>${escapeHtml(customer.email || '-')}</td>
                    <td style="text-align:right;"><button class="erp-btn erp-btn-primary" type="button" onclick="selectCustomer(${Number(customer.id)})">Selecionar</button></td>
                </tr>
            `).join('') || '<tr><td colspan="4" style="color:#94a3b8; text-align:center; padding:24px;">Nenhum cliente encontrado.</td></tr>';
        }

        function openCustomerModal() {
            document.getElementById('customer-modal').classList.add('open');
            document.getElementById('customer-modal').setAttribute('aria-hidden', 'false');
            renderCustomerList();
            document.getElementById('customer-search').focus();
        }

        function closeCustomerModal() {
            document.getElementById('customer-modal').classList.remove('open');
            document.getElementById('customer-modal').setAttribute('aria-hidden', 'true');
        }

        function recalcSale() {
            let grossBeforeDiscount = 0;
            let netBeforeDiscount = 0;
            let taxBeforeDiscount = 0;

            documentLines.forEach((line) => {
                const product = findProduct(line.id);
                if (!product) return;
                const lineGross = Number(line.qty || 0) * Number(product.price || 0);
                const split = splitGross(lineGross, product.tax);
                grossBeforeDiscount += lineGross;
                netBeforeDiscount += split.net;
                taxBeforeDiscount += split.tax;
            });

            const discountRate = readCommercialDiscount();
            const discountFactor = Math.max(0, 1 - (discountRate / 100));
            const discount = grossBeforeDiscount * (discountRate / 100);
            const gross = Math.max(grossBeforeDiscount - discount, 0);
            const net = netBeforeDiscount * discountFactor;
            const tax = taxBeforeDiscount * discountFactor;
            const payments = readPayments();
            const paid = payments.cash + payments.card + payments.multi + payments.transf;
            const change = Math.min(Math.max(paid - gross, 0), Math.max(payments.cash, 0));
            const due = Math.max(gross - paid, 0);

            document.getElementById('net-total').textContent = money(net);
            document.getElementById('tax-total').textContent = money(tax);
            document.getElementById('discount-total').textContent = money(discount);
            document.getElementById('gross-total').textContent = money(gross);
            document.getElementById('paid-total').textContent = money(paid);
            document.getElementById('change-total').textContent = money(change);
            document.getElementById('due-total').textContent = money(due);
            document.getElementById('payment-discount-total').textContent = money(discount);
            document.getElementById('payment-gross-total').textContent = money(gross);
            document.getElementById('payment-paid-total').textContent = money(paid);
            document.getElementById('payment-change-total').textContent = money(change);
            document.getElementById('payment-due-total').textContent = money(due);

            return { gross, net, tax, discount, paid, change, due };
        }

        function readPayments() {
            return {
                cash: Number(document.getElementById('pay-cash').value || 0),
                card: Number(document.getElementById('pay-card').value || 0),
                multi: Number(document.getElementById('pay-multi').value || 0),
                transf: Number(document.getElementById('pay-transf').value || 0),
            };
        }
        function paymentTotal() {
            const payments = readPayments();
            return payments.cash + payments.card + payments.multi + payments.transf;
        }

        function applyShiftState(open) {
            manualShiftOpen = Boolean(open);
            const panel = document.getElementById('manual-shift-panel');
            const dot = document.getElementById('manual-shift-dot');
            const title = document.getElementById('manual-shift-title');
            const note = document.getElementById('manual-shift-note');
            const button = document.getElementById('manual-open-shift-button');

            panel?.classList.toggle('closed', !manualShiftOpen);
            dot?.classList.toggle('open', manualShiftOpen);
            dot?.classList.toggle('closed', !manualShiftOpen);
            if (title) title.innerHTML = `<span class="shift-dot ${manualShiftOpen ? 'open' : 'closed'}" id="manual-shift-dot"></span>${manualShiftOpen ? 'Caixa aberto' : 'Caixa fechado'}`;
            if (note) note.textContent = manualShiftOpen
                ? 'Pagamentos desta venda entram no fecho atual.'
                : 'Vendas pagas precisam de caixa aberto; FT 100% a credito pode ser emitida sem caixa.';
            if (button) button.style.display = manualShiftOpen ? 'none' : 'inline-flex';
        }

        function openShiftModal() {
            if (!canOpenShift) {
                setStatus('Sem permissao para abrir caixa.', 'error');
                return;
            }
            document.getElementById('shift-modal').classList.add('open');
            document.getElementById('shift-modal').setAttribute('aria-hidden', 'false');
            document.getElementById('manual-opening-cash').focus();
        }

        function closeShiftModal() {
            document.getElementById('shift-modal').classList.remove('open');
            document.getElementById('shift-modal').setAttribute('aria-hidden', 'true');
        }

        async function openManualShift() {
            const button = document.getElementById('manual-confirm-open-shift');
            const openingCash = Number(document.getElementById('manual-opening-cash').value || 0);
            button.disabled = true;
            button.textContent = 'Abrindo...';

            try {
                const response = await fetch(shiftOpenUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({ opening_cash: openingCash }),
                });
                const data = await response.json().catch(() => ({}));
                if (!response.ok || !data.success) throw new Error(data.message || 'Nao foi possivel abrir o caixa.');
                applyShiftState(true);
                closeShiftModal();
                setStatus(data.message || 'Caixa aberto com sucesso.', 'ok');
            } catch (error) {
                if (invoiceWindow) {
                    invoiceWindow.close();
                }
                setStatus(error.message, 'error');
            } finally {
                button.disabled = false;
                button.textContent = 'Abrir caixa';
            }
        }

        function renderLines() {
            const body = document.getElementById('sale-items');
            const empty = document.getElementById('empty-lines');
            body.innerHTML = '';
            empty.style.display = documentLines.length ? 'none' : 'block';

            documentLines.forEach((line) => {
                const product = findProduct(line.id);
                if (!product) return;
                const lineTotal = Number(line.qty || 0) * Number(product.price || 0);
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td><strong>${escapeHtml(product.name)}</strong><div style="color:#94a3b8; font-size:12px;">${escapeHtml(product.barcode || '-')}</div></td>
                    <td>${Number(product.stock || 0).toLocaleString('pt-PT')} ${escapeHtml(product.unit || '')}</td>
                    <td><input class="erp-number" type="number" min="0.01" step="0.01" value="${Number(line.qty || 1)}" data-line-qty="${product.id}"></td>
                    <td class="erp-number">${money(product.price)}</td>
                    <td class="erp-number">${Number(product.tax || 0).toLocaleString('pt-PT', { maximumFractionDigits: 2 })}%</td>
                    <td class="erp-number" style="font-weight:900;">${money(lineTotal)}</td>
                    <td><button class="erp-btn erp-btn-danger" type="button" data-remove-line="${product.id}">Remover</button></td>
                `;
                body.appendChild(row);
            });

            body.querySelectorAll('[data-line-qty]').forEach((input) => {
                input.addEventListener('input', () => {
                    const line = documentLines.find((item) => Number(item.id) === Number(input.dataset.lineQty));
                    if (line) line.qty = Math.max(Number(input.value || 0), 0.01);
                    renderLines();
                });
            });

            body.querySelectorAll('[data-remove-line]').forEach((button) => {
                button.addEventListener('click', () => {
                    documentLines = documentLines.filter((line) => Number(line.id) !== Number(button.dataset.removeLine));
                    renderLines();
                });
            });

            recalcSale();
        }

        function renderProductList() {
            const search = document.getElementById('product-search').value.trim().toLowerCase();
            const body = document.getElementById('product-list');
            const filtered = products.filter((product) => {
                const haystack = `${product.name || ''} ${product.barcode || ''}`.toLowerCase();
                return !search || haystack.includes(search);
            });

            body.innerHTML = filtered.map((product) => {
                const selected = modalSelections[product.id];
                const checked = selected ? 'checked' : '';
                const qty = selected ? selected.qty : 1;
                return `
                    <tr>
                        <td><input type="checkbox" data-product-check="${product.id}" ${checked}></td>
                        <td><strong>${escapeHtml(product.name)}</strong></td>
                        <td>${escapeHtml(product.barcode || '-')}</td>
                        <td class="erp-number">${Number(product.stock || 0).toLocaleString('pt-PT')} ${escapeHtml(product.unit || '')}</td>
                        <td class="erp-number">${money(product.price)}</td>
                        <td class="erp-number">${Number(product.tax || 0).toLocaleString('pt-PT', { maximumFractionDigits: 2 })}%</td>
                        <td class="erp-number"><input type="number" min="0.01" step="0.01" value="${Number(qty || 1)}" data-product-qty="${product.id}"></td>
                    </tr>
                `;
            }).join('') || '<tr><td colspan="7" style="color:#94a3b8; text-align:center; padding:24px;">Nenhum artigo encontrado.</td></tr>';

            body.querySelectorAll('[data-product-check]').forEach((checkbox) => checkbox.addEventListener('change', () => {
                const id = Number(checkbox.dataset.productCheck);
                const qtyInput = document.querySelector(`#product-list [data-product-qty="${id}"]`);
                if (checkbox.checked) {
                    modalSelections[id] = { qty: Math.max(Number(qtyInput?.value || 1), 0.01) };
                } else {
                    delete modalSelections[id];
                }
                updateSelectedCount();
            }));
            body.querySelectorAll('[data-product-qty]').forEach((input) => input.addEventListener('input', () => {
                const id = Number(input.dataset.productQty);
                if (modalSelections[id]) {
                    modalSelections[id].qty = Math.max(Number(input.value || 1), 0.01);
                }
            }));
            updateSelectedCount();
        }

        function updateSelectedCount() {
            const count = Object.keys(modalSelections).length;
            document.getElementById('selected-count').textContent = count + ' selecionado' + (count === 1 ? '' : 's');
        }

        function openProductModal() {
            modalSelections = Object.fromEntries(documentLines.map((line) => [line.id, { qty: line.qty }]));
            document.getElementById('product-modal').classList.add('open');
            document.getElementById('product-modal').setAttribute('aria-hidden', 'false');
            renderProductList();
            document.getElementById('product-search').focus();
        }

        function closeProductModal() {
            document.getElementById('product-modal').classList.remove('open');
            document.getElementById('product-modal').setAttribute('aria-hidden', 'true');
        }

        function applySelectedProducts() {
            documentLines = Object.entries(modalSelections).map(([id, selection]) => ({
                id: Number(id),
                qty: Math.max(Number(selection.qty || 1), 0.01),
            }));
            renderLines();
            closeProductModal();
        }

        function openPaymentModal() {
            recalcSale();
            document.getElementById('payment-modal').classList.add('open');
            document.getElementById('payment-modal').setAttribute('aria-hidden', 'false');
            document.getElementById('pay-cash').focus();
        }

        function closePaymentModal() {
            document.getElementById('payment-modal').classList.remove('open');
            document.getElementById('payment-modal').setAttribute('aria-hidden', 'true');
        }

        function setProcessingButton(button, processing, label) {
            if (!button) return;
            const labelEl = button.querySelector('.button-label');
            if (labelEl && label) labelEl.textContent = label;
            button.classList.toggle('is-processing', processing);
            button.disabled = processing;
            button.setAttribute('aria-busy', processing ? 'true' : 'false');
        }

        function setStatus(message, type) {
            const box = document.getElementById('sale-status');
            box.textContent = message;
            box.className = 'erp-status ' + type;
        }

        document.getElementById('customer-search').addEventListener('input', renderCustomerList);
        document.getElementById('product-search').addEventListener('input', renderProductList);
        document.querySelectorAll('.payment-input').forEach((field) => field.addEventListener('input', recalcSale));
        ['invoice-currency', 'commercial-discount'].forEach((id) => document.getElementById(id)?.addEventListener('input', recalcSale));

        document.getElementById('manual-sale-form').addEventListener('submit', (event) => {
            event.preventDefault();
            if (!documentLines.length) {
                setStatus('Selecione pelo menos um artigo.', 'error');
                return;
            }
            openPaymentModal();
        });

        async function emitSale() {
            const totals = recalcSale();
            const submit = document.getElementById('submit-sale');
            const confirm = document.getElementById('confirm-payment');
            const items = documentLines.map((line) => ({ id: line.id, qty: line.qty })).filter((item) => item.id && Number(item.qty) > 0);

            if (!items.length) {
                closePaymentModal();
                setStatus('Selecione pelo menos um artigo.', 'error');
                return;
            }

            if (totals.paid > 0 && !manualShiftOpen) {
                setStatus('Abra o caixa antes de confirmar pagamentos. Para FT a credito, deixe os pagamentos a zero.', 'error');
                openShiftModal();
                return;
            }

            if (totals.due > 0 && !selectedCustomerId) {
                setStatus('Venda a credito exige cliente selecionado.', 'error');
                return;
            }

            submit.disabled = true;
            setProcessingButton(confirm, true, 'A processar...');
            setStatus('A emitir documento...', 'ok');
            const invoiceWindow = window.open('about:blank', '_blank');

            try {
                const payments = readPayments();
                const response = await fetch(storeUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({
                        customer_id: selectedCustomerId,
                        items,
                        payments,
                        total: totals.gross,
                        currency: currentCurrency(),
                        exchange_rate: Number(document.getElementById('invoice-exchange-rate')?.value || 1),
                        exemption_reason: document.getElementById('exemption-reason')?.value || '',
                        commercial_discount: readCommercialDiscount(),
                        payment_condition: document.getElementById('payment-condition')?.value || '',
                        due_date: document.getElementById('due-date')?.value || null,
                    }),
                });

                const data = await response.json().catch(() => ({}));
                if (!response.ok || !data.success) throw new Error(data.error || 'Nao foi possivel emitir a venda.');

                setStatus('Documento ' + data.invoice + ' emitido com sucesso.', 'ok');
                const invoiceUrl = invoiceA4UrlTemplate.replace('__SALE_ID__', data.sale_id);
                if (invoiceWindow) {
                    invoiceWindow.location.href = invoiceUrl;
                    window.location.href = showUrlTemplate.replace('__SALE_ID__', data.sale_id);
                } else {
                    window.location.href = invoiceUrl;
                }
            } catch (error) {
                if (invoiceWindow) {
                    invoiceWindow.close();
                }
                setStatus(error.message, 'error');
                submit.disabled = false;
                setProcessingButton(confirm, false, 'Confirmar pagamento');
            }
        }

        document.addEventListener('keydown', (event) => {
            if (event.key !== 'Escape') return;
            closeShiftModal();
            closeCustomerModal();
            closeProductModal();
            closePaymentModal();
        });

        renderSelectedCustomer();
        applyShiftState(manualShiftOpen);
        renderLines();
    </script>
@endsection