<?php

namespace App\Jobs;

use App\Models\AgtDocument;
use App\Services\AGTElectronicInvoiceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ConsultarEstadoAGTJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 180;

    public function __construct(
        public int $limit = 50,
        public ?int $agtDocumentId = null
    ) {
        $this->onQueue('agt');
    }

    public function handle(AGTElectronicInvoiceService $service): void
    {
        if ($this->agtDocumentId) {
            $document = AgtDocument::find($this->agtDocumentId);

            if ($document) {
                $service->syncStatus($document);
            }

            return;
        }

        $service->syncPendingStatus($this->limit);
    }
}