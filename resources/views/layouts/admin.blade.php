<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'Nkama ERP') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <meta name="csrf-token" content="{{ csrf_token() }}">



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

        .menu a:hover {
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
        }
    </style>
</head>

<body>

    <div id="toast-container"></div>

    <div class="app">
        <aside class="sidebar" id="sidebar">
            <div class="logo">NKAMA ERP</div>
            <nav class="menu">
                <a href="{{ route('admin.dashboard') }}">📊 Dashboard</a>
                <a href="#" onclick="goToPOS(event)">⌗ POS / Caixa</a>
                <a href="{{ route('admin.restaurantMesa.index') }}">🍽️ Gerir Mesas</a>
                <a href="{{ route('admin.sales.index') }}">💰 Vendas</a>
                <a href="{{ route('admin.products.index') }}">📦 Inventário</a>
            </nav>
        </aside>

        <main class="main">
            <header class="topbar">
                <div style="display:flex; align-items:center; gap:16px;">
                    <button class="btn-toggle" onclick="toggleSidebar()">☰</button>
                    <strong>@yield('page-title', 'Dashboard')</strong>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="btn-primary" type="submit">Sair</button>
                </form>
            </header>

            <section class="content">
                @yield('content')
            </section>
        </main>
    </div>

    <div id="cashModal" class="modal hidden">
        <div class="modal-box">
            <h3 style="color:var(--primary); margin-top:0;">ABRIR CAIXA</h3>
            <label>Valor Inicial</label>
            <input id="openingCash" type="number" step="0.01" placeholder="0.00">
            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:20px;">
                <button class="btn-toggle" onclick="closeCash()">Cancelar</button>
                <button id="btnOpenCash" class="btn-primary" onclick="openCashSubmit()">Confirmar</button>
            </div>
        </div>
    </div>

    <script>
        function showToast(msg) {
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

        async function goToPOS(e) {
            e.preventDefault();
            const res = await fetch("{{ route('admin.shift.current') }}");
            const data = await res.json();
            if (data?.open) window.location.href = "{{ route('admin.pos.index') }}";
            else document.getElementById("cashModal").classList.remove("hidden");
        }

        function closeCash() {
            document.getElementById("cashModal").classList.add("hidden");
        }

        async function openCashSubmit() {
            const btn = document.getElementById('btnOpenCash');
            btn.disabled = true;

            const res = await fetch("{{ route('admin.shift.open') }}", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": "{{ csrf_token() }}"
                },
                body: JSON.stringify({
                    opening_cash: document.getElementById("openingCash").value
                })
            });

            const data = await res.json();
            if (data.success) {
                window.location.href = "{{ route('admin.pos.index') }}";
            } else {
                showToast(data.message || "Erro ao abrir caixa");
                btn.disabled = false;
            }
        }
    </script>
</body>

</html>
