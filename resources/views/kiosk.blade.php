<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MARIA ERP LOGIN</title>

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <script>
        (function() {
            const storedTheme = localStorage.getItem('nkama_theme') || 'dark';
            document.documentElement.dataset.theme = storedTheme === 'light' ? 'light' : 'dark';
        })();
    </script>

    @php
        $loginBackgroundUrl = $loginBackgroundUrl ?? null;
    @endphp

    <style>
        :root {
            color-scheme: dark;
            --bg1: #030712;
            --bg2: #0b1528;
            --halo1: #1e293b;
            --halo2: #111827;
            --orange: #f97316;
            --orange-glow: rgba(249, 115, 22, 0.35);
            --text: #f3f4f6;
            --heading: #ffffff;
            --muted: #d1d5db;
            --subtle: #9ca3af;
            --glass: rgba(6, 10, 20, 0.78);
            --border: rgba(255, 255, 255, 0.12);
            --login-bg-start: rgba(3, 7, 18, 0.86);
            --login-bg-end: rgba(3, 7, 18, 0.38);
            --login-side-bg: rgba(3, 7, 18, 0.72);
            --login-shadow: -22px 0 60px rgba(0, 0, 0, 0.34);
            --pin-bg: rgba(0, 0, 0, 0.4);
            --key-bg: rgba(31, 41, 55, 0.5);
            --key-hover-bg: rgba(55, 65, 81, 0.68);
        }

        :root[data-theme="light"] {
            color-scheme: light;
            --bg1: #f8fafc;
            --bg2: #e8eef6;
            --halo1: rgba(234, 88, 12, 0.12);
            --halo2: rgba(14, 165, 233, 0.14);
            --orange: #ea580c;
            --orange-glow: rgba(234, 88, 12, 0.24);
            --text: #0f172a;
            --heading: #0f172a;
            --muted: #475569;
            --subtle: #64748b;
            --glass: rgba(255, 255, 255, 0.86);
            --border: rgba(15, 23, 42, 0.14);
            --login-bg-start: rgba(248, 250, 252, 0.92);
            --login-bg-end: rgba(248, 250, 252, 0.34);
            --login-side-bg: rgba(255, 255, 255, 0.74);
            --login-shadow: -22px 0 60px rgba(15, 23, 42, 0.14);
            --pin-bg: rgba(248, 250, 252, 0.88);
            --key-bg: rgba(255, 255, 255, 0.74);
            --key-hover-bg: rgba(241, 245, 249, 0.9);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
        }

        body {
            background:
                radial-gradient(circle at 15% 15%, var(--halo1) 0%, transparent 48%),
                radial-gradient(circle at 85% 85%, var(--halo2) 0%, transparent 46%),
                linear-gradient(135deg, var(--bg2), var(--bg1));
            color: var(--text);
            min-height: 100vh;
            min-height: 100svh;
            overflow-x: hidden;
        }

        body.has-login-bg {
            background:
                linear-gradient(90deg, var(--login-bg-start), var(--login-bg-end)),
                url('{{ $loginBackgroundUrl }}') center / cover no-repeat;
        }

        .login-shell {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(380px, 460px);
            min-height: 620px;
            min-height: 100vh;
            min-height: 100svh;
            width: 100%;
        }

        .brand-side {
            display: flex;
            flex-direction: column;
            gap: 32px;
            justify-content: space-between;
            min-width: 0;
            padding: 48px;
        }

        .brand-mark {
            color: var(--muted);
            font-size: 13px;
            font-weight: 900;
            letter-spacing: 0.18em;
            text-transform: uppercase;
        }

        .brand-top {
            align-items: center;
            display: flex;
            gap: 14px;
            justify-content: space-between;
        }

        .kiosk-actions {
            display: flex;
            gap: 10px;
        }

        .kiosk-action-btn {
            background: var(--glass);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--text);
            cursor: pointer;
            font-size: 12px;
            font-weight: 900;
            min-height: 36px;
            padding: 0 12px;
            text-transform: uppercase;
        }

        .kiosk-action-btn:hover {
            border-color: var(--orange);
            color: var(--orange);
        }

        .brand-copy {
            max-width: 620px;
        }

        .brand-copy h1 {
            color: var(--heading);
            font-size: 54px;
            font-weight: 900;
            letter-spacing: 0;
            line-height: 1.02;
            margin-bottom: 18px;
            text-transform: uppercase;
        }

        .brand-copy p {
            color: var(--muted);
            font-size: 17px;
            font-weight: 700;
            line-height: 1.55;
            max-width: 520px;
        }

        .brand-footer {
            color: var(--subtle);
            font-size: 12px;
            font-weight: 800;
        }

        .login-side {
            align-items: center;
            background: var(--login-side-bg);
            border-left: 1px solid var(--border);
            box-shadow: var(--login-shadow);
            display: flex;
            justify-content: center;
            min-width: 0;
            padding: 34px;
        }

        .box {
            background: var(--glass);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid var(--border);
            border-radius: 8px;
            box-shadow:
                0 4px 30px rgba(0, 0, 0, 0.4),
                0 30px 60px rgba(0, 0, 0, 0.6),
                inset 0 1px 1px rgba(255, 255, 255, 0.1);
            padding: 30px;
            width: min(100%, 390px);
        }

        .title {
            color: var(--orange);
            font-size: 20px;
            font-weight: 900;
            letter-spacing: 0;
            text-align: left;
            text-shadow: 0 0 15px var(--orange-glow);
        }

        .subtitle {
            color: var(--subtle);
            font-size: 13px;
            font-weight: 700;
            margin: 6px 0 22px;
            text-align: left;
        }

        .pin-box {
            background: var(--pin-bg);
            border: 1px solid var(--border);
            border-radius: 8px;
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.5);
            margin-bottom: 24px;
            padding: 16px;
            text-align: center;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .pin-box.active {
            border-color: var(--orange);
            box-shadow:
                0 0 20px rgba(249, 115, 22, 0.2),
                inset 0 2px 4px rgba(0, 0, 0, 0.5);
        }

        .pin {
            align-items: center;
            color: var(--orange);
            display: flex;
            font-size: 28px;
            font-weight: 900;
            justify-content: center;
            letter-spacing: 14px;
            min-height: 38px;
            text-shadow: 0 0 10px var(--orange-glow);
        }

        .grid {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(3, 1fr);
        }

        .btn {
            align-items: center;
            background: var(--key-bg);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--text);
            cursor: pointer;
            display: flex;
            font-size: 22px;
            font-weight: 800;
            justify-content: center;
            min-height: 66px;
            padding: 18px;
            transition: all 0.15s cubic-bezier(0.4, 0, 0.2, 1);
            user-select: none;
        }

        .btn:hover {
            background: var(--key-hover-bg);
            border-color: var(--border);
            transform: translateY(-1px);
        }

        .btn:active {
            background: rgba(55, 65, 81, 0.8);
            transform: translateY(1px) scale(0.97);
        }

        .ok {
            background: var(--orange);
            border: none;
            box-shadow: 0 4px 15px var(--orange-glow);
            color: #030712;
            font-weight: 900;
        }

        .ok:hover {
            background: #f97316;
            box-shadow: 0 6px 20px rgba(249, 115, 22, 0.5);
            filter: brightness(1.1);
        }

        .clear {
            background: rgba(239, 68, 68, 0.1);
            border-color: rgba(239, 68, 68, 0.2);
            color: #ef4444;
        }

        .clear:hover {
            background: rgba(239, 68, 68, 0.2);
            border-color: rgba(239, 68, 68, 0.4);
        }

        .status {
            font-size: 13px;
            font-weight: 700;
            margin-top: 18px;
            min-height: 20px;
            text-align: center;
        }

        .forgot-link {
            color: #f97316;
            display: block;
            font-size: 13px;
            font-weight: 800;
            margin-top: 14px;
            text-align: center;
            text-decoration: none;
        }

        .forgot-link:hover {
            text-decoration: underline;
        }

        .shake {
            animation: shake 0.4s ease-in-out;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            20%, 60% { transform: translateX(-6px); }
            40%, 80% { transform: translateX(6px); }
        }

        @media (max-width: 920px) {
            .login-shell {
                grid-template-columns: 1fr;
                min-height: 100vh;
                min-height: 100svh;
            }

            .brand-side {
                min-height: 240px;
                padding: 28px;
            }

            .brand-copy h1 {
                font-size: 34px;
            }

            .brand-copy p {
                font-size: 14px;
            }

            .login-side {
                border-left: none;
                border-top: 1px solid var(--border);
                box-shadow: none;
                padding: 22px;
            }
        }

        @media (max-width: 560px) {
            .login-shell {
                display: flex;
                flex-direction: column;
            }

            .brand-side {
                gap: 24px;
                min-height: auto;
                padding: 22px 18px;
            }

            .brand-top {
                gap: 12px;
            }

            .brand-copy h1 {
                font-size: 28px;
                line-height: 1.08;
                margin-bottom: 10px;
            }

            .brand-copy p {
                font-size: 13px;
                line-height: 1.45;
            }

            .brand-footer {
                display: none;
            }

            .login-side {
                align-items: flex-start;
                flex: 1;
                padding: 18px;
            }

            .box {
                padding: 22px;
                width: 100%;
            }

            .pin-box {
                margin-bottom: 18px;
                padding: 12px;
            }

            .pin {
                font-size: 24px;
                letter-spacing: 10px;
                min-height: 34px;
            }

            .grid {
                gap: 10px;
            }

            .btn {
                font-size: 20px;
                min-height: 58px;
                padding: 14px;
            }
        }

        @media (max-width: 360px) {
            .brand-side {
                padding: 18px 14px;
            }

            .brand-mark,
            .kiosk-action-btn {
                font-size: 11px;
            }

            .kiosk-action-btn {
                min-height: 34px;
                padding: 0 10px;
            }

            .brand-copy h1 {
                font-size: 24px;
            }

            .box {
                padding: 18px;
            }

            .pin {
                font-size: 22px;
                letter-spacing: 7px;
            }

            .btn {
                min-height: 52px;
                padding: 12px;
            }
        }

        @media (max-height: 640px) and (min-width: 921px) {
            .login-shell {
                min-height: 100vh;
                min-height: 100svh;
            }

            .brand-side {
                padding: 30px;
            }

            .brand-copy h1 {
                font-size: 42px;
            }

            .login-side {
                padding: 22px;
            }

            .box {
                padding: 24px;
            }

            .btn {
                min-height: 56px;
                padding: 14px;
            }
        }    </style>
