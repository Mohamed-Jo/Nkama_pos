<?php

namespace App\Http\Middleware;

use App\Models\Operator;
use Closure;
use Illuminate\Http\Request;

class OperatorAuth
{
    public function handle(Request $request, Closure $next)
    {
        if (!session()->has('operator_id')) {
            return redirect('/kiosk');
        }

        $operator = Operator::find(session('operator_id'));

        if (!$operator || !$operator->active) {
            session()->forget(['operator_id', 'operator_name', 'operator_role']);

            return redirect('/kiosk');
        }

        session([
            'operator_name' => $operator->name,
            'operator_role' => $operator->role,
        ]);

        return $next($request);
    }
}
