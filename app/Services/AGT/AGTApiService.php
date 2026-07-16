<?php

namespace App\Services\AGT;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AGTApiService
{
    public function __construct(
        private AGTSignatureService $signatureService,
        private AGTPayloadFactory $payloadFactory
    ) {
    }

    public function solicitarSerie(string $documentType, ?int $seriesYear = null): array
    {
        if ($errors = $this->configurationErrors()) {
            return $this->localError($errors);
        }

        $payload = $this->payloadFactory->makeSolicitarSeriePayload($documentType, $seriesYear);
        $signaturePayload = $this->signatureService->buildSeriesSignaturePayload($payload);

        return $this->postSignedSeriesPayload(
            $this->withSeriesSignature($payload, $this->signatureService->signSerieRequest($signaturePayload)),
            'solicitar_serie',
            'Solicitar Serie'
        );
    }

    public function listarSeries(string $documentType, ?int $seriesYear = null): array
    {
        if ($errors = $this->configurationErrors()) {
            return $this->localError($errors);
        }

        $payload = $this->payloadFactory->makeListarSeriesPayload($documentType, $seriesYear);
        $signaturePayload = $this->signatureService->buildSeriesSignaturePayload($payload);

        return $this->postSignedSeriesPayload(
            $this->withSeriesSignature($payload, $this->signatureService->signSerieRequest($signaturePayload)),
            'listar_series',
            'Listar Series'
        );
    }

    public function registarFactura(array $payload): array
    {
        return $this->postPayload($payload, 'registar_factura', 'Registar Factura');
    }

    public function consultarEstado(string $requestId): array
    {
        $payload = $this->payloadFactory->makeEstadoPayload($requestId);
        $payload['jwsSignature'] = $this->signatureService->signDocument([
            'taxRegistrationNumber' => $payload['taxRegistrationNumber'],
            'requestID' => $payload['requestID'],
        ]);

        return $this->postPayload($payload, 'obter_estado', 'Obter Estado');
    }

    private function withSeriesSignature(array $payload, string $jwsSignature): array
    {
        $ordered = [];

        foreach ($payload as $key => $value) {
            if ($key === 'seriesContingencyIndicator') {
                $ordered['jwsSignature'] = $jwsSignature;
            }

            $ordered[$key] = $value;
        }

        return $ordered;
    }

    private function postSignedSeriesPayload(array $payload, string $endpointKey, string $label): array
    {
        if ((bool) config('agt.debug_jws', false)) {
            Log::channel(config('agt.log_channel', 'stack'))->debug("AGT {$label} assinatura", [
                'signed_payload' => $this->signatureService->decodeJwsPayload($payload['jwsSignature']),
            ]);
        }

        return $this->postPayload($payload, $endpointKey, $label);
    }

    private function postPayload(array $payload, string $endpointKey, string $label): array
    {
        if ($errors = $this->configurationErrors()) {
            return $this->localError($errors, $payload);
        }

        $endpoint = $this->endpoint($endpointKey);

        if (! $endpoint) {
            return [
                'resultCode' => 0,
                'errorList' => ["Endpoint AGT {$endpointKey} nao configurado."],
                'payload' => $payload,
            ];
        }

        $jsonBody = $this->signatureService->canonicalJson($payload);

        Log::channel(config('agt.log_channel', 'stack'))->info("AGT {$label} request", [
            'endpoint' => $endpoint,
            'payload_hash' => hash('sha256', $jsonBody),
        ]);

        try {
            $response = Http::withBasicAuth((string) config('agt.username'), (string) config('agt.password'))
                ->acceptJson()
                ->withHeaders(['Content-Type' => 'application/json'])
                ->withBody($jsonBody, 'application/json')
                ->connectTimeout((int) config('agt.connect_timeout', 10))
                ->timeout((int) config('agt.timeout', 30))
                ->post($endpoint);
        } catch (ConnectionException $e) {
            return $this->transportError($endpoint, $e->getMessage(), $payload);
        } catch (\Throwable $e) {
            return $this->transportError($endpoint, $e->getMessage(), $payload);
        }

        $body = $response->json();

        if (! $response->successful()) {
            return [
                'resultCode' => 0,
                'errorList' => ['HTTP ' . $response->status(), mb_substr($response->body(), 0, 1500)],
                'raw' => $body ?? mb_substr($response->body(), 0, 1500),
            ];
        }

        return $body ?? [
            'resultCode' => 0,
            'errorList' => ['Resposta AGT vazia.'],
            'raw' => mb_substr($response->body(), 0, 1500),
        ];
    }

    private function transportError(string $endpoint, string $message, array $payload): array
    {
        Log::channel(config('agt.log_channel', 'stack'))->warning('Falha de conectividade AGT', [
            'endpoint' => $endpoint,
            'message' => $message,
            'payload_hash' => hash('sha256', $this->signatureService->canonicalJson($payload)),
        ]);

        return [
            'resultCode' => 0,
            'transportError' => true,
            'errorList' => ['Falha de conectividade AGT: ' . mb_substr($message, 0, 500)],
        ];
    }
    private function endpoint(string $key): ?string
    {
        $environment = (string) config('agt.environment', 'hml');

        return config("agt.endpoints.{$environment}.{$key}");
    }
    private function configurationErrors(): array
    {
        $errors = [];

        if (! (bool) config('agt.enabled', false)) {
            $errors[] = 'Integracao AGT desativada. Defina AGT_ENABLED=true no .env.';
        }

        foreach ([
            'agt.nif' => 'AGT_NIF',
            'agt.username' => 'AGT_USERNAME',
            'agt.password' => 'AGT_PASSWORD',
            'agt.software.validation_number' => 'AGT_SOFTWARE_VALIDATION_NUMBER',
        ] as $configKey => $envKey) {
            if (blank(config($configKey))) {
                $errors[] = "{$envKey} nao configurado.";
            }
        }

        $hasInlineKey = filled(config('agt.private_key'));
        $keyPath = $this->privateKeyPath();

        if (! $hasInlineKey && ($keyPath === '' || ! is_file($keyPath))) {
            $errors[] = 'Chave privada AGT nao configurada ou nao encontrada.';
        }

        return $errors;
    }

    private function privateKeyPath(): string
    {
        $path = trim((string) config('agt.private_key_path'));

        if ($path === '') {
            return '';
        }

        if (preg_match('/^(?:[A-Za-z]:[\\\\\/]?|\\\\\\\\|\/)/', $path)) {
            return $path;
        }

        return base_path(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path));
    }
    private function localError(array $errors, array $payload = []): array
    {
        return array_filter([
            'resultCode' => 0,
            'localError' => true,
            'errorList' => $errors,
            'payload' => $payload ?: null,
        ], fn ($value) => $value !== null);
    }
}
