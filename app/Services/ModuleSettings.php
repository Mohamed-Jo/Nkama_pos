<?php

namespace App\Services;

use App\Models\AppSetting;
use Illuminate\Support\Facades\Schema;

class ModuleSettings
{
    public const DEFAULTS = [
        'restaurant' => true,
        'supermarket' => true,
        'sales' => true,
        'stock' => true,
        'stock_warehouses' => false,
        'transfers' => true,
        'current_account' => true,
        'accounting' => true,
        'customer_card' => true,
        'customer_card_otp' => true,
        'purchases' => true,
        'view_ticket' => true,
        'audit' => true,
    ];

    public static function all(): array
    {
        if (! Schema::hasTable('app_settings')) {
            return self::DEFAULTS;
        }

        $setting = AppSetting::where('key', 'modules')->first();

        return array_merge(self::DEFAULTS, $setting?->value ?? []);
    }

    public static function enabled(string $module): bool
    {
        return (bool) (self::all()[$module] ?? false);
    }

    public static function update(array $modules): array
    {
        $values = [];

        foreach (array_keys(self::DEFAULTS) as $key) {
            $values[$key] = (bool) ($modules[$key] ?? false);
        }

        AppSetting::updateOrCreate(
            ['key' => 'modules'],
            ['value' => $values]
        );

        return $values;
    }
}