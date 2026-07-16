<?php

namespace App\Services;

use App\Models\CustomerCard;
use App\Models\CustomerCardAuthorizationRequest;
use App\Models\Operator;
use App\Models\Sale;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CustomerCardAuthorizationService
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_USED = 'used';
    public const PENDING_MINUTES = 10;
    public const APPROVED_MINUTES = 5;

    public function request(CustomerCard $card, float $amount, ?int $operatorId, ?string $reason = null, array $context = []): CustomerCardAuthorizationRequest
    {
        $this->expireOld();

        $authorization = CustomerCardAuthorizationRequest::create([
            'customer_card_id' => $card->id,
            'requested_by_operator_id' => $operatorId,
            'amount' => round($amount, 2),
            'reason' => $reason,
            'status' => self::STATUS_PENDING,
            'context' => $context,
            'expires_at' => now()->addMinutes(self::PENDING_MINUTES),
        ]);

        AuditLogger::log('customer_card_authorization_requested', 'CustomerCard', $card->id, [
            'authorization_id' => $authorization->id,
            'card_number' => $card->card_number,
            'customer_id' => $card->customer_id,
            'amount' => round($amount, 2),
            'reason' => $reason,
            'operator_id' => $operatorId,
        ]);

        return $authorization;
    }

    public function approve(CustomerCardAuthorizationRequest $authorization, Operator $supervisor, ?string $note = null): array
    {
        $this->ensureSupervisor($supervisor);
        $this->ensurePending($authorization);

        $token = Str::random(48);

        $authorization->forceFill([
            'reviewed_by_operator_id' => $supervisor->id,
            'status' => self::STATUS_APPROVED,
            'token_hash' => Hash::make($token),
            'decision_note' => $note,
            'approved_at' => now(),
            'expires_at' => now()->addMinutes(self::APPROVED_MINUTES),
        ])->save();

        AuditLogger::log('customer_card_authorization_approved', 'CustomerCard', $authorization->customer_card_id, [
            'authorization_id' => $authorization->id,
            'amount' => (float) $authorization->amount,
            'requester_id' => $authorization->requested_by_operator_id,
            'supervisor_id' => $supervisor->id,
            'supervisor_name' => $supervisor->name,
            'note' => $note,
            'expires_at' => $authorization->expires_at?->toDateTimeString(),
        ]);

        return [$authorization->fresh(['card.customer', 'requester', 'reviewer']), $token];
    }

    public function reject(CustomerCardAuthorizationRequest $authorization, Operator $supervisor, ?string $note = null): CustomerCardAuthorizationRequest
    {
        $this->ensureSupervisor($supervisor);
        $this->ensurePending($authorization);

        $authorization->forceFill([
            'reviewed_by_operator_id' => $supervisor->id,
            'status' => self::STATUS_REJECTED,
            'decision_note' => $note,
            'rejected_at' => now(),
        ])->save();

        AuditLogger::log('customer_card_authorization_rejected', 'CustomerCard', $authorization->customer_card_id, [
            'authorization_id' => $authorization->id,
            'amount' => (float) $authorization->amount,
            'requester_id' => $authorization->requested_by_operator_id,
            'supervisor_id' => $supervisor->id,
            'supervisor_name' => $supervisor->name,
            'note' => $note,
        ]);

        return $authorization->fresh(['card.customer', 'requester', 'reviewer']);
    }

    public function verify(CustomerCard $card, string $token, float $amount, ?int $operatorId): CustomerCardAuthorizationRequest
    {
        $this->expireOld();

        $candidates = CustomerCardAuthorizationRequest::where('customer_card_id', $card->id)
            ->where('requested_by_operator_id', $operatorId)
            ->where('status', self::STATUS_APPROVED)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->latest()
            ->limit(10)
            ->get();

        foreach ($candidates as $authorization) {
            if (!Hash::check($token, (string) $authorization->token_hash)) {
                continue;
            }

            if ((float) $authorization->amount + 0.0001 < round($amount, 2)) {
                throw new \RuntimeException('Autorizacao aprovada para valor inferior ao valor de fidelidade usado.');
            }

            return $authorization;
        }

        AuditLogger::log('customer_card_authorization_verify_failed', 'CustomerCard', $card->id, [
            'card_number' => $card->card_number,
            'amount' => round($amount, 2),
            'operator_id' => $operatorId,
        ]);

        throw new \RuntimeException('Autorizacao do gestor invalida, expirada ou ja usada.');
    }


    public function verifyApproved(CustomerCard $card, int $authorizationId, float $amount, ?int $operatorId): CustomerCardAuthorizationRequest
    {
        $this->expireOld();

        $authorization = CustomerCardAuthorizationRequest::whereKey($authorizationId)
            ->where('customer_card_id', $card->id)
            ->where('requested_by_operator_id', $operatorId)
            ->where('status', self::STATUS_APPROVED)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->first();

        if (!$authorization) {
            AuditLogger::log('customer_card_authorization_verify_failed', 'CustomerCard', $card->id, [
                'authorization_id' => $authorizationId,
                'card_number' => $card->card_number,
                'amount' => round($amount, 2),
                'operator_id' => $operatorId,
            ]);

            throw new \RuntimeException('Autorizacao do gestor invalida, expirada ou ja usada.');
        }

        if ((float) $authorization->amount + 0.0001 < round($amount, 2)) {
            throw new \RuntimeException('Autorizacao aprovada para valor inferior ao valor de fidelidade usado.');
        }

        return $authorization;
    }
    public function markUsed(CustomerCardAuthorizationRequest $authorization, Sale $sale): void
    {
        $authorization->forceFill([
            'sale_id' => $sale->id,
            'status' => self::STATUS_USED,
            'used_at' => now(),
        ])->save();

        AuditLogger::log('customer_card_authorization_used', 'CustomerCard', $authorization->customer_card_id, [
            'authorization_id' => $authorization->id,
            'sale_id' => $sale->id,
            'invoice_number' => $sale->invoice_number,
            'amount' => (float) $authorization->amount,
            'operator_id' => $authorization->requested_by_operator_id,
            'supervisor_id' => $authorization->reviewed_by_operator_id,
        ]);
    }

    public function expireOld(): void
    {
        CustomerCardAuthorizationRequest::whereIn('status', [self::STATUS_PENDING, self::STATUS_APPROVED])
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->update(['status' => self::STATUS_EXPIRED]);
    }

    public function ensureSupervisor(Operator $operator): void
    {
        if (!$operator->active || !OperatorPermissions::allowsAny($operator->role, ['security.manage', 'cash.audit', 'management.view'])) {
            throw new \RuntimeException('Operador sem permissao para aprovar solicitacoes de Cartao Cliente.');
        }
    }

    private function ensurePending(CustomerCardAuthorizationRequest $authorization): void
    {
        $this->expireOld();
        $authorization->refresh();

        if ($authorization->status !== self::STATUS_PENDING) {
            throw new \RuntimeException('Solicitacao ja processada ou expirada.');
        }
    }
}
