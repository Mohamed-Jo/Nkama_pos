<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use App\Services\ModuleSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ModuleController extends Controller
{
    public function index(): View
    {
        return view('admin.modules.index', [
            'modules' => ModuleSettings::all(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $before = ModuleSettings::all();
        $after = ModuleSettings::update($request->input('modules', []));

        AuditLogger::log('modules_updated', 'ModuleSettings', null, [
            'before' => $before,
            'after' => $after,
        ]);

        return redirect()
            ->route('admin.modules.index')
            ->with('success', 'Módulos atualizados com sucesso.');
    }
}
