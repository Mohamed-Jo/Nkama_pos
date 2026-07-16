<?php

namespace App\Jobs;

use App\Models\AgtDocument;
use App\Services\AGTElectronicInvoiceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class EnviarDocumentoAGTJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(public int $agtDocumentId)
    {
        $this->onQueue('agt');
    }

    public function handle(AGTElectronicInvoiceService $service): void
    {
        $document = AgtDocument::find($this->agtDocumentId);

        if (! $document || $document->status === 'submitted') {
            return;
        }

        $service->send($document);
    }
}