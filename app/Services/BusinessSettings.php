<?php

namespace App\Services;

use App\Models\AppSetting;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class BusinessSettings
{
    public const COMPANY_DEFAULTS = [
        'name' => '',
        'location' => '',
        'nif' => '',
        'iban' => '',
        'account_number' => '',
        'swift' => '',
        'logo_path' => '',
    ];

    public const TAX_DEFAULTS = [
        'active' => false,
        'value' => 14,
    ];

    public static function company(): array
    {
        return self::setting('company_profile', self::COMPANY_DEFAULTS);
    }

    public static function tax(): array
    {
        return self::setting('tax_settings', self::TAX_DEFAULTS);
    }

    public static function updateCompany(array $company): array
    {
        $values = array_merge(self::COMPANY_DEFAULTS, [
            'name' => trim((string) ($company['name'] ?? '')),
            'location' => trim((string) ($company['location'] ?? '')),
            'nif' => trim((string) ($company['nif'] ?? '')),
            'iban' => trim((string) ($company['iban'] ?? '')),
            'account_number' => trim((string) ($company['account_number'] ?? '')),
            'swift' => trim((string) ($company['swift'] ?? '')),
            'logo_path' => (string) ($company['logo_path'] ?? ''),
        ]);

        AppSetting::updateOrCreate(
            ['key' => 'company_profile'],
            ['value' => $values]
        );

        return $values;
    }

    public static function updateTax(array $tax): array
    {
        $values = [
            'active' => (bool) ($tax['active'] ?? false),
            'value' => round(max(0, (float) ($tax['value'] ?? self::TAX_DEFAULTS['value'])), 2),
        ];

        AppSetting::updateOrCreate(
            ['key' => 'tax_settings'],
            ['value' => $values]
        );

        return $values;
    }

    public static function logoUrl(?array $company = null): ?string
    {
        $company ??= self::company();
        $path = $company['logo_path'] ?? '';

        if ($path === '') {
            return null;
        }

        return Storage::disk('public')->url($path);
    }

    public static function splitGrossTotal(float $grossTotal, ?float $taxRate = null): array
    {
        if ($taxRate === null) {
            $tax = self::tax();
            $taxRate = (bool) ($tax['active'] ?? false) ? (float) ($tax['value'] ?? 0) : 0;
        }

        $rate = max(0, (float) $taxRate);

        if ($rate <= 0) {
            return [
                'subtotal' => round($grossTotal, 2),
                'tax' => 0.0,
                'tax_rate' => 0.0,
                'total' => round($grossTotal, 2),
            ];
        }

        $taxAmount = round($grossTotal * $rate / (100 + $rate), 2);

        return [
            'subtotal' => round($grossTotal - $taxAmount, 2),
            'tax' => $taxAmount,
            'tax_rate' => round($rate, 2),
            'total' => round($grossTotal, 2),
        ];
    }

    private static function setting(string $key, array $defaults): array
    {
        if (!Schema::hasTable('app_settings')) {
            return $defaults;
        }

        $setting = AppSetting::where('key', $key)->first();

        return array_merge($defaults, $setting?->value ?? []);
    }
}
