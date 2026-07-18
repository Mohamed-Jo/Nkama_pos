<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use App\Services\BusinessSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\Process\Process;

class SettingController extends Controller
{
    public function index(): View
    {
        $company = BusinessSettings::company();

        return view('admin.settings.index', [
            'company' => $company,
            'tax' => BusinessSettings::tax(),
            'print' => BusinessSettings::print(),
            'directPrint' => BusinessSettings::directPrint(),
            'invoice' => BusinessSettings::invoice(),
            'logoUrl' => BusinessSettings::logoUrl($company),
            'loginBackgroundUrl' => BusinessSettings::loginBackgroundUrl($company),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'company.name' => ['required', 'string', 'max:255'],
            'company.location' => ['nullable', 'string', 'max:255'],
            'company.nif' => ['nullable', 'string', 'max:80'],
            'company.iban' => ['nullable', 'string', 'max:80'],
            'company.account_number' => ['nullable', 'string', 'max:80'],
            'company.bank_name' => ['nullable', 'string', 'max:120'],
            'company.swift' => ['nullable', 'string', 'max:80'],
            'company.logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'company.login_background' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'company.remove_login_background' => ['nullable', 'boolean'],
            'tax.active' => ['nullable', 'boolean'],
            'tax.value' => ['required', 'numeric', 'min:0', 'max:100'],
            'print.paper_width_mm' => ['required', 'numeric', 'min:58', 'max:100'],
            'print.page_margin_left_mm' => ['required', 'numeric', 'min:0', 'max:20'],
            'print.page_margin_right_mm' => ['required', 'numeric', 'min:0', 'max:20'],
            'print.page_margin_top_mm' => ['required', 'numeric', 'min:0', 'max:20'],
            'print.page_margin_bottom_mm' => ['required', 'numeric', 'min:0', 'max:20'],
            'print.ticket_width_mm' => ['required', 'numeric', 'min:50', 'max:96'],
            'print.ticket_padding_mm' => ['required', 'numeric', 'min:0', 'max:10'],
            'print.font_family' => ['required', 'string', 'max:120'],
            'print.base_font_size_px' => ['required', 'numeric', 'min:8', 'max:14'],
            'print.company_font_size_px' => ['required', 'numeric', 'min:8', 'max:18'],
            'print.content_font_size_px' => ['required', 'numeric', 'min:8', 'max:16'],
            'print.total_font_size_px' => ['required', 'numeric', 'min:9', 'max:18'],
            'print.tax_summary_font_size_px' => ['required', 'numeric', 'min:8', 'max:14'],
            'print.item_product_width_mm' => ['required', 'numeric', 'min:12', 'max:34'],
            'print.item_tax_width_mm' => ['required', 'numeric', 'min:3', 'max:10'],
            'print.item_qty_width_mm' => ['required', 'numeric', 'min:3', 'max:10'],
            'print.item_price_width_mm' => ['required', 'numeric', 'min:8', 'max:22'],
            'print.item_subtotal_width_mm' => ['required', 'numeric', 'min:10', 'max:30'],
            'direct_print.sumatra_path' => ['nullable', 'string', 'max:500'],
            'direct_print.printer_name' => ['nullable', 'string', 'max:255'],
            'invoice.currency' => ['required', 'string', 'max:12'],
            'invoice.exchange_rate' => ['required', 'numeric', 'min:0.000001', 'max:999999999'],
            'invoice.exemption_reason' => ['nullable', 'string', 'max:255'],
            'invoice.commercial_discount' => ['required', 'numeric', 'min:0', 'max:100'],
            'invoice.payment_condition' => ['nullable', 'string', 'max:120'],
            'invoice.due_days' => ['required', 'integer', 'min:0', 'max:3650'],
            'invoice.show_agt_qr' => ['nullable', 'boolean'],
        ]);

        $before = [
            'company' => BusinessSettings::company(),
            'tax' => BusinessSettings::tax(),
            'print' => BusinessSettings::print(),
            'direct_print' => BusinessSettings::directPrint(),
            'invoice' => BusinessSettings::invoice(),
        ];

        $company = array_merge($before['company'], $validated['company'] ?? []);

        if ($request->hasFile('company.logo')) {
            if (!empty($before['company']['logo_path'])) {
                Storage::disk('public')->delete($before['company']['logo_path']);
            }

            $company['logo_path'] = $request->file('company.logo')->store('company', 'public');
        }

        if ($request->boolean('company.remove_login_background') && !empty($before['company']['login_background_path'])) {
            Storage::disk('public')->delete($before['company']['login_background_path']);
            $company['login_background_path'] = '';
        }

        if ($request->hasFile('company.login_background')) {
            if (!empty($before['company']['login_background_path'])) {
                Storage::disk('public')->delete($before['company']['login_background_path']);
            }

            $company['login_background_path'] = $request->file('company.login_background')->store('company', 'public');
        }

        $after = [
            'company' => BusinessSettings::updateCompany($company),
            'tax' => BusinessSettings::updateTax($validated['tax'] ?? []),
            'print' => BusinessSettings::updatePrint($validated['print'] ?? []),
            'direct_print' => BusinessSettings::updateDirectPrint($validated['direct_print'] ?? []),
            'invoice' => BusinessSettings::updateInvoice($validated['invoice'] ?? []),
        ];

        AuditLogger::log('business_settings_updated', 'BusinessSettings', null, [
            'before' => $before,
            'after' => $after,
        ]);

        return redirect()
            ->route('admin.settings.index')
            ->with('success', 'Configuracoes atualizadas com sucesso.');
    }

