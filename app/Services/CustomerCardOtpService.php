<?php

namespace App\Services;

use App\Models\CustomerCard;
use App\Models\CustomerCardOtp;
use App\Models\Sale;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class CustomerCardOtpService
{
    public const PURPOSE_SALE_PAYMENT = 'sale_payment';
    public const EXPIRES_MINUTES = 5;
    public const MAX_ATTEMPTS = 3;

    public function issue(CustomerCard $card, float $amount, ?int $operatorId = null): CustomerCardOtp
    {
        $card->loadMissing('customer');
        $phone = trim((string) ($card->customer?->phone ?? ''));

        if ($phone === '') {
            throw new \RuntimeException('Cliente sem telefone para receber OTP.');
        }

        CustomerCardOtp::where('customer_card_id', $card->id)
            ->where('purpose', self::PURPOSE_SALE_PAYMENT)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->update(['used_at' => now()]);

        $code = (string) random_int(100000, 999999);

        $otp = CustomerCardOtp::create([
            'customer_card_id' => $card->id,
            'purpose' => self::PURPOSE_SALE_PAYMENT,
            'amount' => round($amount, 2),
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes(self::EXPIRES_MINUTES),
            'attempts' => 0,
            'sent_to' => $phone,
            'requested_by_operator_id' => $operatorId,
        ]);

        $this->deliver($card, $otp, $code);

        AuditLogger::log('customer_card_otp_sent', 'CustomerCard', $card->id, [
            'customer_id' => $card->customer_id,
            'card_number' => $card->card_number,
            'amount' => round($amount, 2),
            'sent_to' => $this->maskPhone($phone),
            'expires_at' => $otp->expires_at?->toDateTimeString(),
            'operator_id' => $operatorId,
        ]);

        return $otp;
    }

    public function verify(CustomerCard $card, string $code, float $amount, ?int $operatorId = null): CustomerCardOtp
    {
        $otp = CustomerCardOtp::where('customer_card_id', $card->id)
            ->where('purpose', self::PURPOSE_SALE_PAYMENT)
            ->whereNull('used_at')
            ->latest()
            ->first();

        if (!$otp) {
            throw new \RuntimeException('OTP nao solicitado para este cartao.');
        }

        if ($otp->expires_at->isPast()) {
            throw new \RuntimeException('OTP expirado. Solicite novo codigo.');
        }

        if ((float) $otp->amount + 0.0001 < round($amount, 2)) {
            throw new \RuntimeException('OTP solicitado para valor inferior ao valor de fidelidade usado.');
        }

        if ($otp->attempts >= self::MAX_ATTEMPTS) {
            throw new \RuntimeException('Limite de tentativas do OTP excedido. Solicite novo codigo.');
        }

        $otp->increment('attempts');

        if (!Hash::check(trim($code), $otp->code_hash)) {
            AuditLogger::log('customer_card_otp_failed', 'CustomerCard', $card->id, [
                'customer_id' => $card->customer_id,
                'card_number' => $card->card_number,
                'amount' => round($amount, 2),
                'attempts' => $otp->fresh()->attempts,
                'operator_id' => $operatorId,
            ]);

            throw new \RuntimeException('OTP invalido.');
        }

        $otp->forceFill([
            'verified_by_operator_id' => $operatorId,
        ])->save();

        return $otp;
    }

    public function markUsed(CustomerCardOtp $otp, Sale $sale): void
    {
        $otp->forceFill([
            'sale_id' => $sale->id,
            'used_at' => now(),
        ])->save();

        AuditLogger::log('customer_card_otp_used', 'CustomerCard', $otp->customer_card_id, [
            'otp_id' => $otp->id,
            'sale_id' => $sale->id,
            'invoice_number' => $sale->invoice_number,
            'amount' => (float) $otp->amount,
            'operator_id' => $otp->verified_by_operator_id,
        ]);
    }

    public function maskPhone(?string $phone): string
    {
        $phone = preg_replace('/\D+/', '', (string) $phone);

        if (strlen($phone) <= 4) {
            return '****';
        }

        return str_repeat('*', max(strlen($phone) - 4, 0)) . substr($phone, -4);
    }

    private function deliver(CustomerCard $card, CustomerCardOtp $otp, string $code): void
    {
        $message = $this->otpMessage($card, $otp, $code);
        $sentByWhatsApp = $this->deliverWhatsApp($otp, $message);

        Log::warning('OTP Cartao Cliente gerado', [
            'otp_id' => $otp->id,
            'card_id' => $card->id,
            'card_number' => $card->card_number,
            'sent_to' => $this->maskPhone($otp->sent_to),
            'delivery_channel' => $sentByWhatsApp ? 'whatsapp' : 'log',
            'code' => $code,
            'expires_at' => $otp->expires_at?->toDateTimeString(),
        ]);
    }

    private function deliverWhatsApp(CustomerCardOtp $otp, string $message): bool
    {
        $config = config('services.customer_card_whatsapp', []);

        if (empty($config['enabled']) || empty($config['endpoint'])) {
            return false;
        }

        $phone = $this->normalizeWhatsAppPhone($otp->sent_to, (string) ($config['country_code'] ?? '244'));

        if ($phone === '') {
            return false;
        }

        $phoneKey = (string) ($config['phone_key'] ?? 'phone');
        $messageKey = (string) ($config['message_key'] ?? 'message');
        $request = Http::timeout(10);

        if (!empty($config['token'])) {
            $request = $request->withToken((string) $config['token']);
        }

        try {
            $response = $request->post((string) $config['endpoint'], [
                $phoneKey => $phone,
                $messageKey => $message,
            ]);

            if ($response->successful()) {
                return true;
            }

            Log::warning('Falha no envio WhatsApp OTP Cartao Cliente', [
                'otp_id' => $otp->id,
                'status' => $response->status(),
                'body' => mb_substr($response->body(), 0, 500),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Erro no envio WhatsApp OTP Cartao Cliente', [
                'otp_id' => $otp->id,
                'error' => $e->getMessage(),
            ]);
        }

        return false;
    }

    private function normalizeWhatsAppPhone(?string $phone, string $countryCode): string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);
        $countryCode = preg_replace('/\D+/', '', $countryCode);

        if ($digits === '') {
            return '';
        }

        if ($countryCode !== '' && !str_starts_with($digits, $countryCode)) {
            $digits = $countryCode . ltrim($digits, '0');
        }

        return $digits;
    }

    private function otpMessage(CustomerCard $card, CustomerCardOtp $otp, string $code): string
    {
        return "Codigo OTP NKAMA: {$code}. Cartao {$card->card_number}. Valido ate "
            . $otp->expires_at?->format('d/m/Y H:i')
            . '. Nao partilhe este codigo.';
    }
}