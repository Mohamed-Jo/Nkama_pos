<?php

namespace App\Http\Middleware;

use App\Models\Shift;
use Closure;
use Illuminate\Support\Facades\Schema;

class EnsureShiftOpen
{
    public function handle($request, Closure $next)
    {
        $operatorId = session('operator_id');

        if (!$operatorId) {
            return redirect('/kiosk');
        }

        $query = Shift::where('status', 'open');

        if (Schema::hasColumn('shifts', 'operator_id')) {
            $query->where('operator_id', $operatorId);
        } elseif (Schema::hasColumn('shifts', 'user_id') && auth()->id()) {
            $query->where('user_id', auth()->id());
        }

        $shift = $query->first();

        if (!$shift) {
            return response()->json([
                'success' => false,
                'message' => 'Abra o caixa antes de vender'
            ], 403);
        }

        return $next($request);
    }
}
