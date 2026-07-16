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
        'bank_name' => '',
        'swift' => '',
        'logo_path' => '',
        'login_background_path' => '',
    ];

    public const TAX_DEFAULTS = [
        'active' => false,
        'value' => 14,
    ];

    public const PRINT_DEFAULTS = [
        'paper_width_mm' => 80,
        'page_margin_left_mm' => 0,
        'page_margin_right_mm' => 0,
        'page_margin_top_mm' => 0,
        'page_margin_bottom_mm' => 0,
        'ticket_width_mm' => 76,
        'ticket_padding_mm' => 5,
        'font_family' => 'Arial, Helvetica, sans-serif',
        'base_font_size_px' => 10,
        'company_font_size_px' => 12,
        'content_font_size_px' => 11,
        'total_font_size_px' => 12,
        'tax_summary_font_size_px' => 10,
        'item_product_width_mm' => 20,
        'item_tax_width_mm' => 5,
        'item_qty_width_mm' => 5,
        'item_price_width_mm' => 12,
        'item_subtotal_width_mm' => 20,
    ];

    public const DIRECT_PRINT_DEFAULTS = [
        'sumatra_path' => '',
        'printer_name' => '',
    ];

    public const INVOICE_DEFAULTS = [
        'currency' => 'AOA',
        'exchange_rate' => 1,
        'exemption_reason' => '',
        'commercial_discount' => 0,
        'payment_condition' => 'Pronto pagamento',
        'due_days' => 0,
    ];

    public static function company(): array
    {
        return self::setting('company_profile', self::COMPANY_DEFAULTS);
    }

    public static function tax(): array
    {
        return self::setting('tax_settings', self::TAX_DEFAULTS);
    }

    public static function print(): array
    {
        return self::setting('print_settings', self::PRINT_DEFAULTS);
    }

    public static function directPrint(): array
    {
        return self::setting('direct_print_settings', [
            'sumatra_path' => (string) config('printing.sumatra_path', ''),
            'printer_name' => (string) config('printing.printer_name', ''),
        ] + self::DIRECT_PRINT_DEFAULTS);
    }

    public static function invoice(): array
    {
        return self::setting('invoice_settings', self::INVOICE_DEFAULTS);
    }

    public static function updateCompany(array $company): array
    {
        $values = array_merge(self::COMPANY_DEFAULTS, [
            'name' => trim((string) ($company['name'] ?? '')),
            'location' => trim((string) ($company['location'] ?? '')),
            'nif' => trim((string) ($company['nif'] ?? '')),
            'iban' => trim((string) ($company['iban'] ?? '')),
            'account_number' => trim((string) ($company['account_number'] ?? '')),
            'bank_name' => trim((string) ($company['bank_name'] ?? '')),
            'swift' => trim((string) ($company['swift'] ?? '')),
            'logo_path' => (string) ($company['logo_path'] ?? ''),
            'login_background_path' => (string) ($company['login_background_path'] ?? ''),
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

    public static function updatePrint(array $print): array
    {
        $fontFamily = trim((string) ($print['font_family'] ?? self::PRINT_DEFAULTS['font_family']));

        if ($fontFamily === '') {
            $fontFamily = self::PRINT_DEFAULTS['font_family'];
        }

        $values = [
            'paper_width_mm' => self::clampNumber($print['paper_width_mm'] ?? null, 58, 100, self::PRINT_DEFAULTS['paper_width_mm']),
            'page_margin_left_mm' => self::clampNumber($print['page_margin_left_mm'] ?? null, 0, 20, self::PRINT_DEFAULTS['page_margin_left_mm']),
            'page_margin_right_mm' => self::clampNumber($print['page_margin_right_mm'] ?? null, 0, 20, self::PRINT_DEFAULTS['page_margin_right_mm']),
            'page_margin_top_mm' => self::clampNumber($print['page_margin_top_mm'] ?? null, 0, 20, self::PRINT_DEFAULTS['page_margin_top_mm']),
            'page_margin_bottom_mm' => self::clampNumber($print['page_margin_bottom_mm'] ?? null, 0, 20, self::PRINT_DEFAULTS['page_margin_bottom_mm']),
            'ticket_width_mm' => self::clampNumber($print['ticket_width_mm'] ?? null, 50, 96, self::PRINT_DEFAULTS['ticket_width_mm']),
            'ticket_padding_mm' => self::clampNumber($print['ticket_padding_mm'] ?? null, 0, 10, self::PRINT_DEFAULTS['ticket_padding_mm']),
            'font_family' => $fontFamily,
            'base_font_size_px' => self::clampNumber($print['base_font_size_px'] ?? null, 8, 14, self::PRINT_DEFAULTS['base_font_size_px']),
            'company_font_size_px' => self::clampNumber($print['company_font_size_px'] ?? null, 8, 18, self::PRINT_DEFAULTS['company_font_size_px']),
            'content_font_size_px' => self::clampNumber($print['content_font_size_px'] ?? null, 8, 16, self::PRINT_DEFAULTS['content_font_size_px']),
            'total_font_size_px' => self::clampNumber($print['total_font_size_px'] ?? null, 9, 18, self::PRINT_DEFAULTS['total_font_size_px']),
            'tax_summary_font_size_px' => self::clampNumber($print['tax_summary_font_size_px'] ?? null, 8, 14, self::PRINT_DEFAULTS['tax_summary_font_size_px']),
            'item_product_width_mm' => self::clampNumber($print['item_product_width_mm'] ?? null, 12, 34, self::PRINT_DEFAULTS['item_product_width_mm']),
            'item_tax_width_mm' => self::clampNumber($print['item_tax_width_mm'] ?? null, 3, 10, self::PRINT_DEFAULTS['item_tax_width_mm']),
            'item_qty_width_mm' => self::clampNumber($print['item_qty_width_mm'] ?? null, 3, 10, self::PRINT_DEFAULTS['item_qty_width_mm']),
            'item_price_width_mm' => self::clampNumber($print['item_price_width_mm'] ?? null, 8, 22, self::PRINT_DEFAULTS['item_price_width_mm']),
            'item_subtotal_width_mm' => self::clampNumber($print['item_subtotal_width_mm'] ?? null, 10, 30, self::PRINT_DEFAULTS['item_subtotal_width_mm']),
        ];

        AppSetting::updateOrCreate(
            ['key' => 'print_settings'],
            ['value' => $values]
        );

        return $values;
    }

    public static function updateDirectPrint(array $directPrint): array
    {
        $values = [
            'sumatra_path' => trim((string) ($directPrint['sumatra_path'] ?? '')),
            'printer_name' => trim((string) ($directPrint['printer_name'] ?? '')),
        ];

        AppSetting::updateOrCreate(
            ['key' => 'direct_print_settings'],
            ['value' => $values]
        );

        return $values;
    }

    public static function updateInvoice(array $invoice): array
    {
        $currency = strtoupper(trim((string) ($invoice['currency'] ?? self::INVOICE_DEFAULTS['currency'])));

        if ($currency === '') {
            $currency = self::INVOICE_DEFAULTS['currency'];
        }

        $values = [
            'currency' => substr($currency, 0, 12),
            'exchange_rate' => self::clampNumber($invoice['exchange_rate'] ?? null, 0.000001, 999999999, self::INVOICE_DEFAULTS['exchange_rate'], 6),
            'exemption_reason' => trim((string) ($invoice['exemption_reason'] ?? '')),
            'commercial_discount' => self::clampNumber($invoice['commercial_discount'] ?? null, 0, 100, self::INVOICE_DEFAULTS['commercial_discount']),
            'payment_condition' => trim((string) ($invoice['payment_condition'] ?? self::INVOICE_DEFAULTS['payment_condition'])),
            'due_days' => (int) self::clampNumber($invoice['due_days'] ?? null, 0, 3650, self::INVOICE_DEFAULTS['due_days']),
        ];

        AppSetting::updateOrCreate(
            ['key' => 'invoice_settings'],
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

        return '/storage/' . ltrim($path, '/');
    }

    public static function logoDataUri(?array $company = null): ?string
    {
        $company ??= self::company();
        $path = $company['logo_path'] ?? '';

        if ($path === '') {
            return null;
        }

        $fullPath = Storage::disk('public')->path($path);

        if (! is_file($fullPath)) {
            return null;
        }

        $mimeType = mime_content_type($fullPath) ?: 'image/png';

        return 'data:' . $mimeType . ';base64,' . base64_encode(file_get_contents($fullPath));
    }

    public static function loginBackgroundUrl(?array $company = null): ?string
    {
        $company ??= self::company();
        $path = $company['login_background_path'] ?? '';

        if ($path === '') {
            return null;
        }

        return '/storage/' . ltrim($path, '/');
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

    private static function clampNumber(mixed $value, float $min, float $max, float $default, int $precision = 2): float
    {
        $number = is_numeric($value) ? (float) $value : $default;

        return round(min(max($number, $min), $max), $precision);
    }
}
