<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar acesso - MARIA ERP</title>
    <style>
        body { align-items: center; background: #030712; color: #e5e7eb; display: flex; font-family: system-ui, sans-serif; justify-content: center; min-height: 100vh; margin: 0; }
        .box { background: rgba(17, 24, 39, .92); border: 1px solid rgba(255,255,255,.08); border-radius: 12px; max-width: 420px; padding: 24px; width: 100%; }
        h1 { color: #f97316; font-size: 22px; margin: 0 0 8px; }
        p { color: #9ca3af; font-size: 14px; line-height: 1.5; }
        label { color: #9ca3af; display: block; font-size: 12px; font-weight: 800; margin: 16px 0 6px; text-transform: uppercase; }
        input { background: #070a12; border: 1px solid rgba(255,255,255,.1); border-radius: 8px; color: #fff; box-sizing: border-box; padding: 12px; width: 100%; }
        button, a { border-radius: 8px; display: inline-flex; font-weight: 800; margin-top: 14px; padding: 10px 14px; text-decoration: none; }
        button { background: #f97316; border: 0; color: #111827; cursor: pointer; }
        a { color: #cbd5e1; }
        .status { background: rgba(16,185,129,.12); border: 1px solid rgba(16,185,129,.24); border-radius: 8px; color: #86efac; padding: 10px; }
        .error { color: #fecaca; font-size: 13px; margin-top: 8px; }
    </style>
</head>
<body>
    <div class="box">
        <h1>Recuperar acesso</h1>
        <p>Informe o email do operador, o código de recuperação offline e defina um novo PIN.</p>

        @if(session('status'))
            <div class="status">{{ session('status') }}</div>
        @endif

        <form method="POST" action="{{ route('operator.password.update') }}">
            @csrf
            <label>Email do operador</label>
            <input name="email" type="email" value="{{ old('email') }}" required autofocus>
            @error('email') <div class="error">{{ $message }}</div> @enderror

            <label>Código de recuperação</label>
            <input name="recovery_code" value="{{ old('recovery_code') }}" placeholder="XXXX-XXXX-XXXX" required>
            @error('recovery_code') <div class="error">{{ $message }}</div> @enderror

            <label>Novo PIN</label>
            <input name="pin" type="password" inputmode="numeric" minlength="8" maxlength="8" required>

            <label>Confirmar PIN</label>
            <input name="pin_confirmation" type="password" inputmode="numeric" minlength="8" maxlength="8" required>

            <label>Nova senha opcional</label>
            <input name="password" type="password" autocomplete="new-password">

            <label>Confirmar senha</label>
            <input name="password_confirmation" type="password" autocomplete="new-password">

            <button type="submit">Atualizar acesso</button>
            <a href="{{ route('kiosk') }}">Voltar ao login</a>
        </form>
    </div>
</body>
</html>
