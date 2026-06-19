<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'Nkama ERP') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <style>
        /* 1. VARIÁVEIS E GLOBAIS */
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
            font-family: system-ui, -apple-system, sans-serif;
            background: var(--bg);
            color: var(--text);
            -webkit-font-smoothing: antialiased;
            overflow: hidden; /* Remove rolagem global da janela */
        }

        /* 2. ESTRUTURA E LAYOUT PRINCIPAL */
        .app {
            display: flex;
            height: 100vh;
            width: 100vw;
            overflow: hidden;
        }

        .main {
            flex: 1;
            display: flex;
            flex-direction: column;
            height: 100%;
            overflow: hidden; /* Garante que a estrutura externa não role */
        }

        /* 3. BARRA LATERAL (SIDEBAR) */
        .sidebar {
            width: 260px;
            min-width: 260px;
            background: var(--panel);
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            backdrop-filter: blur(10px);
            transition: width 0.25s ease, min-width 0.25s ease;
            overflow: hidden;
            white-space: nowrap;
            height: 100%;
        }

        .sidebar.collapsed {
            width: 0;
            min-width: 0;
            border-right: none;
        }

        .logo {
            padding: 24px 20px;
            font-weight: 700;
            font-size: 18px;
            letter-spacing: 0.05em;
            color: var(--primary);
            border-bottom: 1px solid var(--border);
            flex-shrink: 0;
        }

        .menu {
            padding: 12px;
            overflow-y: auto; /* Permite rolar os links se a tela for muito baixa */
            flex: 1;
        }

        .menu a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 16px;
            color: var(--muted);
            text-decoration: none;
            cursor: pointer;
            border-radius: 8px;
            font-weight: 500;
            font-size: 14px;
            transition: all 0.2s ease-in-out;
            margin-bottom: 4px;
        }

        .menu a:hover {
            background: rgba(249, 115, 22, 0.08);
            color: var(--primary);
            padding-left: 20px;
        }

        /* 4. COMPONENTES DO TOPO (TOPBAR) */
        .topbar {
            height: 64px;
            background: var(--panel);
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 24px;
            backdrop-filter: blur(10px);
            flex-shrink: 0;
        }

        .topbar-left {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        /* 5. ÁREA DE CONTEÚDO DINÂMICO */
        .content {
            padding: 16px;
            flex: 1;
            overflow-y: auto; /* A barra vertical de rolagem aparecerá estritamente aqui se necessário */
            box-sizing: border-box;
        }

        /* 6. BOTÕES & INTERAÇÕES */
        .btn {
            padding: 8px 16px;
            border-radius: 8px;
            border: 1px solid var(--border);
            background: rgba(255, 255, 255, 0.03);
            color: var(--text);
            font-weight: 500;
            font-size: 14px;
            cursor: pointer;
            transition: background 0.2s;
        }

        .btn:hover {
            background: rgba(255, 255, 255, 0.08);
        }

        .btn-primary {
            background: var(--primary);
            color: #000;
            padding: 8px 16px;
            border-radius: 8px;
            border: none;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: opacity 0.2s;
        }

        .btn-primary:hover {
            opacity: 0.9;
        }

        .btn-toggle {
            width: 38px;
            height: 38px;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid var(--border);
            color: var(--text);
            font-size: 18px;
            cursor: pointer;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }

        .btn-toggle:hover {
            background: rgba(249, 115, 22, 0.12);
            color: var(--primary);
        }

        /* 7. MODAL OVERLAYS */
        .modal {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.8);
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
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }

        .modal-box label {
            font-size: 12px;
            font-weight: 600;
            color: var(--muted);
            text-transform: uppercase;
            display: block;
            margin-top: 16px;
        }

        input {
            width: 100%;
            box-sizing: border-box;
            padding: 12px;
            margin-top: 6px;
            border-radius: 8px;
            border: 1px solid var(--border);
            background: #070a12;
            color: var(--text);
            font-size: 15px;
        }

        input:focus {
            outline: none;
            border-color: var(--primary);
        }

        /* 8. SCROLLBARS CUSTOMIZADAS (Opcional - Estilo Premium Mac/Moderno) */
        .content::-webkit-scrollbar, .menu::-webkit-scrollbar {
            width: 6px;
        }
        .content::-webkit-scrollbar-track, .menu::-webkit-scrollbar-track {
            background: transparent;
        }
        .content::-webkit-scrollbar-thumb, .menu::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.08);
            border-radius: 3px;
        }
        .content::-webkit-scrollbar-thumb:hover, .menu::-webkit-scrollbar-thumb:hover {
            background: var(--primary);
        }
    </style>
