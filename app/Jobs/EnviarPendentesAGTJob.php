<?php

namespace App\Jobs;

use App\Services\AGTElectronicInvoiceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class EnviarPendentesAGTJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 300;

    public function __construct(public int $limit = 20)
    {
        $this->onQueue('agt');
    }

    public function handle(AGTElectronicInvoiceService $service): void
    {
        $service->sendPending($this->limit);
    }
}