<?php

namespace App\Services\AGT;

use Illuminate\Support\Facades\Log;

class AGTSignatureService
{
    public function __construct(private ?AGTCanonicalizer $canonicalizer = null)
    {
        $this->canonicalizer ??= new AGTCanonicalizer();
    }

    public function signSerieRequest(array $payload): string
    {
        return $this->generateJws($this->canonicalizer->canonicalize($payload));
    }

    public function signSoftware(array $payload): string
    {
        return $this->generateJws($this->canonicalizer->canonicalize($payload));
    }

    public function signDocument(array $payload): string
    {
        return $this->generateJws($this->canonicalizer->canonicalize($payload));
    }

    public function buildSeriesSignaturePayload(array $payload): array
    {
        $documentType = strtoupper(trim((string) ($payload['documentType'] ?? '')));

        if (! in_array($documentType, ['FR', 'FT', 'NC'], true)) {
            throw new \InvalidArgumentException('Tipo de documento AGT invalido. Use FR, FT ou NC.');
        }

        return [
            'taxRegistrationNumber' => trim((string) ($payload['taxRegistrationNumber'] ?? '')),
            'seriesYear' => trim((string) ($payload['seriesYear'] ?? '')),
            'documentType' => $documentType,
            'establishmentNumber' => trim((string) ($payload['establishmentNumber'] ?? '')),
            'seriesContingencyIndicator' => trim((string) ($payload['seriesContingencyIndicator'] ?? '')),
        ];
    }

    public function softwareInfo(): array
    {
        $detail = [
            'productId' => (string) config('agt.software.product_id'),
            'productVersion' => (string) config('agt.software.version'),
            'softwareValidationNumber' => (string) config('agt.software.validation_number'),
        ];

        return [
            'softwareInfoDetail' => $detail,
            'jwsSoftwareSignature' => $this->signSoftware($detail),
        ];
    }

    public function canonicalJson(array $payload): string
    {
        $json = json_encode(
            $this->canonicalizer->canonicalize($payload),
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION
        );

        if ($json === false) {
            throw new \RuntimeException('Nao foi possivel gerar JSON canonico AGT.');
        }

        return $json;
    }

    public function decodeJwsPayload(string $jws): array
    {
        $parts = explode('.', $jws);

        if (count($parts) !== 3) {
            throw new \InvalidArgumentException('JWS AGT invalido.');
        }

        $payload = $this->base64UrlDecode($parts[1]);
        $decoded = json_decode($payload, true);

        if (! is_array($decoded)) {
            throw new \InvalidArgumentException('Payload JWS AGT nao e JSON valido.');
        }

        return $decoded;
    }

    private function generateJws(array $payload): string
    {
        if ($payload === []) {
            throw new \InvalidArgumentException('Payload AGT vazio nao pode ser assinado.');
        }

        $header = ['typ' => 'JOSE', 'alg' => 'RS256'];
        $encodedHeader = $this->base64UrlEncode(json_encode($header, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        $encodedPayload = $this->base64UrlEncode($this->canonicalJson($payload));
        $dataToSign = $encodedHeader . '.' . $encodedPayload;

        $privateKey = openssl_pkey_get_private($this->privateKeyContent());

        if (! $privateKey) {
            throw new \RuntimeException('Nao foi possivel carregar a chave privada AGT.');
        }

        $signature = '';
        $signed = openssl_sign($dataToSign, $signature, $privateKey, OPENSSL_ALGO_SHA256);

        if (! $signed) {
            throw new \RuntimeException('Nao foi possivel assinar o payload AGT.');
        }

        if ((bool) config('agt.debug_jws', false)) {
            Log::channel(config('agt.log_channel', 'stack'))->debug('AGT JWS gerado', [
                'payload' => $payload,
                'encoded_payload' => $encodedPayload,
            ]);
        }

        return $dataToSign . '.' . $this->base64UrlEncode($signature);
    }

    private function privateKeyContent(): string
    {
        $inline = (string) config('agt.private_key');

        if (trim($inline) !== '') {
            return str_replace('\n', "\n", $inline);
        }

        $path = $this->privateKeyPath();

        if ($path === '' || ! is_file($path)) {
            throw new \RuntimeException('Chave privada AGT nao configurada ou nao encontrada.');
        }

        $content = file_get_contents($path);

        if ($content === false || trim($content) === '') {
            throw new \RuntimeException('Nao foi possivel ler a chave privada AGT.');
        }

        return $content;
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
    private function base64UrlEncode(string|false $data): string
    {
        if ($data === false) {
            throw new \RuntimeException('Falha ao codificar dados AGT.');
        }

        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $data): string
    {
        $remainder = strlen($data) % 4;

        if ($remainder > 0) {
            $data .= str_repeat('=', 4 - $remainder);
        }

        return base64_decode(strtr($data, '-_', '+/')) ?: '';
    }
}
