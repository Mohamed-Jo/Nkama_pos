<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'Nkama ERP') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="{{ asset('vendor/offline/sweetalert2.all.min.js') }}"></script>

    <script>
        (function() {
            const storedTheme = localStorage.getItem('nkama_theme') || 'dark';
            document.documentElement.dataset.theme = storedTheme === 'light' ? 'light' : 'dark';
        })();
    </script>



    <style>
        :root {
            color-scheme: dark;
            --bg: #0b0f19;
            --panel: rgba(17, 24, 39, 0.95);
            --card: #111827;
            --border: rgba(255, 255, 255, 0.06);
            --text: #e5e7eb;
            --muted: #9ca3af;
            --primary: #f97316;
            --input-bg: #070a12;
            --input-text: #ffffff;
            --soft-bg: rgba(255, 255, 255, 0.04);
            --page-bg: radial-gradient(circle at top right, #1a1f2e, #0b0f19);
            --swal-bg: #0f172a;
            --swal-text: #e2e8f0;
        }

        :root[data-theme="light"] {
            color-scheme: light;
            --bg: #f8fafc;
            --panel: rgba(255, 255, 255, 0.96);
            --card: #ffffff;
            --border: rgba(15, 23, 42, 0.12);
            --text: #0f172a;
            --muted: #64748b;
            --primary: #ea580c;
            --input-bg: #ffffff;
            --input-text: #0f172a;
            --soft-bg: rgba(15, 23, 42, 0.04);
            --page-bg: linear-gradient(135deg, #f8fafc, #e8eef6);
            --swal-bg: #ffffff;
            --swal-text: #0f172a;
        }

        body {
            margin: 0;
            font-family: 'Inter', system-ui, sans-serif;
            background: var(--page-bg);
            color: var(--text);
            height: 100vh;
            overflow: hidden;
        }

        .app {
            display: flex;
            height: 100vh;
            width: 100vw;
            overflow: hidden;
        }

        .sidebar {
            width: 260px;
            min-width: 260px;
            background: var(--panel);
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            backdrop-filter: blur(12px);
            transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            overflow: hidden;
            height: 100%;
            z-index: 50;
        }

        .sidebar.collapsed,
        .sidebar-collapsed-init .sidebar {
            width: 0;
            min-width: 0;
            border-right: none;
        }

        .logo {
            align-items: center;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: flex-start;
            min-height: 92px;
            padding: 18px 20px;
        }

        .logo img {
            display: block;
            max-height: 64px;
            max-width: 178px;
            object-fit: contain;
        }

        .menu {
            padding: 12px;
            flex: 1;
            overflow-y: auto;
        }

        .menu-section {
            padding: 14px 16px 6px;
            color: #6b7280;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .menu a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            color: var(--muted);
            text-decoration: none;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.2s ease;
            margin-bottom: 4px;
        }

        .menu a:hover,
        .menu a.active {
            background: rgba(249, 115, 22, 0.08);
            color: var(--primary);
            padding-left: 20px;
        }

        .main {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .topbar {
            height: 64px;
            background: var(--panel);
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 24px;
        }

        .topbar-actions {
            align-items: center;
            display: flex;
            gap: 12px;
        }

        .system-date {
            align-items: center;
            background: var(--soft-bg);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--muted);
            display: flex;
            flex-direction: column;
            font-size: 11px;
            gap: 2px;
            line-height: 1.2;
            min-width: 132px;
            padding: 7px 10px;
            text-align: right;
        }

        .system-date strong {
            color: var(--text);
            font-size: 13px;
        }

        .operator-chip {
            align-items: center;
            background: var(--soft-bg);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--muted);
            display: flex;
            flex-direction: column;
            font-size: 11px;
            gap: 2px;
            line-height: 1.2;
            min-width: 132px;
            padding: 7px 10px;
            text-align: right;
        }

        .operator-chip strong {
            color: var(--text);
            font-size: 13px;
            max-width: 150px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .btn-audit {
            align-items: center;
            background: rgba(249, 115, 22, 0.1);
            border: 1px solid rgba(249, 115, 22, 0.32);
            border-radius: 8px;
            color: var(--primary);
            cursor: pointer;
            display: inline-flex;
            font-size: 13px;
            font-weight: 800;
            gap: 8px;
            min-height: 36px;
            padding: 0 12px;
            text-decoration: none;
        }

        .btn-audit:hover {
            background: rgba(249, 115, 22, 0.16);
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(5px);
            }

            to {
                opacity: 1;
            }
        }

        .content {
            padding: 24px;
            flex: 1;
            overflow-y: auto;
            animation: fadeIn 0.4s ease-out;
        }

        .btn-primary {
            background: var(--primary);
            color: #000;
            padding: 8px 16px;
            border-radius: 8px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: opacity 0.2s;
        }

        .btn-primary:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* Toast Notifications */
        #toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 10000;
        }

        .toast {
            background: var(--card);
            border-left: 4px solid var(--primary);
            padding: 16px;
            margin-bottom: 10px;
            border-radius: 4px;
            color: var(--text);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
        }

        .modal {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            backdrop-filter: blur(4px);
        }

        .hidden {
            display: none !important;
        }

        .modal-box {
            width: 400px;
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 24px;
        }

        input:not([type="checkbox"]):not([type="radio"]) {
            width: 100%;
            padding: 12px;
            margin-top: 6px;
            border-radius: 8px;
            border: 1px solid var(--border);
            background: var(--input-bg);
            color: var(--input-text);
        }

        input[type="checkbox"],
        input[type="radio"] {
            appearance: none;
            -webkit-appearance: none;
            align-items: center;
            background: var(--input-bg);
            border: 1px solid rgba(148, 163, 184, 0.45);
            cursor: pointer;
            display: inline-grid;
            flex: 0 0 auto;
            height: 18px;
            justify-content: center;
            margin: 0;
            padding: 0;
            position: relative;
            vertical-align: middle;
            width: 18px;
        }

        input[type="checkbox"] {
            border-radius: 5px;
        }

        input[type="radio"] {
            border-radius: 999px;
        }

        input[type="checkbox"]::after {
            border: solid #020617;
            border-width: 0 2px 2px 0;
            content: "";
            display: none;
            height: 9px;
            margin-top: -2px;
            transform: rotate(45deg);
            width: 5px;
        }

        input[type="radio"]::after {
            background: #020617;
            border-radius: 999px;
            content: "";
            display: none;
            height: 8px;
            width: 8px;
        }

        input[type="checkbox"]:checked,
        input[type="radio"]:checked {
            background: #38bdf8;
            border-color: #38bdf8;
            box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.16);
        }

        input[type="checkbox"]:checked::after,
        input[type="radio"]:checked::after {
            display: block;
        }

        input[type="checkbox"]:focus-visible,
        input[type="radio"]:focus-visible {
            outline: 2px solid rgba(56, 189, 248, 0.75);
            outline-offset: 2px;
        }

        label:has(> input[type="checkbox"]),
        label:has(> input[type="radio"]) {
            align-items: center;
            display: inline-flex;
            gap: 8px;
        }

        .btn-control {
            align-items: center;
            background: var(--soft-bg);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--text);
            cursor: pointer;
            display: inline-flex;
            font-size: 12px;
            font-weight: 800;
            gap: 6px;
            min-height: 36px;
            padding: 0 12px;
            white-space: nowrap;
        }

        .btn-control:hover {
            border-color: rgba(249, 115, 22, 0.38);
            color: var(--primary);
        }

        :root[data-theme="light"] .menu-section {
            color: #64748b;
        }

        :root[data-theme="light"] .menu a:hover,
        :root[data-theme="light"] .menu a.active {
            background: rgba(234, 88, 12, 0.1);
        }

        :root[data-theme="light"] .modal {
            background: rgba(15, 23, 42, 0.32);
        }

        :root[data-theme="light"] .btn-primary {
            color: #ffffff;
        }

        :root[data-theme="light"] .content :is(.card, .panel, .module-card, .settings-card, .report-card, .purchase-card, .summary-card, .stat-card, .table-wrap, .section-card, .modal-box) {
            background: var(--card) !important;
            border-color: var(--border) !important;
            color: var(--text) !important;
        }

        :root[data-theme="light"] .content :is(input, select, textarea) {
            background: var(--input-bg) !important;
            border-color: var(--border) !important;
            color: var(--input-text) !important;
        }

        :root[data-theme="light"] .content :is(table, th, td) {
            border-color: var(--border) !important;
        }


        :root[data-theme="light"] .content [style*="#020617"],
        :root[data-theme="light"] .content [style*="#0f172a"],
        :root[data-theme="light"] .content [style*="#111827"],
        :root[data-theme="light"] .content [style*="#070a12"],
        :root[data-theme="light"] .content [style*="rgb(2, 6, 23)"],
        :root[data-theme="light"] .content [style*="rgb(15, 23, 42)"] {
            background: var(--card) !important;
            border-color: var(--border) !important;
            color: var(--text) !important;
        }
        :root[data-theme="light"] .content :is(.text-muted, .muted, small) {
            color: var(--muted) !important;
        }

        @media (max-width: 768px) {
            .sidebar {
                position: absolute;
            }

            .system-date {
                display: none;
            }

            .operator-chip {
                display: none;
            }

            .btn-control {
                min-width: 38px;
                padding: 0 10px;
            }

            .btn-control span:last-child {
                display: none;
            }

            .btn-audit span:last-child {
                display: none;
            }

        }
    </style>

    
