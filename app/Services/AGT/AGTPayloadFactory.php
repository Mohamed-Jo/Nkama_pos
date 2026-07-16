<?php

namespace App\Services\AGT;

use Illuminate\Support\Str;

class AGTPayloadFactory
{
    public function __construct(private AGTSignatureService $signatureService)
    {
    }

    public function makeSolicitarSeriePayload(string $documentType, ?int $seriesYear = null): array
    {
        $documentType = strtoupper(trim($documentType));

        if (! in_array($documentType, ['FR', 'FT', 'NC'], true)) {
            throw new \InvalidArgumentException('Tipo de documento AGT invalido. Use FR, FT ou NC.');
        }

        return [
            'schemaVersion' => '1.2',
            'submissionUUID' => (string) Str::uuid(),
            'taxRegistrationNumber' => (string) config('agt.nif'),
            'submissionTimeStamp' => now()->utc()->format('Y-m-d\TH:i:s\Z'),
            'softwareInfo' => $this->signatureService->softwareInfo(),
            'seriesYear' => (string) ($seriesYear ?: now()->year),
            'documentType' => $documentType,
            'establishmentNumber' => (string) config('agt.establishment_number', 'SEDE'),
            'seriesContingencyIndicator' => (string) config('agt.series_contingency_indicator', 'N'),
        ];
    }

    public function makeListarSeriesPayload(string $documentType, ?int $seriesYear = null): array
    {
        return $this->makeSolicitarSeriePayload($documentType, $seriesYear);
    }

    public function makeEstadoPayload(string $requestId): array
    {
        return [
            'schemaVersion' => '1.2',
            'submissionUUID' => (string) Str::uuid(),
            'taxRegistrationNumber' => (string) config('agt.nif'),
            'submissionTimeStamp' => now()->utc()->format('Y-m-d\TH:i:s\Z'),
            'softwareInfo' => $this->signatureService->softwareInfo(),
            'requestID' => trim($requestId),
        ];
    }

    public function makeSubmission(array $documents): array
    {
        return [
            'schemaVersion' => '1.2',
            'submissionUUID' => (string) Str::uuid(),
            'taxRegistrationNumber' => (string) config('agt.nif'),
            'submissionTimeStamp' => now()->utc()->format('Y-m-d\TH:i:s\Z'),
            'softwareInfo' => $this->signatureService->softwareInfo(),
            'numberOfEntries' => count($documents),
            'documents' => $documents,
        ];
    }
}
