<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Operator;
use App\Services\AuditLogger;
use App\Services\OperatorPermissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OperatorController extends Controller
{
    public function index(): View
    {
        $operators = Operator::latest()->paginate(15);
        $roleOptions = OperatorPermissions::roleOptions();

        return view('admin.operators.index', compact('operators', 'roleOptions'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:operators,email',
            'pin' => 'required|digits:8|confirmed',
            'password' => 'nullable|string|min:8|confirmed',
            'role' => ['required', Rule::in(OperatorPermissions::roleKeys())],
            'active' => 'sometimes|boolean',
        ]);

        $pinFingerprint = Operator::pinFingerprint($validated['pin']);

        if (Operator::where('pin_fingerprint', $pinFingerprint)->exists()) {
            return back()
                ->withInput($request->except(['pin', 'pin_confirmation', 'password', 'password_confirmation']))
                ->withErrors(['pin' => 'Este PIN já está a ser usado por outro operador.']);
        }

        $recoveryCode = $this->newRecoveryCode();

        $operator = Operator::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'pin' => $validated['pin'],
            'pin_fingerprint' => $pinFingerprint,
            'password' => $validated['password'] ?? null,
            'recovery_code' => $recoveryCode,
            'recovery_code_used_at' => null,
            'role' => $validated['role'],
            'active' => $request->boolean('active', true),
        ]);

        AuditLogger::log('operator_registered', 'Operator', $operator->id, [
            'name' => $operator->name,
            'email' => $operator->email,
            'role' => $operator->role,
        ]);

        return redirect()
            ->route('admin.operators.index')
            ->with('success', 'Operador registado com sucesso.')
            ->with('recovery_code', $recoveryCode)
            ->with('recovery_operator', $operator->name);
    }

    public function update(Request $request, Operator $operator): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('operators', 'email')->ignore($operator->id)],
            'pin' => 'nullable|digits:8|confirmed',
            'password' => 'nullable|string|min:8|confirmed',
            'role' => ['required', Rule::in(OperatorPermissions::roleKeys())],
            'active' => 'sometimes|boolean',
        ]);

        $payload = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'active' => $request->boolean('active'),
        ];

        if (!empty($validated['pin'])) {
            $pinFingerprint = Operator::pinFingerprint($validated['pin']);

            if (Operator::where('pin_fingerprint', $pinFingerprint)->whereKeyNot($operator->id)->exists()) {
                return back()
                    ->withInput($request->except(['pin', 'pin_confirmation', 'password', 'password_confirmation']))
                    ->withErrors(['pin' => 'Este PIN já está a ser usado por outro operador.']);
            }

            $payload['pin'] = $validated['pin'];
            $payload['pin_fingerprint'] = $pinFingerprint;
        }

        if (!empty($validated['password'])) {
            $payload['password'] = $validated['password'];
        }

        $operator->update($payload);

        AuditLogger::log('operator_updated', 'Operator', $operator->id, [
            'name' => $operator->name,
            'email' => $operator->email,
            'role' => $operator->role,
            'active' => $operator->active,
            'pin_changed' => !empty($validated['pin']),
            'password_changed' => !empty($validated['password']),
        ]);

        return redirect()
            ->route('admin.operators.index')
            ->with('success', 'Operador atualizado com sucesso.');
    }

    public function regenerateRecoveryCode(Operator $operator): RedirectResponse
    {
        $recoveryCode = $this->newRecoveryCode();

        $operator->update([
            'recovery_code' => $recoveryCode,
            'recovery_code_used_at' => null,
        ]);

        AuditLogger::log('operator_recovery_code_regenerated', 'Operator', $operator->id, [
            'name' => $operator->name,
            'email' => $operator->email,
        ]);

        return redirect()
            ->route('admin.operators.index')
            ->with('success', 'Código de recuperação gerado com sucesso.')
            ->with('recovery_code', $recoveryCode)
            ->with('recovery_operator', $operator->name);
    }

    private function newRecoveryCode(): string
    {
        return Str::upper(Str::random(4) . '-' . Str::random(4) . '-' . Str::random(4));
    }
}
