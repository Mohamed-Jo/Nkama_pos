<?php

namespace App\Services;

use App\Models\CashMovement;
use App\Models\Payments;
use Illuminate\Support\Collection;

class PaymentMethodSummary
{
    public static function labels(): array
    {
        return array_merge([
            'cash' => 'Dinheiro',
            'card' => 'Cartao/TPA',
            'transf' => 'Transferencia',
            'transfer' => 'Transferencia',
            'multi' => 'Pagamento Misto',
            'mixed' => 'Pagamento Misto',
            'credit' => 'Conta Corrente',
            'mixed_credit' => 'Misto + Conta',
            'customer_card' => 'Cartao Cliente',
            'bank' => 'Banco',
            'pending' => 'Pendente',
        ], PaymentMethodRegistry::labels());
    }

    public static function label(?string $method): string
    {
        if (! $method) {
            return '-';
        }

        return self::labels()[$method] ?? strtoupper(str_replace('_', ' ', $method));
    }

    public static function totalsForShift(int $shiftId): Collection
    {
        $payments = Payments::where('shift_id', $shiftId)
            ->selectRaw('method, SUM(amount) as total')
            ->groupBy('method')
            ->pluck('total', 'method');

        $movements = CashMovement::where('shift_id', $shiftId)
            ->selectRaw('method, SUM(amount) as total')
            ->groupBy('method')
            ->pluck('total', 'method');

        return self::mergeTotals($payments, $movements);
    }

    public static function totalsForCollections(Collection $payments, Collection $cashMovements): Collection
    {
        return self::mergeTotals(
            $payments->groupBy('method')->map(fn ($items) => (float) $items->sum('amount')),
            $cashMovements->groupBy('method')->map(fn ($items) => (float) $items->sum('amount'))
        );
    }

    public static function normalize(Collection $totals): Collection
    {
        $labels = self::labels();

        return $totals
            ->mapWithKeys(fn ($amount, $method) => [(string) $method => round((float) $amount, 2)])
            ->sortKeysUsing(function ($a, $b) use ($labels) {
                $priority = ['cash' => 10, 'card' => 20, 'transf' => 30, 'multi' => 40, 'customer_card' => 50];
                return ($priority[$a] ?? 999) <=> ($priority[$b] ?? 999) ?: strcmp($labels[$a] ?? $a, $labels[$b] ?? $b);
            })
            ->map(fn ($amount, $method) => (object) [
                'code' => $method,
                'label' => $labels[$method] ?? strtoupper(str_replace('_', ' ', $method)),
                'total' => $amount,
            ])
            ->values();
    }

    public static function cashTotal(Collection $summary): float
    {
        return (float) optional($summary->firstWhere('code', 'cash'))->total;
    }

    private static function mergeTotals(Collection $payments, Collection $movements): Collection
    {
        $keys = $payments->keys()->merge($movements->keys())->filter()->unique();

        return self::normalize($keys->mapWithKeys(fn ($method) => [
            (string) $method => (float) ($payments[$method] ?? 0) + (float) ($movements[$method] ?? 0),
        ]));
    }
}