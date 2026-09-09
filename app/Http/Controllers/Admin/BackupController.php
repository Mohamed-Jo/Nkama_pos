<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use App\Services\BackupService;
use App\Services\BackupSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\View\View;

class BackupController extends Controller
{
    public function index(BackupService $backups): View
    {
        return view('admin.backups.index', [
            'backups' => $backups->list(),
            'lastBackup' => $backups->latest(),
            'settings' => BackupSettings::get(),
        ]);
    }

    public function run(): RedirectResponse
    {
        Artisan::call('nkama:backup', ['--manual' => true]);
        AuditLogger::log('backup_manual_requested', 'Backup', null, [], 'warning');

        return back()->with('success', trim(Artisan::output()) ?: 'Backup executado.');
    }

    public function updateSchedule(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'enabled' => ['nullable', 'boolean'],
            'time' => ['required', 'date_format:H:i'],
            'keep' => ['required', 'integer', 'min:1', 'max:60'],
        ]);

        $before = BackupSettings::get();
        $after = BackupSettings::update($validated);

        AuditLogger::log('backup_schedule_updated', 'BackupSettings', null, [
            'before' => $before,
            'after' => $after,
        ], 'warning');

        return back()->with('success', 'Configuracao de backup atualizada com sucesso.');
    }
}