</head>

<body class="{{ $loginBackgroundUrl ? 'has-login-bg' : '' }}">
    <div class="login-shell">
        <section class="brand-side">
            <div class="brand-top">
                <div class="brand-mark">MARIA ERP</div>
                <div class="kiosk-actions">
                    <button type="button" class="kiosk-action-btn" onclick="toggleKioskTheme()" id="kiosk-theme-btn">Tema</button>
                </div>
            </div>
            <div class="brand-copy">
                <h1>Terminal de venda</h1>
                <p>Acesso seguro para operadores, com caixa, vendas e restaurante prontos para o turno.</p>
            </div>
            <div class="brand-footer">v1.0 kiosk mode</div>
        </section>

        <section class="login-side">
            <div class="box">
                <div class="title">POS Login</div>
                <div class="subtitle">Digite o PIN de 8 digitos para iniciar.</div>

                <div id="pinBox" class="pin-box">
                    <div id="pinView" class="pin">********</div>
                </div>

                <div class="grid">
                    <button class="btn" onclick="add(1)">1</button>
                    <button class="btn" onclick="add(2)">2</button>
                    <button class="btn" onclick="add(3)">3</button>

                    <button class="btn" onclick="add(4)">4</button>
                    <button class="btn" onclick="add(5)">5</button>
                    <button class="btn" onclick="add(6)">6</button>

                    <button class="btn" onclick="add(7)">7</button>
                    <button class="btn" onclick="add(8)">8</button>
                    <button class="btn" onclick="add(9)">9</button>

                    <button class="btn clear" onclick="clearPin()">C</button>
                    <button class="btn" onclick="add(0)">0</button>
                    <button class="btn ok" onclick="login()">OK</button>
                </div>

                <div id="status" class="status"></div>

                @if(session('status'))
                    <div class="status" style="color: #10b981;">{{ session('status') }}</div>
                @endif

                <a class="forgot-link" href="{{ route('operator.password.request') }}">Esqueci o PIN</a>
            </div>
        </section>
    </div>

    <script src="{{ asset('js/kiosk.js') }}"></script>
</body>

</html>
