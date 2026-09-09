<?php

use App\Jobs\ConsultarEstadoAGTJob;
use App\Jobs\EnviarPendentesAGTJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;


Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new EnviarPendentesAGTJob(10))
    ->everyMinute()
    ->withoutOverlapping();

Schedule::job(new ConsultarEstadoAGTJob(50))
    ->everyFiveMinutes()
    ->withoutOverlapping();

Artisan::command('nkama:backup {--manual : Manual backup}', function () {
    $result = app(\App\Services\BackupService::class)->create((bool) $this->option('manual'));
    \App\Services\AuditLogger::log('backup_created', 'Backup', null, $result, $this->option('manual') ? 'warning' : 'info');
    $this->info('Backup criado: ' . $result['filename'] . ' (' . number_format($result['size'] / 1024, 1, ',', '.') . ' KB)');
})->purpose('Create a local SQL backup of the Nkama POS database');

Schedule::command('nkama:backup')
    ->dailyAt(\App\Services\BackupSettings::time())
    ->when(fn () => \App\Services\BackupSettings::enabled())
    ->withoutOverlapping();