    public function printingDiscovery(): JsonResponse
    {
        $directPrint = BusinessSettings::directPrint();

        return response()->json([
            'success' => true,
            'sumatra_paths' => $this->detectSumatraPaths($directPrint),
            'printers' => $this->detectWindowsPrinters(),
        ]);
    }

    private function detectSumatraPaths(array $directPrint): array
    {
        $candidates = array_filter(array_unique([
            $directPrint['sumatra_path'] ?? null,
            config('printing.sumatra_path'),
            env('LOCALAPPDATA') ? env('LOCALAPPDATA') . '\\SumatraPDF\\SumatraPDF.exe' : null,
            env('USERPROFILE') ? env('USERPROFILE') . '\\AppData\\Local\\SumatraPDF\\SumatraPDF.exe' : null,
            'C:\\Users\\Algardata\\AppData\\Local\\SumatraPDF\\SumatraPDF.exe',
            'C:\\Program Files\\SumatraPDF\\SumatraPDF.exe',
            'C:\\Program Files (x86)\\SumatraPDF\\SumatraPDF.exe',
        ]));

        return array_values(array_filter($candidates, fn ($path) => is_string($path) && is_file($path)));
    }

    private function detectWindowsPrinters(): array
    {
        if (PHP_OS_FAMILY !== 'Windows') {
            return [];
        }

        try {
            $process = new Process([
                'powershell',
                '-NoProfile',
                '-ExecutionPolicy',
                'Bypass',
                '-Command',
                'Get-CimInstance Win32_Printer | Select-Object Name,Default | ConvertTo-Json -Compress',
            ]);
            $process->setTimeout(6);
            $process->run();

            if (! $process->isSuccessful()) {
                return [];
            }

            $decoded = json_decode(trim($process->getOutput()), true);

            if (! is_array($decoded)) {
                return [];
            }

            if (array_key_exists('Name', $decoded)) {
                $decoded = [$decoded];
            }

            return collect($decoded)
                ->filter(fn ($printer) => is_array($printer) && ! empty($printer['Name']))
                ->map(fn ($printer) => [
                    'name' => (string) $printer['Name'],
                    'default' => (bool) ($printer['Default'] ?? false),
                ])
                ->sortByDesc('default')
                ->values()
                ->all();
        } catch (\Throwable) {
            return [];
        }
    }
}
