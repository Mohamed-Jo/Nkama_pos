<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class OperatorAuth
{
    public function handle(Request $request, Closure $next)
    {
        if (!session()->has('operator_id')) {
            return redirect('/kiosk');
        }

        return $next($request);
    }
}