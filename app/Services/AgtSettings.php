<?php

namespace App\Services;

use App\Models\AppSetting;
use Illuminate\Support\Facades\Schema;

class AgtSettings
{
    public const KEY = 'agt_settings';

    public const DEFAULTS = [
        'enabled' => false,
        'environment' => 'hml',
        'timeout' => 30,
        'connect_timeout' => 10,
        'log_channel' => 'stack',
        'debug_jws' => false,
        'nif' => '',
        'username' => '',
        'password' => '',
        'private_key' => '',
        'private_key_path' => '',
        'product_id' => 'XHotel',
        'software_version' => '1.2',
        'software_validation_number' => '',
        'establishment_number' => 'SEDE',
        'series_contingency_indicator' => 'N',
        'endpoints' => [
            'hml' => [
                'registar_factura' => '',
                'consultar_factura' => '',
                'obter_estado' => '',
                'solicitar_serie' => '',
                'listar_series' => '',
                'listar_facturas' => '',
            ],
            'prd' => [
                'registar_factura' => '',
                'consultar_factura' => '',
                'obter_estado' => '',
                'solicitar_serie' => '',
                'listar_series' => '',
                'listar_facturas' => '',
            ],
        ],
    ];

    public static function current(): array
    {
        $defaults = self::defaultsFromConfig();

        if (! self::settingsTableExists()) {
            return $defaults;
        }

        $setting = AppSetting::where('key', self::KEY)->first();

        return self::mergeSettings($defaults, $setting?->value ?? []);
    }

    public static function update(array $input): array
    {
        $before = self::current();
        $values = self::mergeSettings($before, self::normalize($input, $before));

        AppSetting::updateOrCreate(
            ['key' => self::KEY],
            ['value' => $values]
        );

        self::apply($values);

        return $values;
    }

    public static function apply(?array $settings = null): void
    {
        $settings ??= self::current();

        config([
            'agt.enabled' => (bool) ($settings['enabled'] ?? false),
            'agt.environment' => $settings['environment'] ?? 'hml',
            'agt.timeout' => (int) ($settings['timeout'] ?? 30),
            'agt.connect_timeout' => (int) ($settings['connect_timeout'] ?? 10),
            'agt.log_channel' => $settings['log_channel'] ?? 'stack',
            'agt.debug_jws' => (bool) ($settings['debug_jws'] ?? false),
            'agt.nif' => $settings['nif'] ?? '',
            'agt.username' => $settings['username'] ?? '',
            'agt.password' => $settings['password'] ?? '',
            'agt.private_key' => $settings['private_key'] ?? '',
            'agt.private_key_path' => $settings['private_key_path'] ?? '',
            'agt.software.product_id' => $settings['product_id'] ?? '',
            'agt.software.version' => $settings['software_version'] ?? '',
            'agt.software.validation_number' => $settings['software_validation_number'] ?? '',
            'agt.establishment_number' => $settings['establishment_number'] ?? 'SEDE',
            'agt.series_contingency_indicator' => $settings['series_contingency_indicator'] ?? 'N',
            'agt.endpoints' => $settings['endpoints'] ?? [],
        ]);
    }

    public static function hasInlinePrivateKey(array $settings): bool
    {
        return trim((string) ($settings['private_key'] ?? '')) !== '';
    }

    private static function defaultsFromConfig(): array
    {
        return self::mergeSettings(self::DEFAULTS, [
            'enabled' => (bool) config('agt.enabled', false),
            'environment' => (string) config('agt.environment', 'hml'),
            'timeout' => (int) config('agt.timeout', 30),
            'connect_timeout' => (int) config('agt.connect_timeout', 10),
            'log_channel' => (string) config('agt.log_channel', 'stack'),
            'debug_jws' => (bool) config('agt.debug_jws', false),
            'nif' => (string) config('agt.nif', ''),
            'username' => (string) config('agt.username', ''),
            'password' => (string) config('agt.password', ''),
            'private_key' => (string) config('agt.private_key', ''),
            'private_key_path' => (string) config('agt.private_key_path', ''),
            'product_id' => (string) config('agt.software.product_id', 'XHotel'),
            'software_version' => (string) config('agt.software.version', '1.2'),
            'software_validation_number' => (string) config('agt.software.validation_number', ''),
            'establishment_number' => (string) config('agt.establishment_number', 'SEDE'),
            'series_contingency_indicator' => (string) config('agt.series_contingency_indicator', 'N'),
            'endpoints' => (array) config('agt.endpoints', []),
        ]);
    }

    private static function normalize(array $input, array $before): array
    {
        $values = [
            'enabled' => (bool) ($input['enabled'] ?? false),
            'environment' => in_array($input['environment'] ?? 'hml', ['hml', 'prd'], true) ? $input['environment'] : 'hml',
            'timeout' => min(max((int) ($input['timeout'] ?? 30), 1), 180),
            'connect_timeout' => min(max((int) ($input['connect_timeout'] ?? 10), 1), 60),
            'log_channel' => trim((string) ($input['log_channel'] ?? 'stack')) ?: 'stack',
            'debug_jws' => (bool) ($input['debug_jws'] ?? false),
            'nif' => trim((string) ($input['nif'] ?? '')),
            'username' => trim((string) ($input['username'] ?? '')),
            'private_key_path' => trim((string) ($input['private_key_path'] ?? '')),
            'product_id' => trim((string) ($input['product_id'] ?? '')),
            'software_version' => trim((string) ($input['software_version'] ?? '')),
            'software_validation_number' => trim((string) ($input['software_validation_number'] ?? '')),
            'establishment_number' => trim((string) ($input['establishment_number'] ?? 'SEDE')) ?: 'SEDE',
            'series_contingency_indicator' => in_array($input['series_contingency_indicator'] ?? 'N', ['S', 'N'], true)
                ? $input['series_contingency_indicator']
                : 'N',
            'endpoints' => self::normalizeEndpoints((array) ($input['endpoints'] ?? [])),
        ];

        $privateKey = trim((string) ($input['private_key'] ?? ''));
        $values['private_key'] = $privateKey !== '' ? $privateKey : (string) ($before['private_key'] ?? '');

        $password = (string) ($input['password'] ?? '');
        $values['password'] = $password !== '' ? $password : (string) ($before['password'] ?? '');

        return $values;
    }

    private static function normalizeEndpoints(array $endpoints): array
    {
        $normalized = self::DEFAULTS['endpoints'];

        foreach ($normalized as $environment => $keys) {
            foreach ($keys as $key => $default) {
                $normalized[$environment][$key] = trim((string) data_get($endpoints, "{$environment}.{$key}", $default));
            }
        }

        return $normalized;
    }

    private static function mergeSettings(array $base, array $overrides): array
    {
        return array_replace_recursive($base, array_filter($overrides, fn ($value) => $value !== null));
    }

    private static function settingsTableExists(): bool
    {
        try {
            return Schema::hasTable('app_settings');
        } catch (\Throwable) {
            return false;
        }
    }
}
