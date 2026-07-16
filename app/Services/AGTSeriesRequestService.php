<?php

namespace App\Services;

use App\Models\AgtSeries;
use App\Models\CreditNote;
use App\Models\DocumentSeries;
use App\Models\DocumentType;
use App\Models\Sale;
use App\Services\AGT\AGTApiService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class AGTSeriesRequestService
{
    public function __construct(private AGTApiService $agt)
    {
    }

    public function requestForSale(Sale $sale): ?array
    {
        $sale->loadMissing('documentSeries.type');

        return $this->requestForSeries($sale->documentSeries, $sale);
    }

    public function requestForCreditNote(CreditNote $creditNote): ?array
    {
        $creditNote->loadMissing('documentSeries.type');

        return $this->requestForSeries($creditNote->documentSeries, $creditNote);
    }

    public function requestForSeries(?DocumentSeries $series, ?Model $source = null): ?array
    {
        if (! config('agt.enabled') || ! $series || ! $series->type) {
            return null;
        }

        if (! $this->shouldRequest($series)) {
            return null;
        }

        try {
            $response = $this->agt->solicitarSerie((string) $series->type->code, (int) $series->year);
            $success = $this->success($response);

            $this->recordSeriesResponse($series, $response, $success);

            if (! $success) {
                Log::warning('Solicitacao automatica de serie AGT rejeitada', [
                    'series_id' => $series->id,
                    'document_type' => $series->type->code,
                    'year' => $series->year,
                    'source' => $source ? $source::class : null,
                    'source_id' => $source?->getKey(),
                    'message' => $this->message($response),
                ]);
            }

            return $response;
        } catch (\Throwable $e) {
            $this->recordSeriesResponse($series, ['errorList' => [$e->getMessage()]], false);

            Log::warning('Falha na solicitacao automatica de serie AGT', [
                'series_id' => $series->id,
                'document_type' => $series->type->code,
                'year' => $series->year,
                'source' => $source ? $source::class : null,
                'source_id' => $source?->getKey(),
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function recordSeriesResponse(DocumentSeries $series, array $response, bool $success): AgtSeries
    {
        $remoteSeries = $this->firstSeriesInfo($response);
        $targetSeries = $success && $remoteSeries ? $this->ensureLocalSeries($series, $remoteSeries) : $series;
        $targetSeries->loadMissing('type');

        $status = $success ? 'accepted' : 'rejected';
        $now = now();

        return AgtSeries::updateOrCreate(
            [
                'environment' => (string) config('agt.environment', 'hml'),
                'document_type_code' => (string) $targetSeries->type->code,
                'series_year' => (int) $targetSeries->year,
                'series_code' => (string) $targetSeries->code,
            ],
            [
                'document_series_id' => $targetSeries->id,
                'start_number' => $targetSeries->start_number,
                'current_number' => $targetSeries->current_number,
                'status' => $status,
                'request_id' => $response['requestID'] ?? $response['requestId'] ?? null,
                'response_payload' => $response,
                'requested_at' => $now,
                'accepted_at' => $success ? $now : null,
                'rejected_at' => $success ? null : $now,
                'last_error' => $success ? null : $this->message($response),
            ]
        );
    }

    public function syncListedSeries(string $documentType, ?int $year, array $response): int
    {
        if (! $this->success($response)) {
            return 0;
        }

        $count = 0;

        foreach ($this->seriesInfo($response) as $info) {
            $code = trim((string) ($info['seriesCode'] ?? ''));
            $remoteType = strtoupper(trim((string) ($info['documentType'] ?? $documentType)));
            $remoteYear = (int) ($info['seriesYear'] ?? $year ?? now()->year);

            if ($code === '' || ! in_array($remoteType, ['FR', 'FT', 'NC'], true) || $remoteYear < 2000) {
                continue;
            }

            $localSeries = $this->findLocalSeries($remoteType, $remoteYear, $code);
            $status = $this->remoteStatus($info);

            AgtSeries::updateOrCreate(
                [
                    'environment' => (string) config('agt.environment', 'hml'),
                    'document_type_code' => $remoteType,
                    'series_year' => $remoteYear,
                    'series_code' => $code,
                ],
                [
                    'document_series_id' => $localSeries?->id,
                    'start_number' => (int) ($info['firstDocumentNumber'] ?: 1),
                    'current_number' => $localSeries?->current_number,
                    'status' => $status,
                    'request_id' => $info['id'] ?? null,
                    'response_payload' => $info,
                    'requested_at' => $info['seriesCreationDate'] ?? now(),
                    'accepted_at' => $status === 'accepted' ? ($info['seriesCreationDate'] ?? now()) : null,
                    'rejected_at' => $status === 'rejected' ? now() : null,
                    'last_error' => null,
                ]
            );

            $count++;
        }

        return $count;
    }

    private function success(array $response): bool
    {
        if ((int) ($response['resultCode'] ?? 0) === 1) {
            return true;
        }

        if (isset($response['errorList']) && ! empty($response['errorList'])) {
            return false;
        }

        return isset($response['seriesInfo']) || isset($response['requestID']) || isset($response['documents']);
    }

    private function localAgtSeries(DocumentSeries $series): ?AgtSeries
    {
        return AgtSeries::query()
            ->where('environment', (string) config('agt.environment', 'hml'))
            ->where('document_type_code', (string) $series->type->code)
            ->where('series_year', (int) $series->year)
            ->where('series_code', (string) $series->code)
            ->first();
    }

    private function shouldRequest(DocumentSeries $series): bool
    {
        $agtSeries = $this->localAgtSeries($series);

        if (! $agtSeries || $agtSeries->status === null) {
            return true;
        }

        return $agtSeries->status === 'rejected' && $this->wasLocalConfigurationError($agtSeries);
    }

    private function wasLocalConfigurationError(AgtSeries $agtSeries): bool
    {
        $response = (array) $agtSeries->response_payload;

        if ((bool) ($response['localError'] ?? false) || (bool) ($response['transportError'] ?? false)) {
            return true;
        }

        $message = $agtSeries->last_error ?: $this->message($response);

        return str_contains($message, 'Connection refused')
            || str_contains($message, 'Falha de conectividade AGT')
            || str_contains($message, 'cURL error')
            || str_contains($message, 'E39')
            || str_contains($message, 'jwsSoftwareSignature')
            || str_contains($message, 'Integracao AGT desativada')
            || str_contains($message, 'Chave privada AGT nao configurada')
            || str_contains($message, 'AGT_NIF nao configurado')
            || str_contains($message, 'AGT_USERNAME nao configurado')
            || str_contains($message, 'AGT_PASSWORD nao configurado')
            || str_contains($message, 'AGT_SOFTWARE_VALIDATION_NUMBER nao configurado');
    }

    private function ensureLocalSeries(DocumentSeries $series, array $info): DocumentSeries
    {
        $remoteCode = trim((string) ($info['seriesCode'] ?? ''));

        if ($remoteCode === '' || $remoteCode === (string) $series->code) {
            return $series;
        }

        $startNumber = max((int) ($info['firstDocumentNumber'] ?: 1), 1);

        $existing = DocumentSeries::query()
            ->where('document_type_id', $series->document_type_id)
            ->where('year', (int) $series->year)
            ->where('code', $remoteCode)
            ->first();

        if ($existing) {
            if (! $existing->active) {
                $existing->update(['active' => true]);
            }

            return $existing;
        }

        if ((int) $series->current_number === 0) {
            $series->update([
                'code' => $remoteCode,
                'start_number' => $startNumber,
                'active' => true,
            ]);

            return $series->fresh('type');
        }

        $series->update(['active' => false]);

        return DocumentSeries::create([
            'document_type_id' => $series->document_type_id,
            'year' => (int) $series->year,
            'code' => $remoteCode,
            'start_number' => $startNumber,
            'current_number' => 0,
            'active' => true,
        ])->load('type');
    }

    private function findLocalSeries(string $documentType, int $year, string $code): ?DocumentSeries
    {
        $typeId = DocumentType::query()->where('code', $documentType)->value('id');

        if (! $typeId) {
            return null;
        }

        return DocumentSeries::query()
            ->where('document_type_id', $typeId)
            ->where('year', $year)
            ->where('code', $code)
            ->first();
    }

    private function firstSeriesInfo(array $response): ?array
    {
        return $this->seriesInfo($response)[0] ?? null;
    }

    private function seriesInfo(array $response): array
    {
        if (isset($response['seriesFEResult']) && is_array($response['seriesFEResult'])) {
            return [[
                'id' => $response['requestID'] ?? $response['requestId'] ?? null,
                'seriesCode' => $response['seriesFEResult']['seriesCode'] ?? null,
                'seriesYear' => now()->year,
                'seriesStatus' => 'A',
                'firstDocumentNumber' => $response['seriesFEResult']['firstDocumentNo'] ?? 1,
                'response' => $response['seriesFEResult'],
            ]];
        }

        $seriesInfo = $response['seriesInfo'] ?? null;

        if (! is_array($seriesInfo)) {
            return [];
        }

        return array_values(array_filter($seriesInfo, fn ($item) => is_array($item)));
    }

    private function remoteStatus(array $info): string
    {
        return strtoupper((string) ($info['seriesStatus'] ?? '')) === 'A' ? 'accepted' : 'rejected';
    }

    private function message(array $response): string
    {
        $errors = $response['errorList'] ?? $response['errors'] ?? null;

        if (is_array($errors)) {
            return collect($errors)
                ->map(fn ($error) => is_array($error) ? json_encode($error, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : (string) $error)
                ->filter()
                ->take(3)
                ->implode(' | ') ?: 'Erro AGT nao especificado.';
        }

        return (string) ($errors ?: ($response['message'] ?? 'Erro AGT nao especificado.'));
    }
}