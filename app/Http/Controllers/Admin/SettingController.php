<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use App\Services\BusinessSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function index(): View
    {
        $company = BusinessSettings::company();

        return view('admin.settings.index', [
            'company' => $company,
            'tax' => BusinessSettings::tax(),
            'logoUrl' => BusinessSettings::logoUrl($company),
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
            'company.swift' => ['nullable', 'string', 'max:80'],
            'company.logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'tax.active' => ['nullable', 'boolean'],
            'tax.value' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        $before = [
            'company' => BusinessSettings::company(),
            'tax' => BusinessSettings::tax(),
        ];

        $company = array_merge($before['company'], $validated['company'] ?? []);

        if ($request->hasFile('company.logo')) {
            if (!empty($before['company']['logo_path'])) {
                Storage::disk('public')->delete($before['company']['logo_path']);
            }

            $company['logo_path'] = $request->file('company.logo')->store('company', 'public');
        }

        $after = [
            'company' => BusinessSettings::updateCompany($company),
            'tax' => BusinessSettings::updateTax($validated['tax'] ?? []),
        ];

        AuditLogger::log('business_settings_updated', 'BusinessSettings', null, [
            'before' => $before,
            'after' => $after,
        ]);

        return redirect()
            ->route('admin.settings.index')
            ->with('success', 'Configuracoes atualizadas com sucesso.');
    }
}