</head>

<body>

    <div id="toast-container"></div>

    <div class="app">
        @php
            $operatorRole = session('operator_role', 'cashier');
            $isSuperUser = \App\Services\OperatorPermissions::allows($operatorRole, 'security.manage');
            $canAudit = \App\Services\OperatorPermissions::allows($operatorRole, 'audit.view');
            $canReports = \App\Services\OperatorPermissions::allows($operatorRole, 'reports.view');
            $canCurrentAccount = \App\Services\OperatorPermissions::allows($operatorRole, 'current_account.manage');
            $canPurchases = \App\Services\OperatorPermissions::allowsAny($operatorRole, ['purchases.create', 'purchases.approve', 'purchases.receive']);
            $canCatalog = \App\Services\OperatorPermissions::allows($operatorRole, 'catalog.manage');
            $canShiftAudit = \App\Services\OperatorPermissions::allows($operatorRole, 'cash.audit');
            $activeModules = \App\Services\ModuleSettings::all();
            $sidebarLogoUrl = \App\Services\BusinessSettings::logoUrl() ?: asset('images/bg-pos.png');
        @endphp

        <aside class="sidebar" id="sidebar">
            <div class="logo">
                <img src="{{ $sidebarLogoUrl }}" alt="{{ config('app.name', 'Nkama ERP') }}">
            </div>
            <nav class="menu">
                <a class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">📊 Dashboard</a>

                <div class="menu-section">Operações</div>
                <a class="{{ request()->routeIs('admin.pos.*') ? 'active' : '' }}" href="{{ route('admin.pos.index') }}">⌗ POS / Caixa</a>
                <a class="{{ request()->routeIs('admin.sales.*') ? 'active' : '' }}" href="{{ route('admin.sales.index') }}">💰 Vendas</a>
                @if($canAudit && ($activeModules['audit'] ?? true))
                    <a class="{{ request()->routeIs('admin.audit.*') ? 'active' : '' }}" href="{{ route('admin.audit.index') }}">🛡 Auditoria</a>
                @endif
                @if($canReports)
                    <a class="{{ request()->routeIs('admin.reports.*') ? 'active' : '' }}" href="{{ route('admin.reports.index') }}">Relatórios</a>
                @endif
                @if($canCurrentAccount && ($activeModules['current_account'] ?? true))
                    <a class="{{ request()->routeIs('admin.current-accounts.*') ? 'active' : '' }}" href="{{ route('admin.current-accounts.index') }}">Conta Corrente</a>
                @endif
                @if($canPurchases && ($activeModules['purchases'] ?? true))
                    <a class="{{ request()->routeIs('admin.purchases.*') ? 'active' : '' }}" href="{{ route('admin.purchases.index') }}">Compras</a>
                @endif

                <div class="menu-section">Caixa</div>
                @if($canShiftAudit)
                    <a class="{{ request()->routeIs('admin.shifts.*') ? 'active' : '' }}" href="{{ route('admin.shifts.history') }}">🧾 Histórico de Fechos</a>
                @endif

                @if($canCatalog)
                    <div class="menu-section">Cadastros</div>
                    <a class="{{ request()->routeIs('admin.products.*') ? 'active' : '' }}" href="{{ route('admin.products.index') }}">📦 Produtos</a>
                    <a class="{{ request()->routeIs('admin.categories.*') ? 'active' : '' }}" href="{{ route('admin.categories.index') }}">🏷️ Categorias</a>
                    @if($activeModules['restaurant'] ?? true)
                        <a class="{{ request()->routeIs('admin.restaurantMesa.*') ? 'active' : '' }}" href="{{ route('admin.restaurantMesa.index') }}">🪑 Mesas</a>
                    @endif
                    <a class="{{ request()->routeIs('admin.customers.*') ? 'active' : '' }}" href="{{ route('admin.customers.index') }}">👥 Clientes</a>
                    <a class="{{ request()->routeIs('admin.suppliers.*') ? 'active' : '' }}" href="{{ route('admin.suppliers.index') }}">🚚 Fornecedores</a>
                @endif

                @if($isSuperUser)
                    <div class="menu-section">Segurança</div>
                    <a class="{{ request()->routeIs('admin.settings.*') ? 'active' : '' }}" href="{{ route('admin.settings.index') }}">Empresa & IVA</a>
                    <a class="{{ request()->routeIs('admin.document-settings.*') ? 'active' : '' }}" href="{{ route('admin.document-settings.index') }}">Documentos & Séries</a>
                    <a class="{{ request()->routeIs('admin.modules.*') ? 'active' : '' }}" href="{{ route('admin.modules.index') }}">🧩 Módulos</a>
                    <a class="{{ request()->routeIs('admin.operators.*') ? 'active' : '' }}" href="{{ route('admin.operators.index') }}">🔐 Operadores</a>
                @endif
            </nav>
        </aside>

        <main class="main">
            <header class="topbar">
                <div style="display:flex; align-items:center; gap:16px;">
                    <button class="btn-toggle" onclick="toggleSidebar()">☰</button>
                    <strong>@yield('page-title', 'Dashboard')</strong>
                </div>
                <div class="topbar-actions">
                    @php
                        $cachedSystemDate = \Illuminate\Support\Facades\Cache::get('system_date');
                        $systemDate = $cachedSystemDate
                            ? \Carbon\Carbon::parse($cachedSystemDate)
                            : now();
                    @endphp

                    <div class="system-date" title="Data operacional do sistema">
                        <span>Data do sistema</span>
                        <strong>{{ $systemDate->format('d/m/Y') }}</strong>
                    </div>

                    <div class="operator-chip" title="Operador autenticado">
                        <span>Operador</span>
                        <strong>{{ session('operator_name', 'Operador') }}</strong>
                    </div>

                    <button class="btn-control" type="button" onclick="toggleTheme()" title="Alternar modo claro/escuro">
                        <span id="theme-toggle-icon">☾</span>
                        <span id="theme-toggle-label">Tema</span>
                    </button>

                    @if($isSuperUser)
                        <form method="POST" action="{{ route('admin.system-date.next') }}">
                            @csrf
                            <button class="btn-audit" type="submit" title="Gerar auditoria diaria e avancar data do sistema">
                                <span>🛡</span>
                                <span>Auditoria</span>
                            </button>
                        </form>
                    @endif

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="btn-primary" type="submit">Sair</button>
                    </form>
                </div>
            </header>

            <section class="content">
                @yield('content')
            </section>
        </main>
    </div>

    <script>
        function currentTheme() {
            return document.documentElement.dataset.theme === 'light' ? 'light' : 'dark';
        }

        function setTheme(theme) {
            const normalized = theme === 'light' ? 'light' : 'dark';
            document.documentElement.dataset.theme = normalized;
            localStorage.setItem('nkama_theme', normalized);
            updateThemeButton();
        }

        function toggleTheme() {
            setTheme(currentTheme() === 'light' ? 'dark' : 'light');
        }

        function updateThemeButton() {
            const isLight = currentTheme() === 'light';
            const icon = document.getElementById('theme-toggle-icon');
            const label = document.getElementById('theme-toggle-label');

            if (icon) icon.textContent = isLight ? '☀' : '☾';
            if (label) label.textContent = isLight ? 'Claro' : 'Escuro';
        }

        function swalTheme() {
            const styles = getComputedStyle(document.documentElement);
            return {
                background: styles.getPropertyValue('--swal-bg').trim() || '#0f172a',
                color: styles.getPropertyValue('--swal-text').trim() || '#e2e8f0',
            };
        }

        window.nativeAlert = window.alert.bind(window);

        window.nkamaAlert = function(message, type = 'info', title = null) {
            if (!window.Swal) {
                window.nativeAlert(message);
                return Promise.resolve();
            }

            const titles = {
                success: 'Sucesso',
                error: 'Erro',
                warning: 'Atenção',
                info: 'Informação',
                question: 'Confirmar'
            };

            return Swal.fire({
                title: title || titles[type] || titles.info,
                text: message,
                icon: type,
                confirmButtonText: 'OK',
                confirmButtonColor: '#f97316',
                background: swalTheme().background,
                color: swalTheme().color,
                customClass: {
                    popup: 'nkama-swal-popup'
                }
            });
        };

        window.nkamaConfirm = function(message, title = 'Confirmar') {
            if (!window.Swal) {
                return Promise.resolve(window.confirm(message));
            }

            return Swal.fire({
                title,
                text: message,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sim',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#f97316',
                cancelButtonColor: '#334155',
                background: swalTheme().background,
                color: swalTheme().color,
            }).then(result => result.isConfirmed);
        };

        window.alert = function(message) {
            return window.nkamaAlert(String(message), 'info');
        };

        window.nkamaPrintTicket = function(url) {
            if (!url) return;

            return fetch(url, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
            })
                .then(async response => {
                    const data = await response.json().catch(() => ({}));

                    if (!response.ok || data.success === false) {
                        throw new Error(data.message || 'Nao foi possivel imprimir direto.');
                    }

                    if (data.message) {
                        showToast(data.message);
                    }

                    return data;
                })
                .catch(error => {
                    window.nkamaAlert(error.message, 'error', 'Impressao direta');
                });
        };

        document.addEventListener('click', function(event) {
            const trigger = event.target.closest('[data-direct-print-url]');
            if (!trigger) return;

            event.preventDefault();
            window.nkamaPrintTicket(trigger.dataset.directPrintUrl || trigger.href);
        });

        function showToast(msg) {
            if (window.Swal) {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: msg,
                    showConfirmButton: false,
                    timer: 2600,
                    timerProgressBar: true,
                    background: swalTheme().background,
                    color: swalTheme().color,
                });
                return;
            }

            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            toast.className = 'toast';
            toast.textContent = msg;
            container.appendChild(toast);
            setTimeout(() => toast.remove(), 3000);
        }

        document.addEventListener('DOMContentLoaded', function() {
            updateThemeButton();

            @if(session('success'))
                @if(session('daily_report_url'))
                    if (window.Swal) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Fecho diario gerado',
                            text: @json(session('success')),
                            confirmButtonText: 'Baixar relatorio',
                            showCancelButton: true,
                            cancelButtonText: 'Fechar',
                            background: swalTheme().background,
                            color: swalTheme().color,
                        }).then((result) => {
                            if (result.isConfirmed) {
                                window.open(@json(session('daily_report_url')), '_blank');
                            }
                        });
                    } else {
                        showToast(@json(session('success')));
                    }
                @else
                    showToast(@json(session('success')));
                @endif
            @endif

            @if($errors->any())
                window.nkamaAlert(@json($errors->first()), 'error');
            @endif

        });

        function toggleSidebar() {
            const sb = document.getElementById("sidebar");
            sb.classList.toggle("collapsed");
            localStorage.setItem("nkama_sidebar", sb.classList.contains("collapsed"));
        }
    </script>
</body>

</html>
