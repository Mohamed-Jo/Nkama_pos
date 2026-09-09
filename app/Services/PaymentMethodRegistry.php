<?php

namespace App\Services;

use App\Models\PaymentMethod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class PaymentMethodRegistry
{
    public const FALLBACK = [
        ['code' => 'cash', 'name' => 'Dinheiro', 'type' => 'cash', 'show_in_pos' => true, 'show_in_sales' => true, 'show_in_expenses' => true, 'show_in_current_account' => true, 'requires_bank_account' => false],
        ['code' => 'card', 'name' => 'Cartao/TPA', 'type' => 'card', 'show_in_pos' => true, 'show_in_sales' => true, 'show_in_expenses' => true, 'show_in_current_account' => true, 'requires_bank_account' => false],
        ['code' => 'transf', 'name' => 'Transferencia', 'type' => 'bank_transfer', 'show_in_pos' => true, 'show_in_sales' => true, 'show_in_expenses' => true, 'show_in_current_account' => true, 'requires_bank_account' => false],
        ['code' => 'multi', 'name' => 'Pagamento Misto', 'type' => 'mixed', 'show_in_pos' => true, 'show_in_sales' => true, 'show_in_expenses' => false, 'show_in_current_account' => false, 'requires_bank_account' => false],
        ['code' => 'bank', 'name' => 'Banco', 'type' => 'bank', 'show_in_pos' => false, 'show_in_sales' => false, 'show_in_expenses' => true, 'show_in_current_account' => false, 'requires_bank_account' => true],
        ['code' => 'customer_card', 'name' => 'Cartao Cliente', 'type' => 'customer_card', 'show_in_pos' => true, 'show_in_sales' => false, 'show_in_expenses' => false, 'show_in_current_account' => false, 'requires_bank_account' => false],
        ['code' => 'pending', 'name' => 'Pendente', 'type' => 'credit', 'show_in_pos' => false, 'show_in_sales' => false, 'show_in_expenses' => true, 'show_in_current_account' => false, 'requires_bank_account' => false],
    ];

    public static function all(): Collection
    {
        if (! Schema::hasTable('payment_methods')) {
            return self::fallbackCollection();
        }

        return PaymentMethod::with('bankAccount')->orderBy('sort_order')->orderBy('name')->get();
    }

    public static function active(?string $context = null): Collection
    {
        $methods = self::all()->filter(fn ($method) => (bool) $method->active)->values();

        if ($context) {
            $column = 'show_in_' . $context;
            $methods = $methods->filter(fn ($method) => (bool) ($method->{$column} ?? false))->values();
        }

        return $methods;
    }

    public static function codes(?string $context = null): array
    {
        return self::active($context)->pluck('code')->values()->all();
    }

    public static function labels(?string $context = null): array
    {
        return self::active($context)->pluck('name', 'code')->all();
    }

    public static function supports(string $code, ?string $context = null): bool
    {
        return in_array($code, self::codes($context), true);
    }

    private static function fallbackCollection(): Collection
    {
        return collect(self::FALLBACK)->map(function (array $method, int $index) {
            return (object) array_merge([
                'id' => null,
                'active' => true,
                'bank_account_id' => null,
                'bankAccount' => null,
                'sort_order' => ($index + 1) * 10,
            ], $method);
        });
    }
}