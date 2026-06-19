<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NKAMA POS LOGIN</title>

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <style>
        :root {
            --bg1: #050814;
            --bg2: #0a0f1c;
            --orange: #f97316;
            --text: #e5e7eb;
        }

        * {
            box-sizing: border-box;
            font-family: system-ui;
        }

        body {
            margin: 0;
            height: 100vh;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            background:
                radial-gradient(circle at 20% 20%, #1f2937 0%, transparent 40%),
                radial-gradient(circle at 80% 80%, #0f172a 0%, transparent 40%),
                linear-gradient(180deg, var(--bg2), var(--bg1));
            color: var(--text);
            
        }

        /* BACKGROUND */
        .bg-brand {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 72px;
            font-weight: 900;
            letter-spacing: 10px;
            text-transform: uppercase;
            color: rgba(255, 255, 255, 0.03);
            text-align: center;
            user-select: none;
        }

        /* VERSION */
        .version {
            position: absolute;
            bottom: 16px;
            right: 20px;
            font-size: 11px;
            color: rgba(255, 255, 255, 0.25);
        }

        /* LOGIN BOX */
        .box {
            width: 420px;
            padding: 28px;
            border-radius: 22px;
            background: rgba(15, 23, 42, 0.75);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.7);
            z-index: 2;
        }

        /* TITLE */
        .title {
            text-align: center;
            font-size: 20px;
            font-weight: 800;
            color: var(--orange);
        }

        .subtitle {
            text-align: center;
            font-size: 12px;
            opacity: 0.6;
            margin-bottom: 18px;
        }

        /* PIN */
        .pin-box {
            background: rgba(0, 0, 0, 0.25);
            border: 1px solid rgba(255, 255, 255, 0.08);
            padding: 18px;
            border-radius: 16px;
            text-align: center;
            margin-bottom: 18px;
            transition: 0.2s;
        }

        .pin-box.active {
            border-color: var(--orange);
            box-shadow: 0 0 0 2px rgba(249, 115, 22, 0.15);
        }

        .pin {
            font-size: 34px;
            letter-spacing: 12px;
            color: var(--orange);
            min-height: 40px;
        }

        /* KEYPAD */
        .grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
        }

        .btn {
            padding: 18px;
            font-size: 18px;
            border-radius: 14px;
            border: 1px solid rgba(255, 255, 255, 0.08);
            background: rgba(17, 24, 39, 0.8);
            color: white;
            cursor: pointer;
            transition: 0.15s;
            user-select: none;
        }

        .btn:active {
            transform: scale(0.95);
        }

        .btn:hover {
            border-color: var(--orange);
        }

        .ok {
            background: var(--orange);
            color: black;
            font-weight: 800;
        }

        .clear {
            background: rgba(239, 68, 68, 0.15);
        }

        /* STATUS */
        .status {
            text-align: center;
            font-size: 12px;
            margin-top: 14px;
            opacity: 0.7;
        }

        /* SHAKE ERROR */
        .shake {
            animation: shake 0.4s;
        }

        @keyframes shake {
            0% {
                transform: translateX(0)
            }

            25% {
                transform: translateX(-5px)
            }

            50% {
                transform: translateX(5px)
            }

            100% {
                transform: translateX(0)
            }
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

    </div>

    <script src="{{ asset('js/kiosk.js') }}"></script>

</body>


</html>
