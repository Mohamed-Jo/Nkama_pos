<?php

namespace App\Services;

use App\Models\CashMovement;
use App\Models\Customer;
use App\Models\CustomerCard;
use App\Models\CustomerCardBalanceTransaction;
use App\Models\PointTransaction;
use App\Models\Sale;
use App\Models\Shift;
use Illuminate\Support\Facades\DB;

class CustomerCardService
{
    public const POINT_VALUE_KZ = 100;
    public const REDEEM_POINTS = 100;
    public const REDEEM_VALUE_KZ = 500;

    public function ensureCard(Customer $customer): CustomerCard
    {
        if ($customer->relationLoaded('card') && $customer->card) {
            return $customer->card;
        }

        if ($customer->card()->exists()) {
            return $customer->card()->first();
        }

        return DB::transaction(function () use ($customer) {
            $cardNumber = $this->generateCardNumber($customer);

            return CustomerCard::create([
                'customer_id' => $customer->id,
                'card_number' => $cardNumber,
                'barcode' => $this->barcodePayload($cardNumber),
                'qr_code' => $this->qrPayload($cardNumber),
                'points' => 0,
                'balance' => 0,
                'level' => 'Bronze',
                'status' => 'active',
                'issued_at' => now(),
                'expires_at' => now()->addYear(),
            ]);
        });
    }

    public function lookup(string $value): ?CustomerCard
    {
        $number = strtoupper(trim($value));

        return CustomerCard::with('customer')
            ->where(function ($query) use ($number) {
                $query->where('card_number', $number)
                    ->orWhere('barcode', $number)
                    ->orWhere('qr_code', $number);
            })
            ->first();
    }

    public function earnFromSale(Sale $sale): ?PointTransaction
    {
        if (!ModuleSettings::enabled('customer_card') || !$sale->customer_id) {
            return null;
        }

        if (PointTransaction::where('sale_id', $sale->id)->where('type', 'earn')->exists()) {
            return null;
        }

        $customer = Customer::with('card')->find($sale->customer_id);

        if (!$customer) {
            return null;
        }

        $card = $sale->customer_card_id
            ? CustomerCard::lockForUpdate()->find($sale->customer_card_id)
            : CustomerCard::lockForUpdate()->where('customer_id', $customer->id)->first();

        if (!$card) {
            $card = $this->ensureCard($customer);
            $card = CustomerCard::lockForUpdate()->find($card->id);
        }

        if (!$card || !$this->isUsable($card)) {
            return null;
        }

        $points = (int) floor((float) $sale->total / self::POINT_VALUE_KZ);

        if ($points <= 0) {
            return null;
        }

        $card->points += $points;
        $card->level = $this->levelForPoints($card->points);
        $card->save();

        if (!$sale->customer_card_id) {
            $sale->forceFill(['customer_card_id' => $card->id])->save();
        }

        return PointTransaction::create([
            'customer_card_id' => $card->id,
            'sale_id' => $sale->id,
            'type' => 'earn',
            'points' => $points,
            'balance_after' => $card->points,
            'description' => 'Pontos ganhos na venda ' . $sale->invoice_number,
        ]);
    }

