<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NKAMA POS LOGIN</title>

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <style>
        :root {
            --bg1: #030712;
            --bg2: #0b1528;
            --orange: #f97316;
            --orange-glow: rgba(249, 115, 22, 0.35);
            --text: #f3f4f6;
            --glass: rgba(17, 24, 39, 0.7);
            --border: rgba(255, 255, 255, 0.08);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
        }

        body {
            height: 100vh;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            background:
                radial-gradient(circle at 15% 15%, #1e293b 0%, transparent 50%),
                radial-gradient(circle at 85% 85%, #0f172a 0%, transparent 50%),
                linear-gradient(135deg, var(--bg2), var(--bg1));
            color: var(--text);
        }

        /* BACKGROUND BRAND TEXT */
        .bg-brand {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 8vw;
            font-weight: 900;
            letter-spacing: 15px;
            text-transform: uppercase;
            color: rgba(255, 255, 255, 0.02);
            text-align: center;
            user-select: none;
            line-height: 1.1;
            pointer-events: none;
        }

        /* VERSION */
        .version {
            position: absolute;
            bottom: 20px;
            right: 24px;
            font-size: 11px;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: rgba(255, 255, 255, 0.3);
        }

        /* LOGIN BOX */
        .box {
            width: 400px;
            padding: 32px;
            border-radius: 28px;
            background: var(--glass);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid var(--border);
            box-shadow: 
                0 4px 30px rgba(0, 0, 0, 0.4),
                0 30px 60px rgba(0, 0, 0, 0.6),
                inset 0 1px 1px rgba(255, 255, 255, 0.1);
            z-index: 2;
        }

        /* TITLE */
        .title {
            text-align: center;
            font-size: 22px;
            font-weight: 800;
            letter-spacing: 0.5px;
            color: var(--orange);
            text-shadow: 0 0 15px var(--orange-glow);
            margin-bottom: 4px;
        }

        .subtitle {
            text-align: center;
            font-size: 13px;
            color: #9ca3af;
            margin-bottom: 24px;
        }

        /* PIN DISPLAY BOX */
        .pin-box {
            background: rgba(0, 0, 0, 0.4);
            border: 1px solid var(--border);
            padding: 16px;
            border-radius: 18px;
            text-align: center;
            margin-bottom: 24px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.5);
        }

        .pin-box.active {
            border-color: var(--orange);
            box-shadow: 
                0 0 20px rgba(249, 115, 22, 0.2),
                inset 0 2px 4px rgba(0, 0, 0, 0.5);
        }

        .pin {
            font-size: 28px;
            letter-spacing: 14px;
            color: var(--orange);
            text-shadow: 0 0 10px var(--orange-glow);
            min-height: 38px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }

        /* KEYPAD GRID */
        .grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
        }

        .btn {
            padding: 20px;
            font-size: 22px;
            font-weight: 600;
            border-radius: 16px;
            border: 1px solid var(--border);
            background: rgba(31, 41, 55, 0.5);
            color: white;
            cursor: pointer;
            transition: all 0.15s cubic-bezier(0.4, 0, 0.2, 1);
            user-select: none;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn:hover {
            background: rgba(55, 65, 81, 0.6);
            border-color: rgba(255, 255, 255, 0.2);
            transform: translateY(-1px);
        }

        .btn:active {
            transform: translateY(1px) scale(0.97);
            background: rgba(55, 65, 81, 0.8);
        }

        /* SPECIAL BUTTONS */
        .ok {
            background: var(--orange);
            color: #030712;
            font-weight: 800;
            border: none;
            box-shadow: 0 4px 15px var(--orange-glow);
        }

        .ok:hover {
            background: #f97316;
            filter: brightness(1.1);
            box-shadow: 0 6px 20px rgba(249, 115, 22, 0.5);
        }

        .ok:active {
            background: #ea580c;
            box-shadow: 0 2px 10px rgba(249, 115, 22, 0.4);
        }

        .clear {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
            border-color: rgba(239, 68, 68, 0.2);
        }

        .clear:hover {
            background: rgba(239, 68, 68, 0.2);
            border-color: rgba(239, 68, 68, 0.4);
        }

        /* STATUS MESSAGE */
        .status {
            text-align: center;
            font-size: 13px;
            margin-top: 18px;
            min-height: 20px;
            font-weight: 500;
        }

        .forgot-link {
            color: #f97316;
            display: block;
            font-size: 13px;
            font-weight: 700;
            margin-top: 14px;
            text-align: center;
            text-decoration: none;
        }

        .forgot-link:hover {
            text-decoration: underline;
        }

        /* SHAKE ANIMATION ON ERROR */
        .shake {
            animation: shake 0.4s ease-in-out;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            20%, 60% { transform: translateX(-6px); }
            40%, 80% { transform: translateX(6px); }
        }
    </style>
</head>

<body>

    <div class="bg-brand">
        NKAMA POS<br>ENTERPRISE ERP
    </div>

    <div class="version">v1.0 • kiosk mode</div>

    <div class="box">

        <div class="title">🔐 POS LOGIN</div>
        <div class="subtitle">Terminal seguro de operador</div>

        <div id="pinBox" class="pin-box">
            <div id="pinView" class="pin">••••••••</div>
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

    <script src="{{ asset('js/kiosk.js') }}"></script>

</body>

</html>
