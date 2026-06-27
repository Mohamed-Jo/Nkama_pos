@extends('layouts.admin')

@section('page-title', 'Empresa & IVA')

@section('content')
    <style>
        .settings-wrap {
            max-width: 980px;
        }

        .settings-alert {
            background: rgba(16, 185, 129, 0.12);
            border: 1px solid rgba(16, 185, 129, 0.24);
            border-radius: 8px;
            color: #86efac;
            margin-bottom: 14px;
            padding: 10px 12px;
        }

        .settings-error {
            background: rgba(239, 68, 68, 0.12);
            border: 1px solid rgba(239, 68, 68, 0.28);
            border-radius: 8px;
            color: #fecaca;
            margin-bottom: 14px;
            padding: 10px 12px;
        }

        .settings-panel {
            background: rgba(15, 23, 42, 0.72);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 8px;
            padding: 18px;
        }

        .settings-grid {
            display: grid;
            gap: 16px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .settings-section {
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
            margin-bottom: 18px;
            padding-bottom: 18px;
        }

        .settings-section:last-of-type {
            border-bottom: none;
            margin-bottom: 0;
        }

        .settings-title {
            color: #fff;
            font-size: 15px;
            font-weight: 800;
            margin-bottom: 12px;
        }

        .settings-field label {
            color: #cbd5e1;
            display: block;
            font-size: 12px;
            font-weight: 800;
            margin-bottom: 6px;
        }

        .settings-field input {
            margin: 0;
        }

        .settings-field input[type="file"] {
            padding: 9px;
        }

        .settings-toggle {
            align-items: center;
            color: #cbd5e1;
            display: inline-flex;
            font-size: 13px;
            font-weight: 800;
            gap: 8px;
            min-height: 44px;
        }

        .settings-toggle input {
            height: 18px;
            margin: 0;
            width: 18px;
        }

        .settings-logo {
            align-items: center;
            display: flex;
            gap: 14px;
        }

        .settings-logo-preview {
            align-items: center;
            background: #070a12;
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 8px;
            display: flex;
            height: 74px;
            justify-content: center;
            overflow: hidden;
            width: 120px;
        }

        .settings-logo-preview img {
            max-height: 100%;
            max-width: 100%;
            object-fit: contain;
        }

        .settings-logo-preview span {
            color: #64748b;
            font-size: 12px;
            font-weight: 700;
        }

        .settings-actions {
            display: flex;
            justify-content: flex-end;
            margin-top: 18px;
        }

        .settings-btn {
            background: #f97316;
            border: none;
            border-radius: 8px;
            color: #111827;
            cursor: pointer;
            font-weight: 900;
            min-height: 42px;
            padding: 0 16px;
        }

        @media (max-width: 820px) {
            .settings-grid {
                grid-template-columns: 1fr;
            }

            .settings-logo {
                align-items: flex-start;
                flex-direction: column;
            }
        }
    </style>

    <div class="settings-wrap">
        @if(session('success'))
            <div class="settings-alert">{{ session('success') }}</div>
        @endif

        @if($errors->any())
            <div class="settings-error">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data" class="settings-panel">
            @csrf
            @method('PUT')

            <div class="settings-section">
                <div class="settings-title">Dados da empresa</div>

                <div class="settings-grid">
                    <div class="settings-field">
                        <label for="company-name">Nome da empresa</label>
                        <input id="company-name" type="text" name="company[name]" value="{{ old('company.name', $company['name'] ?? '') }}" required>
                    </div>

                    <div class="settings-field">
                        <label for="company-location">Localizacao</label>
                        <input id="company-location" type="text" name="company[location]" value="{{ old('company.location', $company['location'] ?? '') }}">
                    </div>

                    <div class="settings-field">
                        <label for="company-nif">NIF</label>
                        <input id="company-nif" type="text" name="company[nif]" value="{{ old('company.nif', $company['nif'] ?? '') }}">
                    </div>

                    <div class="settings-field">
                        <label for="company-iban">IBAN</label>
                        <input id="company-iban" type="text" name="company[iban]" value="{{ old('company.iban', $company['iban'] ?? '') }}">
                    </div>

                    <div class="settings-field">
                        <label for="company-account-number">No. conta</label>
                        <input id="company-account-number" type="text" name="company[account_number]" value="{{ old('company.account_number', $company['account_number'] ?? '') }}">
                    </div>

                    <div class="settings-field">
                        <label for="company-swift">SWIFT</label>
                        <input id="company-swift" type="text" name="company[swift]" value="{{ old('company.swift', $company['swift'] ?? '') }}">
                    </div>
                </div>

                <div class="settings-field" style="margin-top:16px;">
                    <label for="company-logo">Logotipo</label>
                    <div class="settings-logo">
                        <div class="settings-logo-preview">
                            @if($logoUrl)
                                <img src="{{ $logoUrl }}" alt="Logotipo da empresa">
                            @else
                                <span>Sem logotipo</span>
                            @endif
                        </div>
                        <input id="company-logo" type="file" name="company[logo]" accept="image/png,image/jpeg,image/webp">
                    </div>
                </div>
            </div>

            <div class="settings-section">
                <div class="settings-title">IVA padrão</div>

                <div class="settings-grid">
                    <label class="settings-toggle">
                        <input type="checkbox" name="tax[active]" value="1" @checked(old('tax.active', $tax['active'] ?? false))>
                        Sugerir IVA nos novos produtos
                    </label>

                    <div class="settings-field">
                        <label for="tax-value">Valor padrão do IVA (%)</label>
                        <input id="tax-value" type="number" name="tax[value]" min="0" max="100" step="0.01" value="{{ old('tax.value', $tax['value'] ?? 14) }}">
                    </div>
                </div>
            </div>

            <div class="settings-actions">
                <button type="submit" class="settings-btn">Guardar configuracoes</button>
            </div>
        </form>
    </div>
@endsection