    public function recharge(CustomerCard $card, float $amount, string $method, ?int $operatorId = null, ?string $description = null): CustomerCardBalanceTransaction
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('O valor da recarga deve ser maior que zero.');
        }

        return DB::transaction(function () use ($card, $amount, $method, $operatorId, $description) {
            $locked = CustomerCard::lockForUpdate()->findOrFail($card->id);

            $this->ensureUsable($locked);


            $shift = $operatorId
                ? Shift::where('operator_id', $operatorId)->where('status', 'open')->lockForUpdate()->latest()->first()
                : null;

            if (!$shift) {
                throw new \RuntimeException('Abra o caixa antes de recarregar o cartao.');
            }

            $amount = round($amount, 2);
            $locked->balance = round((float) $locked->balance + $amount, 2);
            $locked->save();

            $movementDescription = $description ?: 'Recarga do cartao cliente ' . $locked->card_number;

            CashMovement::create([
                'shift_id' => $shift->id,
                'operator_id' => $operatorId,
                'type' => 'customer_card_recharge',
                'method' => $method,
                'amount' => $amount,
                'description' => $movementDescription,
            ]);

            return CustomerCardBalanceTransaction::create([
                'customer_card_id' => $locked->id,
                'shift_id' => $shift->id,
                'operator_id' => $operatorId,
                'type' => 'recharge',
                'method' => $method,
                'amount' => $amount,
                'balance_after' => $locked->balance,
                'description' => $movementDescription,
            ]);
        });
    }

    public function paySale(CustomerCard $card, Sale $sale, float $amount): array
    {
        $amount = round($amount, 2);

        if ($amount <= 0) {
            return ['points_used' => 0, 'bonus_value' => 0.0, 'balance_used' => 0.0];
        }

        return DB::transaction(function () use ($card, $sale, $amount) {
            $locked = CustomerCard::lockForUpdate()->findOrFail($card->id);

            $this->ensureUsable($locked);


            $capacity = $this->paymentCapacity($locked);

            if ($capacity['total_available'] + 0.0001 < $amount) {
                throw new \RuntimeException('Bonus e saldo do cartao insuficientes para esta compra.');
            }

            $bonusValue = min($capacity['bonus_available'], floor($amount / self::REDEEM_VALUE_KZ) * self::REDEEM_VALUE_KZ);
            $pointsUsed = (int) (($bonusValue / self::REDEEM_VALUE_KZ) * self::REDEEM_POINTS);
            $balanceUsed = round($amount - $bonusValue, 2);

            if ($pointsUsed > 0) {
                $locked->points -= $pointsUsed;
                $locked->level = $this->levelForPoints($locked->points);
                $locked->save();

                PointTransaction::create([
                    'customer_card_id' => $locked->id,
                    'sale_id' => $sale->id,
                    'type' => 'redeem',
                    'points' => -$pointsUsed,
                    'balance_after' => $locked->points,
                    'description' => 'Bonus usado na venda ' . $sale->invoice_number,
                ]);
            }

            if ($balanceUsed > 0) {
                $locked->balance = round((float) $locked->balance - $balanceUsed, 2);
                $locked->save();

                CustomerCardBalanceTransaction::create([
                    'customer_card_id' => $locked->id,
                    'sale_id' => $sale->id,
                    'type' => 'purchase',
                    'method' => 'customer_card',
                    'amount' => -$balanceUsed,
                    'balance_after' => $locked->balance,
                    'description' => 'Saldo usado na venda ' . $sale->invoice_number,
                ]);
            }

            if (!$sale->customer_card_id) {
                $sale->forceFill(['customer_card_id' => $locked->id])->save();
            }

            AuditLogger::log('customer_card_sale_payment', 'CustomerCard', $locked->id, [
                'sale_id' => $sale->id,
                'invoice_number' => $sale->invoice_number,
                'customer_id' => $locked->customer_id,
                'card_number' => $locked->card_number,
                'amount' => $amount,
                'points_used' => $pointsUsed,
                'bonus_value' => $bonusValue,
                'balance_used' => $balanceUsed,
                'points_after' => $locked->points,
                'balance_after' => (float) $locked->balance,
            ]);

            return [
                'points_used' => $pointsUsed,
                'bonus_value' => $bonusValue,
                'balance_used' => $balanceUsed,
            ];
        });
    }

    public function paymentCapacity(CustomerCard $card): array
    {
        $pointsBlocks = intdiv((int) $card->points, self::REDEEM_POINTS);
        $bonusAvailable = $pointsBlocks * self::REDEEM_VALUE_KZ;
        $balanceAvailable = round((float) $card->balance, 2);

        return [
            'bonus_available' => round($bonusAvailable, 2),
            'balance_available' => $balanceAvailable,
            'total_available' => round($bonusAvailable + $balanceAvailable, 2),
        ];
    }

    public function redeem(CustomerCard $card, int $points, ?Sale $sale = null): PointTransaction
    {
        $points = max(0, $points);

        if ($points < self::REDEEM_POINTS || $points % self::REDEEM_POINTS !== 0) {
            throw new \InvalidArgumentException('O resgate deve ser feito em blocos de 100 pontos.');
        }

        return DB::transaction(function () use ($card, $points, $sale) {
            $locked = CustomerCard::lockForUpdate()->findOrFail($card->id);

            $this->ensureUsable($locked);


            if ($locked->points < $points) {
                throw new \RuntimeException('Pontos insuficientes para resgate.');
            }

            $value = $this->redeemValue($points);
            $locked->points -= $points;
            $locked->level = $this->levelForPoints($locked->points);
            $locked->save();

            return PointTransaction::create([
                'customer_card_id' => $locked->id,
                'sale_id' => $sale?->id,
                'type' => 'redeem',
                'points' => -$points,
                'balance_after' => $locked->points,
                'description' => 'Resgate equivalente a ' . number_format($value, 2, ',', '.') . ' Kz',
            ]);
        });
    }

    public function redeemValue(int $points): float
    {
        return floor($points / self::REDEEM_POINTS) * self::REDEEM_VALUE_KZ;
    }

    public function levelForPoints(int $points): string
    {
        return match (true) {
            $points <= 500 => 'Bronze',
            $points <= 2000 => 'Prata',
            $points <= 5000 => 'Ouro',
            default => 'Platina',
        };
    }

    public function isUsable(CustomerCard $card): bool
    {
        return $card->status === 'active' && !$card->is_expired;
    }

    private function ensureUsable(CustomerCard $card): void
    {
        if ($card->status !== 'active') {
            throw new \RuntimeException('Cartao bloqueado ou inativo.');
        }

        if ($card->is_expired) {
            throw new \RuntimeException('Cartao expirado. Atualize a validade antes de usar.');
        }
    }

    private function barcodePayload(string $cardNumber): string
    {
        if (class_exists(\Milon\Barcode\Facades\DNS1DFacade::class)) {
            return \Milon\Barcode\Facades\DNS1DFacade::getBarcodePNG($cardNumber, 'C128');
        }

        return $cardNumber;
    }

    private function qrPayload(string $cardNumber): string
    {
        if (class_exists(\SimpleSoftwareIO\QrCode\Facades\QrCode::class)) {
            return (string) \SimpleSoftwareIO\QrCode\Facades\QrCode::size(250)->generate($cardNumber);
        }

        return $cardNumber;
    }

    private function generateCardNumber(Customer $customer): string
    {
        $base = 'NK' . str_pad((string) $customer->id, 9, '0', STR_PAD_LEFT);

        if (!CustomerCard::where('card_number', $base)->exists()) {
            return $base;
        }

        $next = (int) CustomerCard::max('id') + 1;

        do {
            $candidate = 'NK' . str_pad((string) $next, 9, '0', STR_PAD_LEFT);
            $next++;
        } while (CustomerCard::where('card_number', $candidate)->exists());

        return $candidate;
    }
}