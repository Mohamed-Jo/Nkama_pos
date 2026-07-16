<?php

namespace App\Jobs;

use App\Models\DocumentSeries;
use App\Services\AGTSeriesRequestService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SolicitarSerieAGTJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 120;

    public function __construct(public int $documentSeriesId)
    {
        $this->onQueue('agt');
    }

    public function handle(AGTSeriesRequestService $service): void
    {
        $series = DocumentSeries::with('type')->find($this->documentSeriesId);

        if (! $series) {
            return;
        }

        $service->requestForSeries($series);
    }
}