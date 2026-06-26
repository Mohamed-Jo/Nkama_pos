<?php

namespace App\Http\Middleware;

use App\Services\ModuleSettings;
use Closure;
use Illuminate\Http\Request;

class EnsureModuleEnabled
{
    public function handle(Request $request, Closure $next, string $module)
    {
        if (!ModuleSettings::enabled($module)) {
            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => 'Módulo desativado pelo super-user.'], 403)
                : redirect()->route('admin.dashboard')->with('error', 'Módulo desativado pelo super-user.');
        }

        return $next($request);
    }
}
