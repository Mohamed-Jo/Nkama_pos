<?php

namespace App\Services;

use App\Models\AppSetting;
use Illuminate\Support\Facades\Schema;

class BackupSettings
{
    public const KEY = 'backup_settings';

    public const DEFAULTS = [
        'enabled' => true,
        'time' => '02:15',
        'keep' => 10,
    ];

    public static function get(): array
    {
        if (! Schema::hasTable('app_settings')) {
            return self::DEFAULTS;
        }

        $setting = AppSetting::where('key', self::KEY)->first();

        return array_merge(self::DEFAULTS, $setting?->value ?? []);
    }

    public static function update(array $values): array
    {
        $settings = [
            'enabled' => (bool) ($values['enabled'] ?? false),
            'time' => self::normalizeTime((string) ($values['time'] ?? self::DEFAULTS['time'])),
            'keep' => min(max((int) ($values['keep'] ?? self::DEFAULTS['keep']), 1), 60),
        ];

        AppSetting::updateOrCreate(
            ['key' => self::KEY],
            ['value' => $settings]
        );

        return $settings;
    }

    public static function time(): string
    {
        return self::normalizeTime((string) (self::get()['time'] ?? self::DEFAULTS['time']));
    }

    public static function enabled(): bool
    {
        return (bool) (self::get()['enabled'] ?? self::DEFAULTS['enabled']);
    }

    public static function keep(): int
    {
        return min(max((int) (self::get()['keep'] ?? self::DEFAULTS['keep']), 1), 60);
    }

    private static function normalizeTime(string $time): string
    {
        if (! preg_match('/^([01]\d|2[0-3]):([0-5]\d)$/', $time)) {
            return self::DEFAULTS['time'];
        }

        return $time;
    }
}