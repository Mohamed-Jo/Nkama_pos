<?php

namespace App\Http\Middleware;

use App\Models\Operator;
use App\Services\OperatorPermissions;
use Closure;
use Illuminate\Http\Request;

class EnsureOperatorPermission
{
    public function handle(Request $request, Closure $next, string ...$permissions)
    {
        $operator = Operator::find(session('operator_id'));

        if (!$operator || !$operator->active) {
            session()->forget(['operator_id', 'operator_name', 'operator_role']);

            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => 'Operador sem sessao ativa.'], 401)
                : redirect()->route('kiosk');
        }

        session(['operator_role' => $operator->role]);

        if (!OperatorPermissions::allowsAny($operator->role, $permissions)) {
            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => 'Sem permissao para esta operacao.'], 403)
                : redirect()->route('admin.dashboard')->with('error', 'Sem permissao para aceder a esta area.');
        }

        return $next($request);
    }
}
