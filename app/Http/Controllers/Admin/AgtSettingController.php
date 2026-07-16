<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AgtSettings;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AgtSettingController extends Controller
{
    public function edit(): View
    {
        $settings = AgtSettings::current();

        return view('admin.agt.settings', [
            'settings' => $settings,
            'hasInlinePrivateKey' => AgtSettings::hasInlinePrivateKey($settings),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'enabled' => ['nullable', 'boolean'],
            'environment' => ['required', 'in:hml,prd'],
            'timeout' => ['required', 'integer', 'min:1', 'max:180'],
            'connect_timeout' => ['required', 'integer', 'min:1', 'max:60'],
            'log_channel' => ['required', 'string', 'max:80'],
            'debug_jws' => ['nullable', 'boolean'],
            'nif' => ['nullable', 'string', 'max:80'],
            'username' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:255'],
            'private_key' => ['nullable', 'string'],
            'private_key_path' => ['nullable', 'string', 'max:500'],
            'product_id' => ['nullable', 'string', 'max:120'],
            'software_version' => ['nullable', 'string', 'max:80'],
            'software_validation_number' => ['nullable', 'string', 'max:160'],
            'establishment_number' => ['required', 'string', 'max:80'],
            'series_contingency_indicator' => ['required', 'in:S,N'],
            'endpoints.hml.registar_factura' => ['nullable', 'url', 'max:500'],
            'endpoints.hml.consultar_factura' => ['nullable', 'url', 'max:500'],
            'endpoints.hml.obter_estado' => ['nullable', 'url', 'max:500'],
            'endpoints.hml.solicitar_serie' => ['nullable', 'url', 'max:500'],
            'endpoints.hml.listar_series' => ['nullable', 'url', 'max:500'],
            'endpoints.hml.listar_facturas' => ['nullable', 'url', 'max:500'],
            'endpoints.prd.registar_factura' => ['nullable', 'url', 'max:500'],
            'endpoints.prd.consultar_factura' => ['nullable', 'url', 'max:500'],
            'endpoints.prd.obter_estado' => ['nullable', 'url', 'max:500'],
            'endpoints.prd.solicitar_serie' => ['nullable', 'url', 'max:500'],
            'endpoints.prd.listar_series' => ['nullable', 'url', 'max:500'],
            'endpoints.prd.listar_facturas' => ['nullable', 'url', 'max:500'],
        ]);

        $before = AgtSettings::current();
        $after = AgtSettings::update($validated);

        AuditLogger::log('agt_settings_updated', 'AgtSettings', null, [
            'before' => $this->masked($before),
            'after' => $this->masked($after),
        ]);

        return redirect()
            ->route('admin.agt.settings')
            ->with('success', 'Configuracoes AGT atualizadas com sucesso.');
    }

    private function masked(array $settings): array
    {
        foreach (['password', 'private_key'] as $key) {
            if (! empty($settings[$key])) {
                $settings[$key] = '***';
            }
        }

        return $settings;
    }
}
