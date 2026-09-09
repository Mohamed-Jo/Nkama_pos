@extends('layouts.admin')

@section('content')
    <style>
        .supplier-form-wrap {
            max-width: 1040px;
            margin: 0 auto;
            padding: 28px 20px;
        }

        .supplier-header {
            align-items: flex-start;
            display: flex;
            justify-content: space-between;
            gap: 18px;
            margin-bottom: 22px;
        }

        .supplier-title {
            color: var(--text);
            font-size: 26px;
            font-weight: 800;
            line-height: 1.1;
            margin: 0;
        }

        .supplier-subtitle {
            color: var(--muted);
            font-size: 13px;
            margin-top: 6px;
        }

        .supplier-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 18px;
        }

        .supplier-grid {
            display: grid;
            grid-template-columns: 1fr 280px;
            gap: 16px;
        }

        .field-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }

        .field-full {
            grid-column: 1 / -1;
        }

        .supplier-card label {
            color: var(--muted);
            display: block;
            font-size: 11px;
            font-weight: 800;
            margin-bottom: 6px;
            text-transform: uppercase;
        }

        .supplier-card input,
        .supplier-card textarea,
        .supplier-card select {
            background: var(--input-bg);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--input-text);
            font-size: 13px;
            outline: none;
            padding: 10px 11px;
            width: 100%;
        }

        .supplier-card textarea {
            min-height: 92px;
            resize: vertical;
        }

        .supplier-actions {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .btn-primary,
        .btn-secondary {
            align-items: center;
            border-radius: 8px;
            display: inline-flex;
            font-size: 13px;
            font-weight: 800;
            justify-content: center;
            min-height: 40px;
            padding: 10px 14px;
            text-decoration: none;
        }

        .btn-primary {
            background: var(--primary);
            color: #fff;
        }

        .btn-secondary {
            background: var(--input-bg);
            border: 1px solid var(--border);
            color: var(--text);
        }

        .status-note {
            border-top: 1px solid var(--border);
            color: var(--muted);
            font-size: 12px;
            line-height: 1.5;
            margin-top: 16px;
            padding-top: 14px;
        }

        @media (max-width: 860px) {
            .supplier-grid,
            .field-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="supplier-form-wrap">
        <div class="supplier-header">
            <div>
                <h1 class="supplier-title">Editar Fornecedor</h1>
                <p class="supplier-subtitle">Atualize os dados comerciais e o estado operacional do fornecedor.</p>
            </div>
            <a href="{{ route('admin.suppliers.index') }}" class="btn-secondary">Voltar</a>
        </div>

        <form method="POST" action="{{ route('admin.suppliers.update', $supplier) }}" class="supplier-grid">
            @csrf
            @method('PUT')

            <div class="supplier-card">
                <div class="field-grid">
                    <div class="field-full">
                        <label for="company_name">Nome da Empresa</label>
                        <input id="company_name" type="text" name="company_name" value="{{ old('company_name', $supplier->company_name) }}" required>
                        @error('company_name') <small class="text-red-500">{{ $message }}</small> @enderror
                    </div>

                    <div>
                        <label for="contact_person">Pessoa de Contacto</label>
                        <input id="contact_person" type="text" name="contact_person" value="{{ old('contact_person', $supplier->contact_person) }}">
                        @error('contact_person') <small class="text-red-500">{{ $message }}</small> @enderror
                    </div>

                    <div>
                        <label for="phone">Telefone</label>
                        <input id="phone" type="text" name="phone" value="{{ old('phone', $supplier->phone) }}">
                        @error('phone') <small class="text-red-500">{{ $message }}</small> @enderror
                    </div>

                    <div>
                        <label for="email">Email Corporativo</label>
                        <input id="email" type="email" name="email" value="{{ old('email', $supplier->email) }}">
                        @error('email') <small class="text-red-500">{{ $message }}</small> @enderror
                    </div>

                    <div>
                        <label for="status">Estado</label>
                        <select id="status" name="status">
                            <option value="1" @selected((int) old('status', $supplier->status) === 1)>Ativo</option>
                            <option value="0" @selected((int) old('status', $supplier->status) === 0)>Inativo</option>
                        </select>
                        @error('status') <small class="text-red-500">{{ $message }}</small> @enderror
                    </div>

                    <div class="field-full">
                        <label for="address">Endereco Fiscal</label>
                        <textarea id="address" name="address">{{ old('address', $supplier->address) }}</textarea>
                        @error('address') <small class="text-red-500">{{ $message }}</small> @enderror
                    </div>
                </div>
            </div>

            <aside class="supplier-card supplier-actions">
                <button type="submit" class="btn-primary">Guardar Alteracoes</button>
                <a href="{{ route('admin.suppliers.index') }}" class="btn-secondary">Cancelar</a>

                <p class="status-note">
                    Use o estado Inativo quando o fornecedor ja tem compras associadas, mas nao deve aparecer como fornecedor disponivel para novas operacoes.
                </p>
            </aside>
        </form>
    </div>
@endsection