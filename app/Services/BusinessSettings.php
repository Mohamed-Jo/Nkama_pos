<?php

namespace App\Services;

use App\Models\AppSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class BusinessSettings
{
    private static array $settingsCache = [];
    private static array $logoDataUriCache = [];
    private static array $agtQrSvgCache = [];
    private static ?bool $hasAppSettingsTable = null;

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
        'show_agt_qr' => true,
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
        self::forgetSetting('company_profile');

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
        self::forgetSetting('tax_settings');

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
        self::forgetSetting('print_settings');

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
        self::forgetSetting('direct_print_settings');

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
            'show_agt_qr' => (bool) ($invoice['show_agt_qr'] ?? false),
        ];

        AppSetting::updateOrCreate(
            ['key' => 'invoice_settings'],
            ['value' => $values]
        );
        self::forgetSetting('invoice_settings');

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

        $sourcePath = self::optimizedLogoPath($fullPath) ?? $fullPath;
        $cacheKey = $sourcePath . '|' . filemtime($sourcePath);

        if (array_key_exists($cacheKey, self::$logoDataUriCache)) {
            return self::$logoDataUriCache[$cacheKey];
        }

        $mimeType = mime_content_type($sourcePath) ?: 'image/png';

        return self::$logoDataUriCache[$cacheKey] = 'data:' . $mimeType . ';base64,' . base64_encode(file_get_contents($sourcePath));
    }

    public static function agtDocumentUrl(?array $company, ?string $documentNumber): ?string
    {
        $company ??= self::company();
        $nif = trim((string) ($company['nif'] ?? config('agt.nif', '')));
        $document = trim((string) $documentNumber);

        if ($nif === '' || $document === '') {
            return null;
        }

        $environment = strtolower((string) config('agt.environment', 'hml'));
        $baseUrl = $environment === 'prd'
            ? 'https://quiosqueagt.minfin.gov.ao/facturacao-eletronica/consultar-fe'
            : 'https://quiosqueagt.hml.minfin.gov.ao/facturacao-eletronica/consultar-fe';

        return $baseUrl . '?' . http_build_query([
            'emissor' => preg_replace('/\s+/', '', $nif),
            'document' => $document,
        ], '', '&', PHP_QUERY_RFC3986);
    }

    public static function agtQrSvg(?array $company, ?string $documentNumber, int $size = 120): ?string
    {
        $url = self::agtDocumentUrl($company, $documentNumber);

        if (! $url || ! class_exists(\SimpleSoftwareIO\QrCode\Facades\QrCode::class)) {
            return null;
        }

        $cacheKey = 'agt_qr_svg:' . sha1($url . '|' . $size);

        if (array_key_exists($cacheKey, self::$agtQrSvgCache)) {
            return self::$agtQrSvgCache[$cacheKey];
        }

        try {
            return self::$agtQrSvgCache[$cacheKey] = Cache::remember($cacheKey, now()->addYear(), function () use ($url, $size) {
                $svg = (string) \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')
                    ->size($size)
                    ->margin(1)
                    ->errorCorrection('M')
                    ->generate($url);

                return preg_replace('/<\?xml[^>]*\?>\s*/', '', $svg) ?: $svg;
            });
        } catch (\Throwable) {
            try {
                $svg = (string) \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')
                    ->size($size)
                    ->margin(1)
                    ->errorCorrection('M')
                    ->generate($url);

                return self::$agtQrSvgCache[$cacheKey] = preg_replace('/<\?xml[^>]*\?>\s*/', '', $svg) ?: $svg;
            } catch (\Throwable) {
                return null;
            }
        }
    }

    public static function agtQrImage(?array $company, ?string $documentNumber, int $size = 120): ?string
    {
        $svg = self::agtQrSvg($company, $documentNumber, $size);

        return $svg ? 'data:image/svg+xml;base64,' . base64_encode($svg) : null;
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

    public static function amountToWords(float $amount, string $currency = 'AOA'): string
    {
        $currency = strtoupper(trim($currency)) ?: 'AOA';
        $integer = (int) floor(abs($amount));
        $cents = (int) round((abs($amount) - $integer) * 100);
        $mainCurrency = $currency === 'AOA' ? ($integer === 1 ? 'kwanza' : 'kwanzas') : $currency;
        $words = ucfirst(self::numberToWords($integer)) . ' ' . $mainCurrency;

        if ($cents > 0) {
            $words .= ' e ' . self::numberToWords($cents) . ' ' . ($cents === 1 ? 'centimo' : 'centimos');
        }

        return $words;
    }

    private static function numberToWords(int $number): string
    {
        $units = ['', 'um', 'dois', 'tres', 'quatro', 'cinco', 'seis', 'sete', 'oito', 'nove', 'dez', 'onze', 'doze', 'treze', 'catorze', 'quinze', 'dezasseis', 'dezassete', 'dezoito', 'dezanove'];
        $tens = ['', '', 'vinte', 'trinta', 'quarenta', 'cinquenta', 'sessenta', 'setenta', 'oitenta', 'noventa'];
        $hundreds = ['', 'cento', 'duzentos', 'trezentos', 'quatrocentos', 'quinhentos', 'seiscentos', 'setecentos', 'oitocentos', 'novecentos'];

        if ($number === 0) {
            return 'zero';
        }

        if ($number === 100) {
            return 'cem';
        }

        if ($number < 20) {
            return $units[$number];
        }

        if ($number < 100) {
            $ten = intdiv($number, 10);
            $rest = $number % 10;

            return $tens[$ten] . ($rest ? ' e ' . $units[$rest] : '');
        }

        if ($number < 1000) {
            $hundred = intdiv($number, 100);
            $rest = $number % 100;

            return $hundreds[$hundred] . ($rest ? ' e ' . self::numberToWords($rest) : '');
        }

        if ($number < 1000000) {
            $thousands = intdiv($number, 1000);
            $rest = $number % 1000;
            $prefix = $thousands === 1 ? 'mil' : self::numberToWords($thousands) . ' mil';

            return $prefix . ($rest ? ' e ' . self::numberToWords($rest) : '');
        }

        $millions = intdiv($number, 1000000);
        $rest = $number % 1000000;
        $prefix = $millions === 1 ? 'um milhao' : self::numberToWords($millions) . ' milhoes';

        return $prefix . ($rest ? ' e ' . self::numberToWords($rest) : '');
    }
    private static function optimizedLogoPath(string $fullPath): ?string
    {
        if (! extension_loaded('gd')) {
            return null;
        }

        $info = @getimagesize($fullPath);

        if (! $info) {
            return null;
        }

        [$width, $height] = $info;
        $maxWidth = 420;
        $maxHeight = 180;

        if ($width <= $maxWidth && $height <= $maxHeight) {
            return $fullPath;
        }

        $cacheDir = storage_path('app/pdf-assets');

        if (! is_dir($cacheDir)) {
            mkdir($cacheDir, 0775, true);
        }

        $cachePath = $cacheDir . DIRECTORY_SEPARATOR . 'logo-' . sha1($fullPath . '|' . filemtime($fullPath)) . '.png';

        if (is_file($cachePath)) {
            return $cachePath;
        }

        $contents = @file_get_contents($fullPath);
        $source = $contents ? @imagecreatefromstring($contents) : false;

        if (! $source) {
            return null;
        }

        $ratio = min($maxWidth / $width, $maxHeight / $height, 1);
        $targetWidth = max(1, (int) round($width * $ratio));
        $targetHeight = max(1, (int) round($height * $ratio));
        $target = imagecreatetruecolor($targetWidth, $targetHeight);
        imagealphablending($target, false);
        imagesavealpha($target, true);
        $transparent = imagecolorallocatealpha($target, 255, 255, 255, 127);
        imagefilledrectangle($target, 0, 0, $targetWidth, $targetHeight, $transparent);
        imagecopyresampled($target, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);
        imagepng($target, $cachePath, 6);
        imagedestroy($source);
        imagedestroy($target);

        return is_file($cachePath) ? $cachePath : null;
    }
    private static function setting(string $key, array $defaults): array
    {
        if (array_key_exists($key, self::$settingsCache)) {
            return self::$settingsCache[$key];
        }

        self::$hasAppSettingsTable ??= Schema::hasTable('app_settings');

        if (! self::$hasAppSettingsTable) {
            return $defaults;
        }

        $setting = AppSetting::where('key', $key)->first();

        return self::$settingsCache[$key] = array_merge($defaults, $setting?->value ?? []);
    }

    private static function forgetSetting(string $key): void
    {
        unset(self::$settingsCache[$key]);
    }

    private static function clampNumber(mixed $value, float $min, float $max, float $default, int $precision = 2): float
    {
        $number = is_numeric($value) ? (float) $value : $default;

        return round(min(max($number, $min), $max), $precision);
    }
}
