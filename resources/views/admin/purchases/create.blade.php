@extends('layouts.admin')

@section('page-title', 'Nova Compra')

@section('content')
    <style>
        .purchase-form { display:grid; gap:12px; }
        .panel { background:var(--card); border:1px solid var(--border); border-radius:8px; padding:12px; }
        .form-grid { display:grid; gap:8px; grid-template-columns:repeat(4,minmax(0,1fr)); }
        .field label { color:var(--muted); display:block; font-size:10px; font-weight:900; margin-bottom:4px; text-transform:uppercase; }
        .field input,.field select,.field textarea { background:var(--input-bg); border:1px solid var(--border); border-radius:8px; color:var(--input-text); font-size:12px; min-height:32px; padding:6px 8px; width:100%; }
        .items-table { border-collapse:collapse; width:100%; }
        .items-table th,.items-table td { border-bottom:1px solid var(--border); font-size:12px; padding:7px 8px; }
        .items-table th { background:var(--soft-bg); color:var(--muted); font-size:10px; text-align:left; text-transform:uppercase; }
        .btn { border:0; border-radius:8px; cursor:pointer; font-size:12px; font-weight:900; min-height:32px; padding:0 10px; }
        .btn-primary,.btn-info { background:var(--primary); color:#111827; }
        .btn-ghost { background:var(--soft-bg); border:1px solid var(--border); color:var(--text); text-decoration:none; display:inline-flex; align-items:center; }
        .btn-danger { background:rgba(239,68,68,.14); color:#f87171; }
        .summary { color:var(--text); display:grid; gap:4px; justify-content:end; text-align:right; font-size:12px; }
        .error-box { background:#fff1f2; border:1px solid #fecdd3; border-radius:8px; color:#be123c; padding:10px; }
        @media (max-width:900px){ .form-grid{grid-template-columns:1fr;} .items-table{min-width:760px;} .table-scroll{overflow:auto;} }
    </style>

    <form method="POST" action="{{ route('admin.purchases.store') }}" class="purchase-form" id="purchase-form" enctype="multipart/form-data">
        @csrf
        @if($errors->any())<div class="error-box">{{ $errors->first() }}</div>@endif

        <div class="panel">
            <div class="form-grid">
                <div class="field"><label>Tipo</label><select name="document_type" required><option value="quotation" @selected(old('document_type') === 'quotation')>Pedido de cotacao</option><option value="order" @selected(old('document_type') === 'order')>Ordem de compra</option><option value="purchase" @selected(old('document_type', 'purchase') === 'purchase')>Compra/Fatura</option></select></div>
                <div class="field"><label>Fornecedor</label><select name="supplier_id" required><option value="">Selecionar fornecedor</option>@foreach($suppliers as $supplier)<option value="{{ $supplier->id }}" @selected(old('supplier_id') == $supplier->id)>{{ $supplier->company_name }}</option>@endforeach</select></div>
                <div class="field"><label>Nº documento</label><input name="document_number" value="{{ old('document_number') }}" placeholder="Documento interno"></div>
                <div class="field"><label>Ref. cotacao</label><input name="quotation_reference" value="{{ old('quotation_reference') }}"></div>
                <div class="field"><label>Ordem compra</label><input name="order_number" value="{{ old('order_number') }}" placeholder="OC-0001"></div>
                <div class="field"><label>Fatura fornecedor</label><input name="supplier_invoice_number" value="{{ old('supplier_invoice_number') }}"></div>
                <div class="field"><label>Data</label><input name="purchase_date" type="date" value="{{ old('purchase_date', now()->toDateString()) }}" required></div>
                <div class="field"><label>Vencimento</label><input name="due_date" type="date" value="{{ old('due_date', now()->toDateString()) }}"></div>
                <div class="field"><label>Liquidacao</label><select name="payment_type" required><option value="direct" @selected(old('payment_type', $currentAccountEnabled ? 'credit' : 'direct') === 'direct')>Pago / sem conta corrente</option>@if($currentAccountEnabled)<option value="credit" @selected(old('payment_type', 'credit') === 'credit')>Conta corrente do fornecedor</option>@endif</select></div>
                <div class="field"><label>Anexos</label><input type="file" name="attachments[]" multiple accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,.xls,.xlsx"></div>
            </div>
            <div class="field" style="margin-top:8px;"><label>Observacoes</label><textarea name="notes" rows="2">{{ old('notes') }}</textarea></div>
        </div>

        <div class="panel">
            <div style="display:flex; justify-content:space-between; gap:8px; align-items:center; margin-bottom:8px;"><strong style="color:var(--text);">Itens da compra</strong><button type="button" class="btn btn-info" onclick="addPurchaseRow()">Adicionar produto</button></div>
            <div class="table-scroll"><table class="items-table"><thead><tr><th style="width:38%;">Produto</th><th>Qtd</th><th>Custo</th><th>IVA %</th><th>Total</th><th></th></tr></thead><tbody id="purchase-items"></tbody></table></div>
            <div class="summary"><div>Subtotal: <strong id="purchase-subtotal">AOA 0,00</strong></div><div>IVA: <strong id="purchase-tax">AOA 0,00</strong></div><div>Despesas: <strong id="purchase-expense-total">AOA 0,00</strong></div><div style="font-size:15px;">Total: <strong id="purchase-total">AOA 0,00</strong></div></div>
        </div>

        <div class="panel">
            <div style="display:flex; justify-content:space-between; gap:8px; align-items:center; margin-bottom:8px;"><strong style="color:var(--text);">Despesas de compra</strong><button type="button" class="btn btn-ghost" onclick="addExpenseRow()">Adicionar despesa</button></div>
            <div id="purchase-expenses" class="form-grid"></div>
        </div>

        <div style="display:flex; gap:8px; justify-content:flex-end;"><a class="btn btn-ghost" href="{{ route('admin.purchases.index') }}">Cancelar</a><button class="btn btn-primary" type="submit">Guardar documento</button></div>
    </form>

    <template id="purchase-row-template">
        <tr><td><select data-name="product_id" required><option value="">Selecionar</option>@foreach($products as $product)<option value="{{ $product->id }}" data-cost="{{ (float) $product->purchase_price }}" data-tax="{{ (float) ($product->tax_rate ?? 0) }}">{{ $product->name }} (stock: {{ $product->stock_quantity }})</option>@endforeach</select></td><td><input data-name="quantity" type="number" min="1" value="1" required></td><td><input data-name="unit_cost" type="number" min="0" step="0.01" value="0" required></td><td><input data-name="tax_rate" type="number" min="0" max="100" step="0.01" value="0"></td><td style="color:var(--text); font-weight:900;" data-line-total>AOA 0,00</td><td><button type="button" class="btn btn-danger" onclick="removePurchaseRow(this)">Remover</button></td></tr>
    </template>
    <template id="expense-row-template">
        <div class="field"><label>Descricao</label><input data-expense-name="description" placeholder="Transporte, despacho, seguro"></div><div class="field"><label>Categoria</label><input data-expense-name="category" value="Geral"></div><div class="field"><label>Valor</label><input data-expense-name="amount" type="number" min="0" step="0.01" value="0"></div><div class="field" style="justify-content:end;"><button type="button" class="btn btn-danger" onclick="removeExpenseRow(this)">Remover</button></div>
    </template>

    <script>
        const purchaseItemsBody = document.getElementById('purchase-items');
        const purchaseTemplate = document.getElementById('purchase-row-template');
        const purchaseExpensesBody = document.getElementById('purchase-expenses');
        const expenseTemplate = document.getElementById('expense-row-template');
        const money = value => 'AOA ' + Number(value || 0).toLocaleString('pt-PT', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        function syncPurchaseNames() { purchaseItemsBody.querySelectorAll('tr').forEach((row, index) => row.querySelectorAll('[data-name]').forEach(field => field.name = `items[${index}][${field.dataset.name}]`)); }
        function syncExpenseNames() { Array.from(purchaseExpensesBody.children).forEach((field, fieldIndex) => field.querySelectorAll('[data-expense-name]').forEach(input => input.name = `expenses[${Math.floor(fieldIndex / 4)}][${input.dataset.expenseName}]`)); }
        function expenseTotal() { return Array.from(purchaseExpensesBody.querySelectorAll('[data-expense-name="amount"]')).reduce((sum, input) => sum + Number(input.value || 0), 0); }

        function recalcPurchase() {
            let subtotal = 0; let tax = 0;
            purchaseItemsBody.querySelectorAll('tr').forEach(row => {
                const quantity = Number(row.querySelector('[data-name="quantity"]').value || 0);
                const unitCost = Number(row.querySelector('[data-name="unit_cost"]').value || 0);
                const taxRate = Number(row.querySelector('[data-name="tax_rate"]').value || 0);
                const lineSubtotal = quantity * unitCost;
                const lineTax = lineSubtotal * taxRate / 100;
                subtotal += lineSubtotal; tax += lineTax;
                row.querySelector('[data-line-total]').textContent = money(lineSubtotal + lineTax);
            });
            const expenses = expenseTotal();
            document.getElementById('purchase-subtotal').textContent = money(subtotal);
            document.getElementById('purchase-tax').textContent = money(tax);
            document.getElementById('purchase-expense-total').textContent = money(expenses);
            document.getElementById('purchase-total').textContent = money(subtotal + tax + expenses);
        }

        function addPurchaseRow() {
            const fragment = purchaseTemplate.content.cloneNode(true);
            const row = fragment.querySelector('tr');
            row.querySelectorAll('input, select').forEach(field => {
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
            purchaseItemsBody.appendChild(fragment); syncPurchaseNames(); recalcPurchase();
        }
        function removePurchaseRow(button) { button.closest('tr').remove(); if (!purchaseItemsBody.querySelector('tr')) addPurchaseRow(); syncPurchaseNames(); recalcPurchase(); }
        function addExpenseRow() { purchaseExpensesBody.appendChild(expenseTemplate.content.cloneNode(true)); purchaseExpensesBody.querySelectorAll('input').forEach(input => input.oninput = recalcPurchase); syncExpenseNames(); recalcPurchase(); }
        function removeExpenseRow(button) { const fields = Array.from(purchaseExpensesBody.children); const start = Math.floor(fields.indexOf(button.closest('.field')) / 4) * 4; fields.slice(start, start + 4).forEach(field => field.remove()); syncExpenseNames(); recalcPurchase(); }
        addPurchaseRow();
    </script>
@endsection
