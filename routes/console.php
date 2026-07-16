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
