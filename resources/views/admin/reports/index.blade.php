@extends('layouts.admin')

@section('page-title', 'Relatórios')

@section('content')
    <style>
        .report-wrap { max-width: 980px; display: grid; gap: 18px; }
        .report-panel { background: rgba(15,23,42,.76); border: 1px solid rgba(255,255,255,.07); border-radius: 8px; padding: 18px; }
        .report-title { color: #fff; font-size: 24px; font-weight: 900; margin: 0; }
        .report-muted { color: #94a3b8; font-size: 13px; margin-top: 5px; }
        .report-grid { display: grid; gap: 12px; grid-template-columns: repeat(4, minmax(0, 1fr)); }
        .report-field { display: flex; flex-direction: column; gap: 6px; }
        .report-field label { color:#cbd5e1; font-size:11px; font-weight:900; letter-spacing:.06em; text-transform:uppercase; }
        .report-field input, .report-field select {
            background:#070a12; border:1px solid rgba(255,255,255,.09); border-radius:8px; color:#e5e7eb;
            min-height:42px; padding:10px 12px; width:100%;
        }
        .report-btn {
            align-items:center; background:#f97316; border:none; border-radius:8px; color:#111827; cursor:pointer;
            display:inline-flex; font-weight:900; justify-content:center; min-height:42px; padding:0 14px; text-decoration:none;
        }
        .report-btn-soft { background:rgba(255,255,255,.06); border:1px solid rgba(255,255,255,.08); color:#e5e7eb; }
        .report-actions { align-items:end; display:flex; gap:10px; flex-wrap:wrap; }
        @media (max-width: 860px) { .report-grid { grid-template-columns: 1fr 1fr; } }
        @media (max-width: 560px) { .report-grid { grid-template-columns: 1fr; } }
    </style>

    <div class="report-wrap">
        <div>
            <h1 class="report-title">Relatórios PDF</h1>
            <div class="report-muted">Escolha o período e gere o PDF direto.</div>
        </div>

        <form class="report-panel" method="GET" target="_blank">
            <div class="report-grid">
                <div class="report-field">
                    <label>De</label>
                    <input type="date" name="from" value="{{ $from }}">
                </div>
                <div class="report-field">
                    <label>Ate</label>
                    <input type="date" name="to" value="{{ $to }}">
                </div>
                <div class="report-actions">
                    <button class="report-btn" type="submit" formaction="{{ route('admin.reports.sales.pdf') }}">Vendas PDF</button>
                    <button class="report-btn report-btn-soft" type="submit" formaction="{{ route('admin.reports.cash.pdf') }}">Caixa PDF</button>
                    <button class="report-btn report-btn-soft" type="submit" formaction="{{ route('admin.reports.daily-postings.pdf') }}">Lançamento Diário PDF</button>
                </div>
            </div>
        </form>

        @if($modules['purchases'] ?? true)
        <form class="report-panel" method="GET" action="{{ route('admin.reports.purchases.pdf') }}" target="_blank">
            <div class="report-grid">
                <div class="report-field">
                    <label>De</label>
                    <input type="date" name="from" value="{{ $from }}">
                </div>
                <div class="report-field">
                    <label>Ate</label>
                    <input type="date" name="to" value="{{ $to }}">
                </div>
                <div class="report-field">
                    <label>Fornecedor</label>
                    <select name="supplier_id">
                        <option value="">Todos</option>
                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}">{{ $supplier->company_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="report-field">
                    <label>Estado</label>
                    <select name="status">
                        <option value="">Todos</option>
                        <option value="draft">Por enviar</option>
                        <option value="ordered">Pedido enviado</option>
                        <option value="partial">Parcial</option>
                        <option value="received">Recebidas</option>
                    </select>
                </div>
                <div class="report-field">
                    <label>Aprovacao</label>
                    <select name="approval_status">
                        <option value="">Todas</option>
                        <option value="pending">Pendentes</option>
                        <option value="approved">Aprovadas</option>
                        <option value="rejected">Rejeitadas</option>
                    </select>
                </div>
                <div class="report-field">
                    <label>Liquidação</label>
                    <select name="payment_type">
                        <option value="">Todas</option>
                        <option value="direct">Direta</option>
                        <option value="credit">Conta corrente</option>
                    </select>
                </div>
                <div class="report-field">
                    <label>Pagamento</label>
                    <select name="payment_status">
                        <option value="">Todos</option>
                        <option value="unpaid">Em aberto</option>
                        <option value="partial">Parcial</option>
                        <option value="paid">Pago</option>
                        <option value="overdue">Vencidas</option>
                    </select>
                </div>
                <div class="report-actions">
                    <button class="report-btn" type="submit">Compras PDF</button>
                </div>
            </div>
        </form>
        @endif

        @if($modules['current_account'] ?? true)
        <form class="report-panel" method="GET" action="{{ route('admin.reports.current-accounts.pdf') }}" target="_blank">
            <div class="report-grid">
                <div class="report-field">
                    <label>De</label>
                    <input type="date" name="from" value="{{ $from }}">
                </div>
                <div class="report-field">
                    <label>Ate</label>
                    <input type="date" name="to" value="{{ $to }}">
                </div>
                <div class="report-field">
                    <label>Tipo</label>
                    <select name="entity_type" id="report-entity-type">
                        <option value="">Todos</option>
                        <option value="customer">Clientes</option>
                        <option value="supplier">Fornecedores</option>
                    </select>
                </div>
                <div class="report-field" id="report-customer-field">
                    <label>Entidade</label>
                    <select name="entity_id" id="report-customer-id">
                        <option value="">Todos</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="report-field" id="report-supplier-field" style="display:none;">
                    <label>Fornecedor</label>
                    <select name="supplier_entity_id" id="report-supplier-id" disabled>
                        <option value="">Todos</option>
                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}">{{ $supplier->company_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="report-actions">
                    <button class="report-btn" type="submit">Conta Corrente PDF</button>
                </div>
            </div>
        </form>
        @endif

        <form class="report-panel" method="GET" action="{{ route('admin.reports.stock.pdf') }}" target="_blank">
            <div class="report-grid">
                <div class="report-field">
                    <label>Status</label>
                    <select name="status">
                        <option value="">Todos</option>
                        <option value="1">Ativos</option>
                        <option value="0">Inativos</option>
                    </select>
                </div>
                <div class="report-field">
                    <label>Stock baixo</label>
                    <select name="low_stock">
                        <option value="0">Todos</option>
                        <option value="1">Somente stock baixo</option>
                    </select>
                </div>
                <div class="report-actions">
                    <button class="report-btn" type="submit">Stock PDF</button>
                </div>
            </div>
        </form>

        <form class="report-panel" method="GET" action="{{ route('admin.reports.stock-movements.pdf') }}" target="_blank">
            <div class="report-grid">
                <div class="report-field">
                    <label>De</label>
                    <input type="date" name="from" value="{{ $from }}">
                </div>
                <div class="report-field">
                    <label>Ate</label>
                    <input type="date" name="to" value="{{ $to }}">
                </div>
                <div class="report-field">
                    <label>Tipo</label>
                    <select name="type">
                        <option value="">Todos</option>
                        <option value="IN">Entradas</option>
                        <option value="OUT">Saidas</option>
                    </select>
                </div>
                <div class="report-actions">
                    <button class="report-btn" type="submit">Movimentos de Stock PDF</button>
                </div>
            </div>
        </form>

        <form class="report-panel" method="GET" action="{{ route('admin.reports.shifts.pdf') }}" target="_blank">
            <div class="report-grid">
                <div class="report-field">
                    <label>De</label>
                    <input type="date" name="from" value="{{ $from }}">
                </div>
                <div class="report-field">
                    <label>Ate</label>
                    <input type="date" name="to" value="{{ $to }}">
                </div>
                <div class="report-field">
                    <label>Estado</label>
                    <select name="status">
                        <option value="">Todos</option>
                        <option value="open">Abertos</option>
                        <option value="closed">Fechados</option>
                    </select>
                </div>
                <div class="report-actions">
                    <button class="report-btn" type="submit">Fechos de Caixa PDF</button>
                </div>
            </div>
        </form>

        @if($modules['audit'] ?? true)
        <form class="report-panel" method="GET" action="{{ route('admin.reports.audit.pdf') }}" target="_blank">
            <div class="report-grid">
                <div class="report-field">
                    <label>De</label>
                    <input type="date" name="from" value="{{ $from }}">
                </div>
                <div class="report-field">
                    <label>Ate</label>
                    <input type="date" name="to" value="{{ $to }}">
                </div>
                <div class="report-field">
                    <label>Ação</label>
                    <select name="action">
                        <option value="">Todas</option>
                        @foreach($auditActions as $action)
                            <option value="{{ $action }}">{{ $action }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="report-field">
                    <label>Modelo</label>
                    <select name="model">
                        <option value="">Todos</option>
                        @foreach($auditModels as $model)
                            <option value="{{ $model }}">{{ $model }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="report-actions">
                    <button class="report-btn" type="submit">Auditoria PDF</button>
                </div>
            </div>
        </form>
        @endif
    </div>

    <script>
        const entityType = document.getElementById('report-entity-type');
        const customerField = document.getElementById('report-customer-field');
        const supplierField = document.getElementById('report-supplier-field');
        const customerSelect = document.getElementById('report-customer-id');
        const supplierSelect = document.getElementById('report-supplier-id');

        function syncEntitySelects() {
            if (!entityType || !customerField || !supplierField || !customerSelect || !supplierSelect) return;

            const type = entityType.value;
            const supplierMode = type === 'supplier';

            customerField.style.display = supplierMode ? 'none' : 'flex';
            supplierField.style.display = supplierMode ? 'flex' : 'none';
            customerSelect.disabled = supplierMode;
            supplierSelect.disabled = !supplierMode;
        }

        if (entityType) {
            entityType.addEventListener('change', syncEntitySelects);
        }
        syncEntitySelects();
    </script>
@endsection
