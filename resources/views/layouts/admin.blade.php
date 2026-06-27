<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'Nkama ERP') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>



    <style>
        :root {
            --bg: #0b0f19;
            --panel: rgba(17, 24, 39, 0.95);
            --card: #111827;
            --border: rgba(255, 255, 255, 0.06);
            --text: #e5e7eb;
            --muted: #9ca3af;
            --primary: #f97316;
        }

        body {
            margin: 0;
            font-family: 'Inter', system-ui, sans-serif;
            background: radial-gradient(circle at top right, #1a1f2e, #0b0f19);
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
            padding: 24px 20px;
            font-weight: 800;
            font-size: 18px;
            color: var(--primary);
            border-bottom: 1px solid var(--border);
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
            background: rgba(255, 255, 255, 0.04);
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
            background: rgba(255, 255, 255, 0.04);
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
            display: inline-flex;
            font-size: 13px;
            font-weight: 800;
            gap: 8px;
            min-height: 36px;
            padding: 0 12px;
            text-decoration: none;
            cursor: pointer;
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
            background: #1f2937;
            border-left: 4px solid var(--primary);
            padding: 16px;
            margin-bottom: 10px;
            border-radius: 4px;
            color: white;
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

        input {
            width: 100%;
            padding: 12px;
            margin-top: 6px;
            border-radius: 8px;
            border: 1px solid var(--border);
            background: #070a12;
            color: white;
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
            $canManage = in_array($operatorRole, ['super_user', 'admin', 'manager'], true);
            $isSuperUser = $operatorRole === 'super_user';
            $activeModules = \App\Services\ModuleSettings::all();
        @endphp

        <aside class="sidebar" id="sidebar">
            <div class="logo">NKAMA ERP</div>
            <nav class="menu">
                <a class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">📊 Dashboard</a>

                <div class="menu-section">Operações</div>
                <a class="{{ request()->routeIs('admin.pos.*') ? 'active' : '' }}" href="{{ route('admin.pos.index') }}">⌗ POS / Caixa</a>
                <a class="{{ request()->routeIs('admin.sales.*') ? 'active' : '' }}" href="{{ route('admin.sales.index') }}">💰 Vendas</a>
                @if($canManage)
                    <a class="{{ request()->routeIs('admin.audit.*') ? 'active' : '' }}" href="{{ route('admin.audit.index') }}">🛡 Auditoria</a>
                    <a class="{{ request()->routeIs('admin.current-accounts.*') ? 'active' : '' }}" href="{{ route('admin.current-accounts.index') }}">Conta Corrente</a>
                @endif

                <div class="menu-section">Caixa</div>
                @if($canManage)
                    <a class="{{ request()->routeIs('admin.shifts.*') ? 'active' : '' }}" href="{{ route('admin.shifts.history') }}">🧾 Histórico de Fechos</a>
                @endif

                @if($canManage)
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

                    @if($isSuperUser)
                        <form method="POST" action="{{ route('admin.system-date.next') }}">
                            @csrf
                            <button class="btn-audit" type="submit" title="Avançar data do sistema para o próximo dia">
                                <span>🛡</span>
                                <span>Próxima data</span>
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
                background: '#0f172a',
                color: '#e2e8f0',
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
                background: '#0f172a',
                color: '#e2e8f0',
            }).then(result => result.isConfirmed);
        };

        window.alert = function(message) {
            return window.nkamaAlert(String(message), 'info');
        };

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
                    background: '#0f172a',
                    color: '#e2e8f0',
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

        function toggleSidebar() {
            const sb = document.getElementById("sidebar");
            sb.classList.toggle("collapsed");
            localStorage.setItem("nkama_sidebar", sb.classList.contains("collapsed"));
        }
    </script>
</body>

</html>
