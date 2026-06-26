<?php

namespace App\Http\Controllers;

use App\Models\Operator;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class OperatorPasswordResetController extends Controller
{
    public function request(): View
    {
        return view('operator-auth.forgot');
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'recovery_code' => 'required|string',
            'pin' => 'required|digits:8|confirmed',
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        $operator = Operator::where('email', $validated['email'])
            ->where('active', true)
            ->first();

        $rawRecoveryCode = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $validated['recovery_code']));
        $recoveryCode = implode('-', str_split($rawRecoveryCode, 4));

        if (!$operator || !$operator->recovery_code || !Hash::check($recoveryCode, $operator->recovery_code)) {
            AuditLogger::log('operator_offline_reset_failed', 'Operator', $operator?->id, [
                'email' => $validated['email'],
            ]);

            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Email ou código de recuperação inválido.']);
        }

        if ($operator->recovery_code_used_at) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['recovery_code' => 'Este código de recuperação já foi utilizado. Peça um novo código ao super-user.']);
        }

        $pinFingerprint = Operator::pinFingerprint($validated['pin']);

        if (Operator::where('pin_fingerprint', $pinFingerprint)->whereKeyNot($operator->id)->exists()) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['pin' => 'Este PIN já está a ser usado por outro operador.']);
        }

        $payload = [
            'pin' => $validated['pin'],
            'pin_fingerprint' => $pinFingerprint,
            'recovery_code' => null,
            'recovery_code_used_at' => now(),
        ];

        if (!empty($validated['password'])) {
            $payload['password'] = $validated['password'];
        }

        $operator->update($payload);

        AuditLogger::log('operator_offline_reset_completed', 'Operator', $operator->id, [
            'email' => $operator->email,
            'password_changed' => !empty($validated['password']),
        ]);

        return redirect()
            ->route('kiosk')
            ->with('status', 'Acesso atualizado. Já pode entrar com o novo PIN.');
    }
}
