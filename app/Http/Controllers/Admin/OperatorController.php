<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
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
    public function index(Request $request): View
    {
        $operators = Operator::query()
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();

                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('role', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('active', $request->input('status') === 'active');
            })
            ->with('company')
            ->latest()
            ->paginate(25)
            ->withQueryString();

        $roleOptions = OperatorPermissions::roleOptions();
        $companies = Company::where('active', true)->orderBy('name')->get();

        return view('admin.operators.index', compact('operators', 'roleOptions', 'companies'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:operators,email',
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'pin' => 'required|digits:8|confirmed',
            'password' => 'nullable|string|min:8|confirmed',
            'role' => ['required', Rule::in(OperatorPermissions::roleKeys())],
            'active' => 'sometimes|boolean',
        ]);

        if ($validated['role'] === 'super_user' && ! $this->currentOperatorIsSuperUser()) {
            return back()
                ->withInput($request->except(['pin', 'pin_confirmation', 'password', 'password_confirmation']))
                ->withErrors(['role' => 'Apenas o super usuario pode criar outro super usuario.']);
        }

        $pinFingerprint = Operator::pinFingerprint($validated['pin']);

        if (Operator::where('pin_fingerprint', $pinFingerprint)->exists()) {
            return back()
                ->withInput($request->except(['pin', 'pin_confirmation', 'password', 'password_confirmation']))
                ->withErrors(['pin' => 'Este PIN ja esta a ser usado por outro operador.']);
        }

        $recoveryCode = $this->newRecoveryCode();

        $operator = Operator::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'company_id' => $validated['company_id'],
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
        ], $operator->role === 'super_user' ? 'critical' : 'warning');

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
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'pin' => 'nullable|digits:8|confirmed',
            'password' => 'nullable|string|min:8|confirmed',
            'role' => ['required', Rule::in(OperatorPermissions::roleKeys())],
            'active' => 'sometimes|boolean',
        ]);

        if (($operator->role === 'super_user' || $validated['role'] === 'super_user') && ! $this->currentOperatorIsSuperUser()) {
            return back()->withErrors(['role' => 'Apenas o super usuario pode alterar contas super usuario.']);
        }

        $willRemainSuperUser = $validated['role'] === 'super_user' && $request->boolean('active');
        if ($operator->role === 'super_user' && ! $willRemainSuperUser && $this->isLastActiveSuperUser($operator)) {
            return back()->withErrors(['role' => 'Nao e possivel rebaixar ou inativar o ultimo super usuario ativo.']);
        }

        $payload = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'company_id' => $validated['company_id'],
            'role' => $validated['role'],
            'active' => $request->boolean('active'),
        ];

        if (! empty($validated['pin'])) {
            $pinFingerprint = Operator::pinFingerprint($validated['pin']);

            if (Operator::where('pin_fingerprint', $pinFingerprint)->whereKeyNot($operator->id)->exists()) {
                return back()
                    ->withInput($request->except(['pin', 'pin_confirmation', 'password', 'password_confirmation']))
                    ->withErrors(['pin' => 'Este PIN ja esta a ser usado por outro operador.']);
            }

            $payload['pin'] = $validated['pin'];
            $payload['pin_fingerprint'] = $pinFingerprint;
        }

        if (! empty($validated['password'])) {
            $payload['password'] = $validated['password'];
        }

        $before = $operator->only(['name', 'email', 'company_id', 'role', 'active']);
        $operator->update($payload);

        AuditLogger::log('operator_updated', 'Operator', $operator->id, [
            'before' => $before,
            'after' => $operator->only(['name', 'email', 'company_id', 'role', 'active']),
            'pin_changed' => ! empty($validated['pin']),
            'password_changed' => ! empty($validated['password']),
        ], ($before['role'] === 'super_user' || $operator->role === 'super_user') ? 'critical' : 'warning');

        return redirect()
            ->route('admin.operators.index')
            ->with('success', 'Operador atualizado com sucesso.');
    }

    public function regenerateRecoveryCode(Operator $operator): RedirectResponse
    {
        if ($operator->role === 'super_user' && ! $this->currentOperatorIsSuperUser()) {
            return back()->withErrors(['role' => 'Apenas o super usuario pode gerar recuperacao para outro super usuario.']);
        }

        $recoveryCode = $this->newRecoveryCode();

        $operator->update([
            'recovery_code' => $recoveryCode,
            'recovery_code_used_at' => null,
        ]);

        AuditLogger::log('operator_recovery_code_regenerated', 'Operator', $operator->id, [
            'name' => $operator->name,
            'email' => $operator->email,
            'role' => $operator->role,
        ], $operator->role === 'super_user' ? 'critical' : 'warning');

        return redirect()
            ->route('admin.operators.index')
            ->with('success', 'Codigo de recuperacao gerado com sucesso.')
            ->with('recovery_code', $recoveryCode)
            ->with('recovery_operator', $operator->name);
    }

    private function currentOperatorIsSuperUser(): bool
    {
        return session('operator_role') === 'super_user';
    }

    private function isLastActiveSuperUser(Operator $operator): bool
    {
        return Operator::where('role', 'super_user')
            ->where('active', true)
            ->whereKeyNot($operator->id)
            ->doesntExist();
    }

    private function newRecoveryCode(): string
    {
        return Str::upper(Str::random(4) . '-' . Str::random(4) . '-' . Str::random(4));
    }
}
