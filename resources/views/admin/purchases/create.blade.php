@extends('layouts.admin')

@section('page-title', 'Nova Compra')

@section('content')
    <style>
        .purchase-form { display: grid; gap: 16px; }
        .panel { background: #0f172a; border: 1px solid rgba(255,255,255,.07); border-radius: 8px; padding: 18px; }
        .form-grid { display: grid; gap: 14px; grid-template-columns: repeat(4, minmax(0, 1fr)); }
        .field label { color: #94a3b8; display: block; font-size: 12px; font-weight: 900; margin-bottom: 6px; text-transform: uppercase; }
        .field input, .field select, .field textarea { background: #020617; border: 1px solid #1e293b; border-radius: 8px; color: #fff; padding: 11px; width: 100%; }
        .items-table { border-collapse: collapse; width: 100%; }
        .items-table th, .items-table td { border-bottom: 1px solid rgba(255,255,255,.06); padding: 10px; }
        .items-table th { color: #94a3b8; font-size: 12px; text-align: left; text-transform: uppercase; }
        .btn { border: none; border-radius: 8px; cursor: pointer; font-weight: 900; padding: 11px 14px; }
        .btn-primary { background: #10b981; color: #020617; }
        .btn-info { background: #38bdf8; color: #020617; }
        .btn-ghost { background: #020617; border: 1px solid #1e293b; color: #94a3b8; text-decoration: none; }
        .btn-danger { background: rgba(239,68,68,.15); color: #fecaca; }
        .summary { color: #fff; display: grid; gap: 8px; justify-content: end; text-align: right; }
        .error-box { background: rgba(239,68,68,.12); border: 1px solid rgba(239,68,68,.28); border-radius: 8px; color: #fecaca; padding: 12px 14px; }
        @media (max-width: 900px) { .form-grid { grid-template-columns: 1fr; } .items-table { min-width: 760px; } .table-scroll { overflow:auto; } }
    </style>

    <form method="POST" action="{{ route('admin.purchases.store') }}" class="purchase-form" id="purchase-form">
        @csrf

        @if($errors->any())
            <div class="error-box">{{ $errors->first() }}</div>
        @endif

        <div class="panel">
            <div class="form-grid">
                <div class="field">
                    <label>Fornecedor</label>
                    <select name="supplier_id" required>
                        <option value="">Selecionar fornecedor</option>
                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" @selected(old('supplier_id') == $supplier->id)>{{ $supplier->company_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label>Nº documento</label>
                    <input name="document_number" value="{{ old('document_number') }}" placeholder="Factura do fornecedor">
                </div>
                <div class="field">
                    <label>Data</label>
                    <input name="purchase_date" type="date" value="{{ old('purchase_date', now()->toDateString()) }}" required>
                </div>
                <div class="field">
                    <label>Liquidação</label>
                    <select name="payment_type" required>
                        <option value="direct" @selected(old('payment_type', $currentAccountEnabled ? 'credit' : 'direct') === 'direct')>Pago / sem conta corrente</option>
                        @if($currentAccountEnabled)
                            <option value="credit" @selected(old('payment_type', 'credit') === 'credit')>Conta corrente do fornecedor</option>
                        @endif
                    </select>
                </div>
            </div>
            <div class="field" style="margin-top:14px;">
                <label>Observações</label>
                <textarea name="notes" rows="2">{{ old('notes') }}</textarea>
            </div>
        </div>

        <div class="panel">
            <div style="display:flex; justify-content:space-between; gap:12px; align-items:center; margin-bottom:12px;">
                <strong style="color:#fff;">Itens da compra</strong>
                <button type="button" class="btn btn-info" onclick="addPurchaseRow()">Adicionar produto</button>
            </div>
            <div class="table-scroll">
                <table class="items-table">
                    <thead>
                        <tr>
                            <th style="width:38%;">Produto</th>
                            <th>Qtd</th>
                            <th>Custo</th>
                            <th>IVA %</th>
                            <th>Total</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="purchase-items"></tbody>
                </table>
            </div>
            <div class="summary">
                <div>Subtotal: <strong id="purchase-subtotal">AOA 0,00</strong></div>
                <div>IVA: <strong id="purchase-tax">AOA 0,00</strong></div>
                <div style="font-size:18px;">Total: <strong id="purchase-total">AOA 0,00</strong></div>
            </div>
        </div>

        <div style="display:flex; gap:10px; justify-content:flex-end;">
            <a class="btn btn-ghost" href="{{ route('admin.purchases.index') }}">Cancelar</a>
            <button class="btn btn-primary" type="submit">Guardar compra</button>
        </div>
    </form>

    <template id="purchase-row-template">
        <tr>
            <td>
                <select data-name="product_id" required>
                    <option value="">Selecionar</option>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}" data-cost="{{ (float) $product->purchase_price }}" data-tax="{{ (float) ($product->tax_rate ?? 0) }}">
                            {{ $product->name }} (stock: {{ $product->stock_quantity }})
                        </option>
                    @endforeach
                </select>
            </td>
            <td><input data-name="quantity" type="number" min="1" value="1" required></td>
            <td><input data-name="unit_cost" type="number" min="0" step="0.01" value="0" required></td>
            <td><input data-name="tax_rate" type="number" min="0" max="100" step="0.01" value="0"></td>
            <td style="color:#fff; font-weight:900;" data-line-total>AOA 0,00</td>
            <td><button type="button" class="btn btn-danger" onclick="removePurchaseRow(this)">Remover</button></td>
        </tr>
    </template>

    <script>
        const purchaseItemsBody = document.getElementById('purchase-items');
        const purchaseTemplate = document.getElementById('purchase-row-template');

        function money(value) {
            return 'AOA ' + Number(value || 0).toLocaleString('pt-PT', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function syncPurchaseNames() {
            purchaseItemsBody.querySelectorAll('tr').forEach((row, index) => {
                row.querySelectorAll('[data-name]').forEach((field) => {
                    field.name = `items[${index}][${field.dataset.name}]`;
                });
            });
        }

        function recalcPurchase() {
            let subtotal = 0;
            let tax = 0;

            purchaseItemsBody.querySelectorAll('tr').forEach((row) => {
                const quantity = Number(row.querySelector('[data-name="quantity"]').value || 0);
                const unitCost = Number(row.querySelector('[data-name="unit_cost"]').value || 0);
                const taxRate = Number(row.querySelector('[data-name="tax_rate"]').value || 0);
                const lineSubtotal = quantity * unitCost;
                const lineTax = lineSubtotal * taxRate / 100;
                const lineTotal = lineSubtotal + lineTax;

                subtotal += lineSubtotal;
                tax += lineTax;
                row.querySelector('[data-line-total]').textContent = money(lineTotal);
            });

            document.getElementById('purchase-subtotal').textContent = money(subtotal);
            document.getElementById('purchase-tax').textContent = money(tax);
            document.getElementById('purchase-total').textContent = money(subtotal + tax);
        }

        function addPurchaseRow() {
            const fragment = purchaseTemplate.content.cloneNode(true);
            const row = fragment.querySelector('tr');

            row.querySelectorAll('input, select').forEach((field) => {
                field.addEventListener('input', recalcPurchase);
                field.addEventListener('change', () => {
                    if (field.dataset.name === 'product_id') {
                        const selected = field.options[field.selectedIndex];
                        row.querySelector('[data-name="unit_cost"]').value = selected?.dataset.cost || 0;
                        row.querySelector('[data-name="tax_rate"]').value = selected?.dataset.tax || 0;
                    }
                    recalcPurchase();
                });
            });

            purchaseItemsBody.appendChild(fragment);
            syncPurchaseNames();
            recalcPurchase();
        }

        function removePurchaseRow(button) {
            button.closest('tr').remove();
            if (!purchaseItemsBody.querySelector('tr')) {
                addPurchaseRow();
            }
            syncPurchaseNames();
            recalcPurchase();
        }

        addPurchaseRow();
    </script>
@endsection
