@extends('layouts.admin')

@section('page-title', 'Configuração de Empresa')

@section('content')
    <style>
        .company-grid { align-items:start; display:grid; gap:18px; grid-template-columns:360px minmax(0,1fr); }
        .company-panel { background:rgba(15,23,42,.72); border:1px solid rgba(255,255,255,.06); border-radius:8px; padding:18px; }
        .company-panel h2 { color:#fff; font-size:16px; margin:0 0 14px; }
        .company-field { margin-bottom:12px; }
        .company-field label { color:#94a3b8; display:block; font-size:11px; font-weight:800; margin-bottom:5px; text-transform:uppercase; }
        .company-field input, .company-modal input { background:#070a12; border:1px solid rgba(255,255,255,.08); border-radius:8px; box-sizing:border-box; color:#fff; margin:0; padding:10px 12px; width:100%; }
        .company-check { align-items:center; color:#cbd5e1; display:flex; gap:8px; font-size:13px; margin-bottom:12px; }
        .company-check input { width:auto; }
        .company-btn, .company-action { align-items:center; border:1px solid transparent; border-radius:8px; cursor:pointer; display:inline-flex; font-size:12px; font-weight:900; justify-content:center; min-height:34px; padding:0 12px; text-decoration:none; white-space:nowrap; }
        .company-btn { background:#f97316; color:#111827; min-height:40px; width:100%; }
        .company-action { background:rgba(255,255,255,.04); border-color:rgba(255,255,255,.08); color:#e2e8f0; }
        .company-action.primary { background:rgba(249,115,22,.14); border-color:rgba(249,115,22,.28); color:#fdba74; }
        .company-action:hover { border-color:rgba(249,115,22,.38); color:#fdba74; }
        .company-context { align-items:center; background:rgba(249,115,22,.1); border:1px solid rgba(249,115,22,.24); border-radius:8px; color:#fed7aa; display:flex; flex-wrap:wrap; gap:8px 12px; justify-content:space-between; margin-bottom:14px; padding:12px 14px; }
        .company-context strong { color:#fff; }
        .company-nav { display:flex; flex-wrap:wrap; gap:10px; margin-bottom:14px; }
        .company-nav a { background:rgba(255,255,255,.04); border:1px solid rgba(255,255,255,.08); border-radius:8px; color:#e2e8f0; font-size:12px; font-weight:900; padding:10px 12px; text-decoration:none; }
        .company-nav a.active { background:rgba(249,115,22,.14); border-color:rgba(249,115,22,.28); color:#fdba74; }
        .company-table-wrap { overflow-x:auto; }
        .company-table { min-width:880px; width:100%; }
        .company-name { color:#fff; font-weight:900; }
        .company-muted { color:#94a3b8; font-size:12px; margin-top:3px; }
        .company-status { border-radius:999px; display:inline-flex; font-size:11px; font-weight:900; padding:5px 9px; }
        .company-status.active { background:rgba(16,185,129,.14); color:#34d399; }
        .company-status.inactive { background:rgba(239,68,68,.14); color:#f87171; }
        .company-actions { display:flex; flex-wrap:wrap; gap:8px; justify-content:flex-end; }
        .company-alert { background:rgba(16,185,129,.12); border:1px solid rgba(16,185,129,.24); border-radius:8px; color:#86efac; margin-bottom:14px; padding:10px 12px; }
        .company-error { background:rgba(239,68,68,.12); border:1px solid rgba(239,68,68,.24); border-radius:8px; color:#fecaca; margin-bottom:14px; padding:10px 12px; }
        .company-modal { position:fixed; inset:0; z-index:10000; }
        .company-modal-backdrop { background:rgba(0,0,0,.72); inset:0; position:absolute; }
        .company-modal-box { background:#0f172a; border:1px solid rgba(255,255,255,.08); border-radius:10px; left:50%; max-height:calc(100vh - 40px); max-width:640px; overflow-y:auto; padding:18px; position:absolute; top:50%; transform:translate(-50%,-50%); width:min(640px,calc(100vw - 28px)); }
        .company-modal-head { align-items:center; display:flex; justify-content:space-between; margin-bottom:14px; }
        .company-modal-title { color:#fff; font-size:16px; font-weight:900; margin:0; }
        .company-form-grid { display:grid; gap:12px; grid-template-columns:repeat(2,minmax(0,1fr)); }
        .company-form-grid .wide { grid-column:1 / -1; }


        /* company-compact-fields */
        .company-form-grid {
            gap: 10px 12px;
        }

        .company-field {
            margin-bottom: 10px;
        }

        .company-field label {
            font-size: 10px;
            margin-bottom: 4px;
        }

        .company-field input,
        .company-modal input {
            min-height: 34px;
            padding: 7px 10px;
        }

        .company-field input[type="file"],
        .company-modal input[type="file"] {
            min-height: 34px;
            padding: 5px 8px;
        }

        .company-check {
            margin-bottom: 10px;
            min-height: 32px;
        }
        /* company-polish */
        .company-panel,
        .company-modal-box {
            background: var(--card);
            border-color: var(--border);
            box-shadow: 0 18px 45px rgba(2, 6, 23, 0.16);
            color: var(--text);
        }

        .company-panel h2,
        .company-modal-title,
        .company-name {
            color: var(--text);
        }

        .company-field label,
        .company-muted,
        .company-table th {
            color: var(--muted);
        }

        .company-field input,
        .company-modal input {
            background: var(--input-bg);
            border-color: var(--border);
            color: var(--input-text);
        }

        .company-check,
        .company-table td {
            color: var(--text);
        }

        .company-table {
            border-collapse: collapse;
        }

        .company-table th,
        .company-table td {
            border-bottom: 1px solid var(--border);
            padding: 12px 10px;
            text-align: left;
        }

        .company-table th:last-child,
        .company-table td:last-child {
            text-align: right;
        }

        .company-table tbody tr:hover {
            background: var(--soft-bg);
        }

        .company-context {
            color: #fed7aa;
        }

        .company-context strong {
            color: var(--text);
        }

        .company-nav a,
        .company-action {
            background: var(--soft-bg);
            border-color: var(--border);
            color: var(--text);
        }

        .company-nav a.active,
        .company-action.primary {
            background: rgba(249,115,22,.14);
            border-color: rgba(249,115,22,.34);
            color: #fdba74;
        }

        :root[data-theme="light"] .company-panel,
        :root[data-theme="light"] .company-modal-box {
            box-shadow: 0 12px 32px rgba(15, 23, 42, 0.07);
        }

        :root[data-theme="light"] .company-context {
            background: #fff7ed;
            border-color: #fed7aa;
            color: #9a3412;
        }

        :root[data-theme="light"] .company-nav a.active,
        :root[data-theme="light"] .company-action.primary {
            background: #ffedd5;
            border-color: #fdba74;
            color: #9a3412;
        }

        :root[data-theme="light"] .company-status.active {
            background: #ecfdf5;
            color: #047857;
        }

        :root[data-theme="light"] .company-status.inactive {
            background: #fff1f2;
            color: #be123c;
        }
        @media (max-width:1050px) { .company-grid { grid-template-columns:1fr; } }
        @media (max-width:720px) { .company-form-grid { grid-template-columns:1fr; } }
    </style>

    @if(session('success'))
        <div class="company-alert">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="company-error">{{ $errors->first() }}</div>
    @endif

    <div class="company-context">
        @php($activeCompany = \App\Services\BusinessSettings::company())
        <span>Empresa em uso: <strong>{{ session('company_name') ?: ($activeCompany['name'] ?: config('app.name', 'NKAMA POS')) }}</strong></span>
        <span>{{ !empty($activeCompany['nif']) ? 'NIF: ' . $activeCompany['nif'] : 'Sem NIF configurado' }}</span>
    </div>

    <div class="company-nav">
        <a href="{{ route('admin.settings.index') }}">Dados, IVA e impressão</a>
        <a class="active" href="{{ route('admin.companies.index') }}">Empresas registadas</a>
    </div>

    <div class="company-grid">
        <section class="company-panel">
            <h2>Nova empresa</h2>
            <form method="POST" action="{{ route('admin.companies.store') }}" enctype="multipart/form-data">
                @csrf
                @include('admin.companies.partials.form-fields', ['company' => null, 'prefix' => 'new'])
                <button class="company-btn" type="submit">Registar empresa</button>
            </form>
        </section>

        <section class="company-panel">
            <h2>Empresas registadas</h2>
            <div class="company-table-wrap">
                <table class="company-table">
                    <thead>
                        <tr>
                            <th>Empresa</th>
                            <th>NIF</th>
                            <th>Banco</th>
                            <th>Operadores</th>
                            <th>Estado</th>
                            <th style="text-align:right;">Acoes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($companies as $company)
                            <tr>
                                <td>
                                    <div class="company-name">{{ $company->name }}</div>
                                    <div class="company-muted">{{ $company->location ?: '-' }}</div>
                                </td>
                                <td>{{ $company->nif ?: '-' }}</td>
                                <td>{{ $company->bank_name ?: '-' }}</td>
                                <td>{{ $company->operators_count }}</td>
                                <td><span class="company-status {{ $company->active ? 'active' : 'inactive' }}">{{ $company->active ? 'Ativa' : 'Inativa' }}</span></td>
                                <td>
                                    <div class="company-actions">
                                        @if((int) $currentCompanyId === $company->id)
                                            <span class="company-action primary">Em uso</span>
                                        @elseif($company->active)
                                            <form method="POST" action="{{ route('admin.companies.switch', $company) }}">
                                                @csrf
                                                <button class="company-action primary" type="submit">Usar</button>
                                            </form>
                                        @else
                                            <span class="company-action">Inativa</span>
                                        @endif
                                        <button class="company-action js-edit-company"
                                            type="button"
                                            data-update-url="{{ route('admin.companies.update', $company) }}"
                                            data-name="{{ e($company->name) }}"
                                            data-location="{{ e($company->location) }}"
                                            data-nif="{{ e($company->nif) }}"
                                            data-iban="{{ e($company->iban) }}"
                                            data-account-number="{{ e($company->account_number) }}"
                                            data-bank-name="{{ e($company->bank_name) }}"
                                            data-swift="{{ e($company->swift) }}"
                                            data-active="{{ $company->active ? '1' : '0' }}"
                                            data-has-logo="{{ $company->logo_path ? '1' : '0' }}"
                                            data-has-background="{{ $company->login_background_path ? '1' : '0' }}">
                                            Editar
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" style="text-align:center; color:#94a3b8; padding:28px;">Nenhuma empresa registada.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div style="margin-top:12px;">{{ $companies->links() }}</div>
        </section>
    </div>

    <div id="company-edit-modal" class="company-modal hidden" aria-hidden="true">
        <div class="company-modal-backdrop" data-close-company-modal></div>
        <div class="company-modal-box" role="dialog" aria-modal="true" aria-labelledby="company-edit-title">
            <div class="company-modal-head">
                <h2 id="company-edit-title" class="company-modal-title">Editar empresa</h2>
                <button class="company-action" type="button" data-close-company-modal>Fechar</button>
            </div>
            <form id="company-edit-form" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                @include('admin.companies.partials.form-fields', ['company' => null, 'prefix' => 'edit'])
                <button class="company-btn" type="submit">Guardar alteracoes</button>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const modal = document.getElementById('company-edit-modal');
            const form = document.getElementById('company-edit-form');

            function setValue(name, value) {
                const input = form.querySelector(`[name="${name}"]`);
                if (input) input.value = value || '';
            }

            function closeModal() {
                modal.classList.add('hidden');
                modal.setAttribute('aria-hidden', 'true');
            }

            document.querySelectorAll('.js-edit-company').forEach(function (button) {
                button.addEventListener('click', function () {
                    form.action = button.dataset.updateUrl;
                    setValue('name', button.dataset.name);
                    setValue('location', button.dataset.location);
                    setValue('nif', button.dataset.nif);
                    setValue('iban', button.dataset.iban);
                    setValue('account_number', button.dataset.accountNumber);
                    setValue('bank_name', button.dataset.bankName);
                    setValue('swift', button.dataset.swift);
                    form.querySelector('[name="active"]').checked = button.dataset.active === '1';
                    form.querySelector('[name="remove_logo"]').closest('label').style.display = button.dataset.hasLogo === '1' ? 'flex' : 'none';
                    form.querySelector('[name="remove_login_background"]').closest('label').style.display = button.dataset.hasBackground === '1' ? 'flex' : 'none';
                    form.querySelectorAll('input[type="file"]').forEach(input => input.value = '');
                    modal.classList.remove('hidden');
                    modal.setAttribute('aria-hidden', 'false');
                    form.querySelector('[name="name"]').focus();
                });
            });

            document.querySelectorAll('[data-close-company-modal]').forEach(button => button.addEventListener('click', closeModal));
            document.addEventListener('keydown', event => {
                if (event.key === 'Escape' && !modal.classList.contains('hidden')) closeModal();
            });
        });
    </script>
@endsection