</head>

<body>

    <div class="app">

        <div class="sidebar" id="sidebar">
            <div class="logo">
                {{ config('app.name', 'Nkama ERP') }}
            </div>
            <div class="menu">
                <a href="{{ route('admin.dashboard') }}">📊 Dashboard</a>
                <a onclick="goToPOS(event)">⌗ POS / Caixa</a>
                <a href="{{ route('admin.restaurant.index') }}">🍽️ Gerir Mesas</a>
                <a href="{{ route('admin.shifts.history') }}">📜 Histórico de Caixas</a>
                <a href="{{ route('admin.sales.index') }}">💰 Vendas & Faturação</a>
                <a href="{{ route('admin.products.index') }}">📦 Inventário / Stock</a>
                <a href="{{ route('admin.suppliers.index') }}">🚚 Fornecedores</a>
                <a href="{{ route('admin.customers.index') }}">👥 Clientes</a>
            </div>
        </div>

        <div class="main">

            <div class="topbar">
                <div class="topbar-left">
                    <button class="btn-toggle" onclick="toggleSidebar()">☰</button>
                    <strong style="font-size: 18px; font-weight: 600;">@yield('page-title', '')</strong>
                </div>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="btn-primary" type="submit">Sair</button>
                </form>
            </div>

            <div class="content">
                @yield('content')
            </div>

        </div>

    </div>

    <div id="cashModal" class="modal hidden">
        <div class="modal-box">
            <h3 style="color:var(--primary); margin-top: 0; font-size: 20px;">ABRIR CAIXA</h3>
            <p style="color:var(--muted); margin-bottom: 0; font-size: 14px;">Nenhum caixa ativo encontrado para o operador.</p>

            <label for="openingCash">Valor Inicial em Caixa</label>
            <input id="openingCash" type="number" step="0.01" placeholder="0.00">

            <div style="display:flex; justify-content: flex-end; gap:10px; margin-top:24px;">
                <button class="btn" onclick="closeCash()">Cancelar</button>
                <button class="btn-primary" onclick="openCashSubmit()">Abrir e continuar</button>
            </div>
        </div>
    </div>

    <script>
        // Configurações de endpoints injetados do Laravel
        const POS_URL = "{{ route('admin.pos.index') }}";
        const CURRENT_SHIFT_URL = "{{ route('admin.shift.current') }}";
        const OPEN_SHIFT_URL = "{{ route('admin.shift.open') }}";

        /* Controle do Estado da Sidebar (Recolher/Expandir) */
        function toggleSidebar() {
            const sidebar = document.getElementById("sidebar");
            sidebar.classList.toggle("collapsed");
            localStorage.setItem("nkama_sidebar", sidebar.classList.contains("collapsed"));
        }

        // Restaura preferência de visualização do utilizador ao carregar o DOM
        document.addEventListener("DOMContentLoaded", () => {
            const sidebar = document.getElementById("sidebar");
            if (localStorage.getItem("nkama_sidebar") === "true") {
                sidebar.classList.add("collapsed");
            }
        });

        /* Validações reativas e Operações Ajax do Caixa */
        async function goToPOS(event) {
            if (event) event.preventDefault();

            try {
                const res = await fetch(CURRENT_SHIFT_URL, {
                    headers: { "Accept": "application/json" }
                });
                const data = await res.json();

                if (data?.open) {
                    window.location.href = POS_URL;
                    return;
                }
                openCash();
            } catch (e) {
                console.error("Erro ao validar caixa atual:", e);
                openCash();
            }
        }

        function openCash() {
            document.getElementById("cashModal").classList.remove("hidden");
        }

        function closeCash() {
            document.getElementById("cashModal").classList.add("hidden");
        }

        async function openCashSubmit() {
            const opening_cash = parseFloat(document.getElementById("openingCash").value || 0);
            const token = document.querySelector('meta[name="csrf-token"]').content;

            try {
                const res = await fetch(OPEN_SHIFT_URL, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": token,
                        "Accept": "application/json"
                    },
                    body: JSON.stringify({ opening_cash })
                });

                const data = await res.json();

                if (!data.success) {
                    alert(data.message || "Erro ao abrir o caixa.");
                    return;
                }

                closeCash();
                window.location.href = POS_URL;
            } catch (e) {
                console.error("Erro ao processar abertura de caixa:", e);
                alert("Erro crítico de ligação ao servidor.");
            }
        }
    </script>
</body>

</html>