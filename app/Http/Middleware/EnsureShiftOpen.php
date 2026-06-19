<?php

namespace App\Http\Middleware;

use App\Models\Shift;
use Closure;

class EnsureShiftOpen
{
    public function handle($request, Closure $next)
    {
        $operatorId = session('operator_id');

        if (!$operatorId) {
            return redirect('/kiosk');
        }

        $shift = Shift::where('operator_id', $operatorId)
            ->where('status', 'open')
            ->first();

        if (!$shift) {
            return response()->json([
                'success' => false,
                'message' => 'Abra o caixa antes de vender'
            ], 403);
        }

        return $next($request);
    }
}