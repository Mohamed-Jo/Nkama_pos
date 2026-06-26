<?php

namespace App\Http\Middleware;

use App\Models\Operator;
use Closure;
use Illuminate\Http\Request;

class EnsureOperatorRole
{
    public function handle(Request $request, Closure $next, string ...$roles)
    {
        $operator = Operator::find(session('operator_id'));

        if (!$operator || !$operator->active) {
            session()->forget(['operator_id', 'operator_name', 'operator_role']);

            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => 'Operador sem sessão ativa.'], 401)
                : redirect()->route('kiosk');
        }

        session(['operator_role' => $operator->role]);

        if (!in_array($operator->role, $roles, true)) {
            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => 'Sem permissão para esta operação.'], 403)
                : redirect()->route('admin.dashboard')->with('error', 'Sem permissão para aceder a esta área.');
        }

        return $next($request);
    }
}
