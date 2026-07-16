<?php

namespace App\Services;

use App\Models\AgtDocument;
use App\Models\CreditNote;
use App\Models\Sale;
use App\Services\AGT\AGTApiService;
use App\Services\AGT\AGTInvoicePayloadBuilder;
use App\Services\AGT\AGTPayloadFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AGTElectronicInvoiceService
{
    public function __construct(
        private AGTInvoicePayloadBuilder $invoiceBuilder,
        private AGTPayloadFactory $payloadFactory
    ) {
    }

    public function prepareSale(Sale $sale): AgtDocument
    {
        $sale->loadMissing('customer', 'operator', 'items.product', 'payments', 'documentSeries');

        return $this->storePayload($sale, $this->officialPayload(fn () => $this->invoiceBuilder->saleDocument($sale), $this->legacySalePayload($sale)));
    }

    public function prepareCreditNote(CreditNote $creditNote): AgtDocument
    {
        $creditNote->loadMissing('customer', 'operator', 'items.product', 'payments', 'originalSale', 'items.product');

        return $this->storePayload($creditNote, $this->officialPayload(fn () => $this->invoiceBuilder->creditNoteDocument($creditNote), $this->legacyCreditNotePayload($creditNote)));
    }

    public function send(AgtDocument $document): AgtDocument
    {
        $document->refresh();

        if (! $document->payload) {
            throw new \RuntimeException('Documento AGT sem payload preparado.');
        }

        if ($document->status === 'submitted') {
            return $document;
        }

        if (! config('agt.enabled')) {
            $document->forceFill([
                'status' => 'ready',
                'last_error' => 'Envio AGT desativado.',
            ])->save();

            return $document;
        }

        try {
            $document = $this->ensureSignedPayload($document);
            $document = $this->refreshSubmissionEnvelope($document);
            $agt = app(AGTApiService::class);
            $registrationResponse = $agt->registarFactura($document->payload);
            $newRequestId = $this->requestId($registrationResponse);
            $requestId = $newRequestId ?: null;
            $statusResponse = $newRequestId ? $agt->consultarEstado($newRequestId) : null;
            $agtStatus = $this->agtDocumentStatus($statusResponse ?: $registrationResponse);
            $status = $this->localStatus($registrationResponse, $agtStatus, $newRequestId);
            $success = in_array($status, ['submitted', 'pending'], true);
            $responseBody = array_filter([
                'registration' => $registrationResponse,
                'status_query' => $statusResponse,
                'agt_document_status' => $agtStatus,
            ], fn ($value) => $value !== null);

            $document->forceFill([
                'status' => $status,
                'attempts' => $document->attempts + 1,
                'submitted_at' => now(),
                'external_id' => $requestId ?: $document->external_id,
                'last_response' => $responseBody,
                'last_error' => $success ? null : $this->responseMessage($statusResponse ?: $registrationResponse),
                'accepted_at' => $status === 'submitted' ? now() : null,
                'rejected_at' => $status === 'failed' ? now() : null,
            ])->save();

            $this->exportResponseJson($document->fresh());
        } catch (\Throwable $e) {
            $document->forceFill([
                'status' => 'failed',
                'attempts' => $document->attempts + 1,
                'submitted_at' => now(),
                'last_error' => $e->getMessage(),
                'rejected_at' => now(),
            ])->save();

            Log::warning('Falha no envio AGT', [
                'agt_document_id' => $document->id,
                'invoice_number' => $document->invoice_number,
                'error' => $e->getMessage(),
            ]);
        }

        return $document->fresh();
    }

    private function ensureSignedPayload(AgtDocument $agtDocument): AgtDocument
    {
        $source = $agtDocument->document;

        if ($source instanceof Sale) {
            return $this->prepareSale($source)->fresh();
        }

        if ($source instanceof CreditNote) {
            return $this->prepareCreditNote($source)->fresh();
        }

        return $agtDocument;
    }

    private function officialPayload(callable $documentBuilder, array $fallback): array
    {
        if (! config('agt.enabled')) {
            return $fallback + [
                'agt_payload_mode' => 'preparado_sem_assinatura',
                'agt_notice' => 'Ative AGT_ENABLED e configure a chave privada para gerar o payload oficial assinado.',
            ];
        }

        $document = $documentBuilder();

        return $this->payloadFactory->makeSubmission([$document]);
    }

    private function storePayload(Model $document, array $payload): AgtDocument
    {
        $hash = hash('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $lookup = [
            'document_model' => $document::class,
            'document_id' => $document->id,
        ];
        $existing = AgtDocument::where($lookup)->first();

        $agtDocument = AgtDocument::updateOrCreate(
            $lookup,
            [
                'document_type_code' => $document->document_type_code,
                'invoice_number' => $document->invoice_number,
                'status' => $existing?->status === 'submitted' ? 'submitted' : 'ready',
                'payload' => $payload,
                'payload_hash' => $hash,
                'last_error' => $existing?->status === 'submitted' ? $existing->last_error : null,
            ]
        );

        $this->exportPayloadJson($agtDocument);

        return $agtDocument;
    }

    private function refreshSubmissionEnvelope(AgtDocument $document): AgtDocument
    {
        $payload = (array) $document->payload;

        if (! isset($payload['documents']) || ! is_array($payload['documents'])) {
            return $document;
        }

        $payload['submissionUUID'] = (string) Str::uuid();
        $payload['submissionTimeStamp'] = now()->utc()->format('Y-m-d\TH:i:s\Z');
        $hash = hash('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $document->forceFill([
            'payload' => $payload,
            'payload_hash' => $hash,
        ])->save();

        $this->exportPayloadJson($document);

        return $document->fresh();
    }
    private function exportPayloadJson(AgtDocument $document): void
    {
        $this->exportJson('payload', $document->invoice_number, $document->payload);
    }

    private function exportResponseJson(AgtDocument $document): void
    {
        if (! $document->last_response) {
            return;
        }

        $this->exportJson('response', $document->invoice_number, $document->last_response);
    }

    private function exportJson(string $prefix, string $invoiceNumber, array $data): void
    {
        $safeNumber = preg_replace('/[^A-Za-z0-9_-]+/', '_', trim($invoiceNumber));
        $safeNumber = trim((string) $safeNumber, '_') ?: 'documento';

        $directory = storage_path('app/agt');

        if (! is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        file_put_contents(
            $directory . DIRECTORY_SEPARATOR . $prefix . '_' . $safeNumber . '.json',
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
    }

    private function legacySalePayload(Sale $sale): array
    {
        return [
            'environment' => config('agt.environment', 'hml'),
            'document' => [
                'type' => $sale->document_type_code,
                'number' => $sale->invoice_number,
                'series' => $sale->documentSeries?->code,
                'sequence' => $sale->document_number,
                'issued_at' => $sale->created_at?->toIso8601String(),
                'hash' => $this->documentHash($sale),
            ],
            'customer' => [
                'id' => $sale->customer_id,
                'name' => $sale->customer?->name ?? 'Consumidor final',
                'email' => $sale->customer?->email,
                'phone' => $sale->customer?->phone,
            ],
            'totals' => [
                'subtotal' => (float) $sale->subtotal,
                'tax' => (float) $sale->tax,
                'discount' => (float) $sale->discount,
                'total' => (float) $sale->total,
                'paid' => (float) $sale->paid,
                'change' => (float) $sale->change,
            ],
            'items' => $sale->items->map(fn ($item) => [
                'product_id' => $item->product_id,
                'description' => $item->product?->name,
                'quantity' => (float) $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'gross_total' => (float) $item->subtotal,
                'net_total' => (float) $item->net_subtotal,
                'tax_rate' => (float) $item->tax_rate,
                'tax_amount' => (float) $item->tax_amount,
            ])->values()->all(),
            'payments' => $sale->payments->map(fn ($payment) => [
                'method' => $payment->method,
                'amount' => (float) $payment->amount,
                'reference' => $payment->reference,
            ])->values()->all(),
        ];
    }

    private function legacyCreditNotePayload(CreditNote $creditNote): array
    {
        return [
            'environment' => config('agt.environment', 'hml'),
            'document' => [
                'type' => $creditNote->document_type_code,
                'number' => $creditNote->invoice_number,
                'sequence' => $creditNote->document_number,
                'issued_at' => $creditNote->created_at?->toIso8601String(),
                'hash' => $this->documentHash($creditNote),
                'reference' => $creditNote->originalSale?->invoice_number,
                'reason' => $creditNote->reason,
            ],
            'customer' => [
                'id' => $creditNote->customer_id,
                'name' => $creditNote->customer?->name ?? 'Consumidor final',
            ],
            'totals' => [
                'subtotal' => (float) $creditNote->subtotal,
                'tax' => (float) $creditNote->tax,
                'total' => (float) $creditNote->total,
            ],
            'items' => $creditNote->items->map(fn ($item) => [
                'product_id' => $item->product_id,
                'description' => $item->product?->name,
                'quantity' => (float) $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'gross_total' => (float) $item->subtotal,
                'net_total' => (float) $item->net_subtotal,
                'tax_rate' => (float) $item->tax_rate,
                'tax_amount' => (float) $item->tax_amount,
            ])->values()->all(),
        ];
    }

    private function requestId(array $response): ?string
    {
        return $response['requestID'] ?? $response['requestId'] ?? $response['id'] ?? $response['uuid'] ?? $response['reference'] ?? null;
    }

    private function agtDocumentStatus(array $response): ?string
    {
        $status = data_get($response, 'documentStatusList.0.documentStatus')
            ?? data_get($response, 'documentStatusList.0.status')
            ?? data_get($response, 'documents.0.documentStatus')
            ?? data_get($response, 'documents.0.status')
            ?? data_get($response, 'documentStatus')
            ?? data_get($response, 'status');

        return $status ? strtoupper((string) $status) : null;
    }

    private function localStatus(array $registrationResponse, ?string $agtStatus, ?string $requestId): string
    {
        if ($agtStatus === 'V') {
            return 'submitted';
        }

        if ($agtStatus === 'I') {
            return 'failed';
        }

        if ($agtStatus === 'P' || $requestId) {
            return 'pending';
        }

        return (int) ($registrationResponse['resultCode'] ?? 0) === 1 ? 'pending' : 'failed';
    }

    private function responseMessage(array $response): string
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

    private function documentHash(Model $document): string
    {
        return hash('sha256', implode('|', [
            $document::class,
            $document->invoice_number,
            $document->document_type_code,
            $document->document_number,
            number_format((float) $document->total, 2, '.', ''),
            optional($document->created_at)->toIso8601String(),
        ]));
    }
}
