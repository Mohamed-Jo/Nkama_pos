@extends('layouts.admin')

@section('page-title', 'Configuracoes AGT')

@section('content')
    @php
        $endpointLabels = [
            'registar_factura' => 'Registar factura',
            'consultar_factura' => 'Consultar factura',
            'obter_estado' => 'Obter estado',
            'solicitar_serie' => 'Solicitar serie',
            'listar_series' => 'Listar series',
            'listar_facturas' => 'Listar facturas',
        ];
    @endphp

    <style>
        .agt-settings-actions { display:flex; flex-wrap:wrap; gap:10px; justify-content:space-between; margin-bottom:14px; }
        .agt-settings-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(240px,1fr)); gap:14px; }
        .agt-settings-panel { background:var(--card); border:1px solid var(--border); border-radius:8px; margin-bottom:16px; padding:16px; }
        .agt-settings-panel h2 { color:var(--text); font-size:17px; font-weight:900; margin:0 0 14px; }
        .agt-field { display:block; font-weight:800; margin-bottom:10px; }
        .agt-field span { color:var(--muted); display:block; font-size:12px; margin-bottom:6px; text-transform:uppercase; }
        .agt-field input, .agt-field select, .agt-field textarea {
            background:var(--input-bg); border:1px solid var(--border); border-radius:8px; color:var(--input-text);
            min-height:42px; padding:10px; width:100%;
        }
        .agt-field textarea { font-family:ui-monospace, SFMono-Regular, Consolas, monospace; min-height:120px; resize:vertical; }
        .agt-check-row { align-items:center; display:flex; gap:10px; font-weight:900; margin:8px 0 2px; }
        .agt-check-row input { height:18px; width:18px; }
        .agt-help { color:var(--muted); font-size:12px; line-height:1.5; margin-top:6px; }
        .agt-save { background:#f97316; border:0; border-radius:8px; color:#111827; cursor:pointer; font-weight:900; padding:11px 14px; }
        .agt-link { background:var(--card); border:1px solid var(--border); border-radius:8px; color:var(--text); display:inline-flex; font-weight:900; padding:10px 12px; text-decoration:none; }
        .agt-alert { border-radius:8px; margin-bottom:14px; padding:12px; }
        .agt-alert.success { background:rgba(16,185,129,.15); border:1px solid #10b981; color:#86efac; }
        .agt-alert.error { background:rgba(239,68,68,.12); border:1px solid #ef4444; color:#fecaca; }
    </style>

    @if(session('success'))
        <div class="agt-alert success">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="agt-alert error">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <div class="agt-settings-actions">
        <a class="agt-link" href="{{ route('admin.agt.index') }}">Voltar ao painel AGT</a>
    </div>

    <form method="POST" action="{{ route('admin.agt.settings.update') }}">
        @csrf
        @method('PUT')

        <div class="agt-settings-panel">
            <h2>Integracao</h2>
            <div class="agt-settings-grid">
                <label class="agt-field">
                    <span>Ambiente</span>
                    <select name="environment" required>
                        <option value="hml" @selected(old('environment', $settings['environment']) === 'hml')>Homologacao</option>
                        <option value="prd" @selected(old('environment', $settings['environment']) === 'prd')>Producao</option>
                    </select>
                </label>
                <label class="agt-field">
                    <span>NIF</span>
                    <input type="text" name="nif" value="{{ old('nif', $settings['nif']) }}">
                </label>
                <label class="agt-field">
                    <span>Utilizador</span>
                    <input type="text" name="username" value="{{ old('username', $settings['username']) }}" autocomplete="off">
                </label>
                <label class="agt-field">
                    <span>Nova password</span>
                    <input type="password" name="password" value="" autocomplete="new-password" placeholder="Preencher apenas para alterar">
                    <div class="agt-help">Por seguranca, a password atual nao e apresentada.</div>
                </label>
                <label class="agt-field">
                    <span>Timeout</span>
                    <input type="number" name="timeout" min="1" max="180" value="{{ old('timeout', $settings['timeout']) }}" required>
                </label>
                <label class="agt-field">
                    <span>Connect timeout</span>
                    <input type="number" name="connect_timeout" min="1" max="60" value="{{ old('connect_timeout', $settings['connect_timeout']) }}" required>
                </label>
                <label class="agt-field">
                    <span>Canal de log</span>
                    <input type="text" name="log_channel" value="{{ old('log_channel', $settings['log_channel']) }}" required>
                </label>
            </div>
            <label class="agt-check-row">
                <input type="checkbox" name="enabled" value="1" @checked(old('enabled', $settings['enabled']))>
                Envio AGT ativo
            </label>
            <label class="agt-check-row">
                <input type="checkbox" name="debug_jws" value="1" @checked(old('debug_jws', $settings['debug_jws']))>
                Registar debug da assinatura JWS
            </label>
        </div>

        <div class="agt-settings-panel">
            <h2>Software certificado</h2>
            <div class="agt-settings-grid">
                <label class="agt-field">
                    <span>Product ID</span>
                    <input type="text" name="product_id" value="{{ old('product_id', $settings['product_id']) }}">
                </label>
                <label class="agt-field">
                    <span>Versao</span>
                    <input type="text" name="software_version" value="{{ old('software_version', $settings['software_version']) }}">
                </label>
                <label class="agt-field">
                    <span>Numero de validacao</span>
                    <input type="text" name="software_validation_number" value="{{ old('software_validation_number', $settings['software_validation_number']) }}">
                </label>
                <label class="agt-field">
                    <span>Estabelecimento</span>
                    <input type="text" name="establishment_number" value="{{ old('establishment_number', $settings['establishment_number']) }}" required>
                </label>
                <label class="agt-field">
                    <span>Serie em contingencia</span>
                    <select name="series_contingency_indicator" required>
                        <option value="N" @selected(old('series_contingency_indicator', $settings['series_contingency_indicator']) === 'N')>Nao</option>
                        <option value="S" @selected(old('series_contingency_indicator', $settings['series_contingency_indicator']) === 'S')>Sim</option>
                    </select>
                </label>
            </div>
        </div>

        <div class="agt-settings-panel">
            <h2>Chave privada</h2>
            <div class="agt-settings-grid">
                <label class="agt-field">
                    <span>Caminho do ficheiro</span>
                    <input type="text" name="private_key_path" value="{{ old('private_key_path', $settings['private_key_path']) }}">
                </label>
                <label class="agt-field">
                    <span>Nova chave inline</span>
                    <textarea name="private_key" placeholder="{{ $hasInlinePrivateKey ? 'Chave inline ja guardada. Preencha apenas para substituir.' : 'Cole a chave PEM apenas se nao usar ficheiro.' }}">{{ old('private_key') }}</textarea>
                    <div class="agt-help">Por seguranca, a chave inline atual nao e apresentada.</div>
                </label>
            </div>
        </div>

        @foreach(['hml' => 'Homologacao', 'prd' => 'Producao'] as $environment => $label)
            <div class="agt-settings-panel">
                <h2>Endpoints {{ $label }}</h2>
                <div class="agt-settings-grid">
                    @foreach($endpointLabels as $key => $endpointLabel)
                        <label class="agt-field">
                            <span>{{ $endpointLabel }}</span>
                            <input type="url" name="endpoints[{{ $environment }}][{{ $key }}]" value="{{ old("endpoints.{$environment}.{$key}", data_get($settings, "endpoints.{$environment}.{$key}")) }}">
                        </label>
                    @endforeach
                </div>
            </div>
        @endforeach

        <button class="agt-save" type="submit">Guardar configuracoes AGT</button>
    </form>
@endsection
