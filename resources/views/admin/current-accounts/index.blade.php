@extends('layouts.admin')

@section('page-title', 'Conta Corrente')

@section('content')
    <style>
        .cc-shell {
            display: grid;
            gap: 20px;
        }

        .cc-panel {
            background: rgba(17, 24, 39, 0.78);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 8px;
            padding: 20px;
        }

        .cc-header {
            align-items: flex-start;
            display: flex;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 20px;
        }

        .cc-title {
            color: #fff;
            font-size: 26px;
            font-weight: 800;
            margin: 0;
        }

        .cc-subtitle {
            color: #94a3b8;
            font-size: 13px;
            margin-top: 5px;
        }

        .cc-grid {
            display: grid;
            gap: 14px;
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }

        .cc-form-grid {
            display: grid;
            gap: 14px;
            grid-template-columns: repeat(6, minmax(0, 1fr));
        }

        .cc-field {
            display: flex;
            flex-direction: column;
            gap: 7px;
        }

        .cc-field label {
            color: #94a3b8;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .cc-field input,
        .cc-field select {
            background: #070a12;
            border: 1px solid rgba(255, 255, 255, 0.09);
            border-radius: 8px;
            color: #e5e7eb;
            min-height: 43px;
            padding: 10px 12px;
            width: 100%;
        }

        .cc-span-2 {
            grid-column: span 2;
        }

        .cc-stat {
            background: rgba(255, 255, 255, 0.035);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 8px;
            padding: 16px;
        }

        .cc-stat span {
            color: #94a3b8;
            display: block;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .cc-stat strong {
            color: #fff;
            display: block;
            font-size: 24px;
            margin-top: 8px;
        }

        .cc-table-wrap {
            overflow-x: auto;
        }

        .cc-table {
            border-collapse: collapse;
            min-width: 920px;
            width: 100%;
        }

        .cc-table th {
            background: rgba(255, 255, 255, 0.04);
            color: #94a3b8;
            font-size: 11px;
            letter-spacing: .08em;
            padding: 12px;
            text-align: left;
            text-transform: uppercase;
        }

        .cc-table td {
            border-top: 1px solid rgba(255, 255, 255, 0.06);
            color: #e5e7eb;
            padding: 13px 12px;
        }

        .cc-muted {
            color: #94a3b8;
            font-size: 12px;
        }

        .cc-debit {
            color: #fbbf24;
            font-weight: 800;
        }

        .cc-credit {
            color: #34d399;
            font-weight: 800;
        }

        .cc-negative {
            color: #fb7185;
        }

        .cc-actions {
            align-items: end;
            display: flex;
            gap: 10px;
        }

        .cc-btn {
            align-items: center;
            border: 0;
            border-radius: 8px;
            cursor: pointer;
            display: inline-flex;
            font-weight: 800;
            justify-content: center;
            min-height: 43px;
            padding: 0 16px;
            text-decoration: none;
        }

        .cc-btn-primary {
            background: #f97316;
            color: #111827;
        }

        .cc-btn-ghost {
            background: rgba(255, 255, 255, 0.06);
            color: #e5e7eb;
        }

        @media (max-width: 1000px) {
            .cc-grid,
            .cc-form-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .cc-span-2 {
                grid-column: span 2;
            }
        }

        @media (max-width: 640px) {
            .cc-header {
                display: block;
            }

            .cc-grid,
            .cc-form-grid {
                grid-template-columns: 1fr;
            }

            .cc-span-2 {
                grid-column: span 1;
            }

            .cc-actions {
                align-items: stretch;
                flex-direction: column;
            }
        }
    </style>

    <div class="cc-shell">
        <div class="cc-header">
            <div>
                <h1 class="cc-title">Conta Corrente</h1>
                <div class="cc-subtitle">Extrato de clientes e fornecedores com lançamentos a débito e crédito.</div>
            </div>
        </div>

        <div class="cc-grid">
            <div class="cc-stat">
                <span>Débitos</span>
                <strong>{{ number_format($totalDebit, 2, ',', '.') }} Kz</strong>
            </div>
            <div class="cc-stat">
                <span>Créditos</span>
                <strong>{{ number_format($totalCredit, 2, ',', '.') }} Kz</strong>
            </div>
            <div class="cc-stat">
                <span>Saldo</span>
                <strong class="{{ $balance < 0 ? 'cc-negative' : '' }}">{{ number_format($balance, 2, ',', '.') }} Kz</strong>
            </div>
            <div class="cc-stat">
                <span>Entidades com movimento</span>
                <strong>{{ $balances->count() }}</strong>
            </div>
        </div>

        <div class="cc-panel">
            <form method="POST" action="{{ route('admin.current-accounts.store') }}" id="cc-entry-form">
                @csrf
                <div class="cc-form-grid">
                    <div class="cc-field">
                        <label>Tipo</label>
                        <select name="entity_type" id="entry_entity_type" required>
                            <option value="customer" @selected(old('entity_type') === 'customer')>Cliente</option>
                            <option value="supplier" @selected(old('entity_type') === 'supplier')>Fornecedor</option>
                        </select>
                    </div>

                    <div class="cc-field cc-span-2">
                        <label>Entidade</label>
                        <select name="entity_id" id="entry_entity_id" required></select>
                    </div>

                    <div class="cc-field">
                        <label>Movimento</label>
                        <select name="movement_type" required>
                            <option value="debit" @selected(old('movement_type') === 'debit')>Débito</option>
                            <option value="credit" @selected(old('movement_type') === 'credit')>Crédito</option>
                        </select>
                    </div>

                    <div class="cc-field">
                        <label>Valor</label>
                        <input type="number" min="0.01" step="0.01" name="amount" value="{{ old('amount') }}" required>
                    </div>

                    <div class="cc-field">
                        <label>Data</label>
                        <input type="date" name="entry_date" value="{{ old('entry_date', now()->toDateString()) }}" required>
                    </div>

                    <div class="cc-field cc-span-2">
                        <label>Descrição</label>
                        <input type="text" name="description" maxlength="255" value="{{ old('description') }}" placeholder="Ex.: Venda a crédito, pagamento, acerto">
                    </div>

                    <div class="cc-actions">
                        <button class="cc-btn cc-btn-primary" type="submit">Registar</button>
                    </div>
                </div>
            </form>
        </div>

        <div class="cc-panel">
            <form method="GET" action="{{ route('admin.current-accounts.index') }}" class="cc-form-grid">
                <div class="cc-field">
                    <label>Filtrar tipo</label>
                    <select name="entity_type" id="filter_entity_type">
                        <option value="">Todos</option>
                        <option value="customer" @selected(($filters['entity_type'] ?? '') === 'customer')>Clientes</option>
                        <option value="supplier" @selected(($filters['entity_type'] ?? '') === 'supplier')>Fornecedores</option>
                    </select>
                </div>

                <div class="cc-field cc-span-2">
                    <label>Filtrar entidade</label>
                    <select name="entity_id" id="filter_entity_id"></select>
                </div>

                <div class="cc-actions">
                    <button class="cc-btn cc-btn-primary" type="submit">Filtrar</button>
                    <a class="cc-btn cc-btn-ghost" href="{{ route('admin.current-accounts.index') }}">Limpar</a>
                </div>
            </form>
        </div>

        <div class="cc-panel">
            <div class="cc-table-wrap">
                <table class="cc-table">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Entidade</th>
                            <th>Descrição</th>
                            <th>Débito</th>
                            <th>Crédito</th>
                            <th>Operador</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($entries as $entry)
                            <tr>
                                <td>{{ $entry->entry_date->format('d/m/Y') }}</td>
                                <td>
                                    <strong>{{ $entry->entity_name }}</strong>
                                    <div class="cc-muted">{{ $entry->entity_type === 'customer' ? 'Cliente' : 'Fornecedor' }}</div>
                                </td>
                                <td>{{ $entry->description ?: 'Movimento manual' }}</td>
                                <td class="cc-debit">{{ $entry->debit > 0 ? number_format((float) $entry->debit, 2, ',', '.') . ' Kz' : '-' }}</td>
                                <td class="cc-credit">{{ $entry->credit > 0 ? number_format((float) $entry->credit, 2, ',', '.') . ' Kz' : '-' }}</td>
                                <td>{{ $entry->operator?->name ?? 'Sistema' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="cc-muted" style="text-align:center; padding:32px;">Sem movimentos na conta corrente.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div style="margin-top:16px;">
                {{ $entries->links() }}
            </div>
        </div>

        <div class="cc-panel">
            <div class="cc-table-wrap">
                <table class="cc-table">
                    <thead>
                        <tr>
                            <th>Entidade</th>
                            <th>Débitos</th>
                            <th>Créditos</th>
                            <th>Saldo</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($balances as $row)
                            <tr>
                                <td>
                                    <strong>{{ $row->name }}</strong>
                                    <div class="cc-muted">{{ $row->entity_type === 'customer' ? 'Cliente' : 'Fornecedor' }}</div>
                                </td>
                                <td class="cc-debit">{{ number_format((float) $row->debit, 2, ',', '.') }} Kz</td>
                                <td class="cc-credit">{{ number_format((float) $row->credit, 2, ',', '.') }} Kz</td>
                                <td class="{{ $row->balance < 0 ? 'cc-negative' : '' }}">{{ number_format($row->balance, 2, ',', '.') }} Kz</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="cc-muted" style="text-align:center; padding:32px;">Nenhuma entidade com saldo.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        const ccCustomers = @json($customers->map(fn ($customer) => ['id' => $customer->id, 'name' => $customer->name])->values());
        const ccSuppliers = @json($suppliers->map(fn ($supplier) => ['id' => $supplier->id, 'name' => $supplier->company_name])->values());
        const oldEntryType = @json(old('entity_type', 'customer'));
        const oldEntryId = @json((string) old('entity_id', ''));
        const filterType = @json($filters['entity_type'] ?? '');
        const filterId = @json((string) ($filters['entity_id'] ?? ''));

        function fillEntitySelect(typeSelectId, entitySelectId, selectedId, allowAll) {
            const type = document.getElementById(typeSelectId).value;
            const select = document.getElementById(entitySelectId);
            const list = type === 'supplier' ? ccSuppliers : ccCustomers;
            select.innerHTML = '';

            if (allowAll) {
                const option = document.createElement('option');
                option.value = '';
                option.textContent = type ? 'Todas as entidades' : 'Todas';
                select.appendChild(option);
            }

            if (!type && allowAll) {
                return;
            }

            list.forEach(item => addOption(select, item.id, item.name, selectedId));
        }

        function addOption(select, value, label, selectedId) {
            const option = document.createElement('option');
            option.value = value;
            option.textContent = label;
            option.selected = String(value) === String(selectedId);
            select.appendChild(option);
        }

        document.getElementById('entry_entity_type').value = oldEntryType;
        fillEntitySelect('entry_entity_type', 'entry_entity_id', oldEntryId, false);
        fillEntitySelect('filter_entity_type', 'filter_entity_id', filterId, true);

        document.getElementById('entry_entity_type').addEventListener('change', () => {
            fillEntitySelect('entry_entity_type', 'entry_entity_id', '', false);
        });

        document.getElementById('filter_entity_type').addEventListener('change', () => {
            fillEntitySelect('filter_entity_type', 'filter_entity_id', '', true);
        });

        @if(session('success'))
            nkamaAlert(@json(session('success')), 'success');
        @endif

        @if($errors->any())
            nkamaAlert(@json($errors->first()), 'error');
        @endif
    </script>
@endsection
