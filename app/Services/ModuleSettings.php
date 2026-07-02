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
        'transfers' => true,
        'current_account' => true,
        'purchases' => true,
        'view_ticket' => true,
        'audit' => true,
    ];

    public static function all(): array
    {
        if (!Schema::hasTable('app_settings')) {
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
        $values = [
            'restaurant' => (bool) ($modules['restaurant'] ?? false),
            'supermarket' => (bool) ($modules['supermarket'] ?? false),
            'sales' => (bool) ($modules['sales'] ?? false),
            'stock' => (bool) ($modules['stock'] ?? false),
            'transfers' => (bool) ($modules['transfers'] ?? false),
            'current_account' => (bool) ($modules['current_account'] ?? false),
            'purchases' => (bool) ($modules['purchases'] ?? false),
            'view_ticket' => (bool) ($modules['view_ticket'] ?? false),
            'audit' => (bool) ($modules['audit'] ?? false),
        ];

        AppSetting::updateOrCreate(
            ['key' => 'modules'],
            ['value' => $values]
        );

        return $values;
    }
}
