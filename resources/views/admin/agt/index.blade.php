@extends('layouts.admin')

@section('page-title', 'Faturacao Eletronica AGT')

@section('content')
    @php
        $documentStatusLabels = [
            'ready' => 'Preparada',
            'pending' => 'Pendente',
            'submitted' => 'Validada',
            'failed' => 'Rejeitada',
        ];

        $seriesStatusLabels = [
            'accepted' => 'Aceite',
            'rejected' => 'Rejeitada',
        ];

        $seriesStatusClass = fn ($status) => $status === 'accepted' ? 'submitted' : ($status === 'rejected' ? 'failed' : '');
    @endphp
    <style>
        .agt-page { color:var(--text); }
        .agt-topbar { align-items:center; display:flex; flex-wrap:wrap; gap:10px; justify-content:space-between; margin-bottom:14px; }
        .agt-endpoint { color:var(--muted); font-size:12px; line-height:1.4; }
        .agt-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(140px,1fr)); gap:10px; margin-bottom:14px; }
        .agt-card { background:var(--card); border:1px solid var(--border); border-radius:8px; padding:12px; }
        .agt-card span { color:var(--muted); display:block; font-size:11px; font-weight:800; text-transform:uppercase; }
        .agt-card strong { color:var(--text); display:block; font-size:20px; line-height:1.1; margin-top:5px; }
        .agt-panel, .agt-table { background:var(--card); border:1px solid var(--border); border-radius:8px; margin-bottom:16px; overflow:hidden; }
        .agt-panel { padding:12px; }
        .agt-panel-title { color:var(--text); font-size:15px; font-weight:900; margin:0 0 10px; }
        .agt-section-title { color:var(--text); font-size:16px; font-weight:900; margin:18px 0 9px; }
        .agt-actions-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(260px,1fr)); gap:10px; }
        .agt-inline-form { align-items:end; display:grid; gap:8px; grid-template-columns:minmax(86px,.8fr) minmax(86px,.8fr) auto; }
        .agt-field { color:var(--text); display:block; font-size:12px; font-weight:800; }
        .agt-field select, .agt-field input {
            background:var(--input-bg); border:1px solid var(--border); border-radius:7px; color:var(--input-text);
            font-size:13px; height:34px; margin-top:4px; padding:6px 9px; width:100%;
        }
        .agt-tabs { display:flex; flex-wrap:wrap; gap:7px; margin-bottom:12px; }
        .agt-tab { background:var(--card); border:1px solid var(--border); border-radius:8px; color:var(--text); display:inline-flex; font-size:13px; font-weight:800; min-height:34px; padding:7px 11px; text-decoration:none; }
        .agt-tab.active { border-color:var(--primary); color:var(--primary); }
        .agt-table { overflow-x:auto; }
        .agt-table table { border-collapse:collapse; min-width:760px; width:100%; }
        .agt-table th, .agt-table td { border-bottom:1px solid var(--border); padding:9px 10px; text-align:left; vertical-align:middle; }
        .agt-table th { color:var(--muted); font-size:10px; letter-spacing:0; text-transform:uppercase; white-space:nowrap; }
        .agt-table td { color:var(--text); font-size:13px; }
        .agt-muted { color:var(--muted); font-size:12px; }
        .agt-mono { font-family:ui-monospace, SFMono-Regular, Consolas, monospace; font-size:12px; }
        .agt-wrap { max-width:360px; word-break:break-word; }
        .agt-status { background:rgba(100,116,139,.14); border-radius:999px; color:var(--text); display:inline-flex; font-size:11px; font-weight:900; line-height:1; padding:5px 8px; white-space:nowrap; }
        .agt-status.ready { background:rgba(14,165,233,.14); color:#0369a1; }
        .agt-status.pending { background:rgba(245,158,11,.15); color:#92400e; }
        .agt-status.submitted { background:rgba(22,163,74,.14); color:#166534; }
        .agt-status.failed { background:rgba(220,38,38,.13); color:#991b1b; }
        .agt-btn { align-items:center; background:#f97316; border:0; border-radius:7px; color:#111827; cursor:pointer; display:inline-flex; font-size:12px; font-weight:900; justify-content:center; min-height:34px; padding:7px 10px; white-space:nowrap; }
        .agt-alert { border-radius:8px; font-size:13px; margin-bottom:12px; padding:10px 12px; }
        .agt-alert.success { background:rgba(16,185,129,.12); border:1px solid rgba(16,185,129,.35); color:#047857; }
        .agt-alert.error { background:rgba(239,68,68,.10); border:1px solid rgba(239,68,68,.35); color:#b91c1c; }
        .agt-pre { background:var(--input-bg); border:1px solid var(--border); border-radius:8px; color:var(--text); font-size:12px; margin:0; max-height:320px; overflow:auto; padding:10px; white-space:pre-wrap; word-break:break-word; }
        @media (max-width:640px) {
            .agt-inline-form { grid-template-columns:1fr; }
            .agt-topbar { align-items:stretch; }
            .agt-tab, .agt-btn { justify-content:center; width:100%; }
        }
    </style>

    <div class="agt-page">
        @if(session('success'))
            <div class="agt-alert success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="agt-alert error">{{ session('error') }}</div>
        @endif

        <div class="agt-topbar">
            <div class="agt-endpoint">Endpoint: {{ $agtEndpoint ?: 'nao configurado' }} | Ambiente: {{ strtoupper((string) $agtEnvironment) }}</div>
            <a class="agt-tab" href="{{ route('admin.agt.settings') }}">Configuracoes AGT</a>
        </div>

        <div class="agt-grid">
            <div class="agt-card"><span>AGT envio</span><strong>{{ $agtEnabled ? 'Ativo' : 'Inativo' }}</strong></div>
            <div class="agt-card"><span>Preparadas</span><strong>{{ (int) ($counts['ready'] ?? 0) }}</strong></div>
            <div class="agt-card"><span>Pendentes</span><strong>{{ (int) ($counts['pending'] ?? 0) }}</strong></div>
            <div class="agt-card"><span>Validadas</span><strong>{{ (int) ($counts['submitted'] ?? 0) }}</strong></div>
            <div class="agt-card"><span>Rejeitadas</span><strong>{{ (int) ($counts['failed'] ?? 0) }}</strong></div>
        </div>

        <div class="agt-panel">
            <h2 class="agt-panel-title">Solicitar serie AGT</h2>
            <div class="agt-actions-grid">
                <form method="POST" action="{{ route('admin.agt.series.request') }}" class="agt-inline-form">
                    @csrf
                    <label class="agt-field">Tipo
                        <select name="document_type" required>
                            <option value="FR">FR</option>
                            <option value="FT">FT</option>
                            <option value="NC">NC</option>
                        </select>
                    </label>
                    <label class="agt-field">Ano
                        <input type="number" name="year" value="{{ now()->year }}" min="2000" max="2100" required>
                    </label>
                    <button class="agt-btn" type="submit">Solicitar</button>
                </form>

                <form method="POST" action="{{ route('admin.agt.series.list') }}" class="agt-inline-form">
                    @csrf
                    <label class="agt-field">Tipo
                        <select name="document_type" required>
                            <option value="FR">FR</option>
                            <option value="FT">FT</option>
                            <option value="NC">NC</option>
                        </select>
                    </label>
                    <label class="agt-field">Ano
                        <input type="number" name="year" value="{{ now()->year }}" min="2000" max="2100">
                    </label>
                    <button class="agt-btn" type="submit">Listar</button>
                </form>
            </div>
            <div class="agt-muted" style="margin-top:10px;">NIF: {{ $agtNif ?: 'nao configurado' }}</div>
        </div>

        @if(session('agt_series_response'))
            <div class="agt-panel">
                <h2 class="agt-panel-title">Ultima resposta de series AGT</h2>
                <pre class="agt-pre">{{ json_encode(session('agt_series_response'), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
            </div>
        @endif

        <h2 class="agt-section-title">Registo AGT de series</h2>
        <div class="agt-table">
            <table>
                <thead><tr><th>Ambiente</th><th>Tipo</th><th>Serie</th><th>Ano</th><th>Estado</th><th>Pedido</th><th>Erro</th><th>Ultima tentativa</th></tr></thead>
                <tbody>
                    @forelse(($agtSeries ?? collect()) as $agtSerie)
                        <tr>
                            <td>{{ strtoupper((string) $agtSerie->environment) }}</td>
                            <td>{{ $agtSerie->document_type_code }}</td>
                            <td><strong>{{ $agtSerie->series_code }}</strong></td>
                            <td>{{ $agtSerie->series_year }}</td>
                            <td><span class="agt-status {{ $seriesStatusClass($agtSerie->status) }}">{{ $seriesStatusLabels[$agtSerie->status] ?? ($agtSerie->status ?: '-') }}</span></td>
                            <td class="agt-mono">{{ $agtSerie->request_id ?: '-' }}</td>
                            <td class="agt-wrap">{{ $agtSerie->last_error ?: '-' }}</td>
                            <td>{{ $agtSerie->requested_at?->format('d/m/Y H:i') ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="agt-muted">Sem registos AGT de series.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <h2 class="agt-section-title">Series locais</h2>
        <div class="agt-table">
            <table>
                <thead><tr><th>Tipo</th><th>Serie</th><th>Ano</th><th>Sequencia</th><th>Estado AGT</th><th>Ultima solicitacao</th></tr></thead>
                <tbody>
                    @forelse($series as $serie)
                        <tr>
                            <td>{{ $serie->type?->code }}</td>
                            <td><strong>{{ $serie->code }}</strong></td>
                            <td>{{ $serie->year }}</td>
                            <td>{{ $serie->current_number }} / inicio {{ $serie->start_number }}</td>
                            <td><span class="agt-status {{ $seriesStatusClass($serie->agtSeries?->status) }}">{{ $seriesStatusLabels[$serie->agtSeries?->status] ?? 'Nao solicitada' }}</span></td>
                            <td>{{ $serie->agtSeries?->requested_at?->format('d/m/Y H:i') ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="agt-muted">Sem series locais criadas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="agt-tabs">
            @foreach(['ready' => 'Preparadas', 'pending' => 'Pendentes', 'submitted' => 'Validadas', 'failed' => 'Rejeitadas', 'all' => 'Todos'] as $key => $label)
                <a class="agt-tab {{ $status === $key ? 'active' : '' }}" href="{{ route('admin.agt.index', ['status' => $key]) }}">{{ $label }}</a>
            @endforeach
        </div>

        <h2 class="agt-section-title" style="margin-top:0;">Documentos AGT</h2>
        <div class="agt-table">
            <table>
                <thead><tr><th>Documento</th><th>Estado</th><th>Hash Payload</th><th>Tentativas</th><th>Ultimo erro</th><th>Acao</th></tr></thead>
                <tbody>
                    @forelse($documents as $document)
                        <tr>
                            <td>
                                <strong>{{ $document->invoice_number }}</strong>
                                <div class="agt-muted">{{ $document->document_type_code }} | {{ $document->created_at?->format('d/m/Y H:i') }}</div>
                            </td>
                            <td><span class="agt-status {{ $document->status }}">{{ $document->status_label }}</span></td>
                            <td class="agt-mono">{{ $document->payload_hash }}</td>
                            <td>{{ $document->attempts }}</td>
                            <td class="agt-wrap">{{ $document->last_error ?: '-' }}</td>
                            <td>
                                @if($document->status === 'submitted')
                                    <span class="agt-status submitted">Validada</span>
                                @else
                                    <form method="POST" action="{{ route('admin.agt.send', $document) }}">
                                        @csrf
                                        <button class="agt-btn" type="submit">Enviar/Atualizar</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="agt-muted">Sem documentos neste estado.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $documents->links() }}

        <h2 class="agt-section-title">Faturas ainda nao preparadas</h2>
        <div class="agt-table">
            <table>
                <thead><tr><th>Fatura</th><th>Cliente</th><th>Total</th><th>Data</th><th>Acao</th></tr></thead>
                <tbody>
                    @forelse($pendingSales as $sale)
                        <tr>
                            <td><strong>{{ $sale->invoice_number }}</strong></td>
                            <td>{{ $sale->customer?->name ?? 'Consumidor final' }}</td>
                            <td>{{ number_format((float) $sale->total, 2, ',', '.') }} Kz</td>
                            <td>{{ $sale->created_at?->format('d/m/Y H:i') }}</td>
                            <td>
                                <form method="POST" action="{{ route('admin.agt.sales.prepare', $sale) }}">
                                    @csrf
                                    <button class="agt-btn" type="submit">Preparar AGT</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="agt-muted">Todas as faturas recentes estao preparadas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <h2 class="agt-section-title">Notas de credito ainda nao preparadas</h2>
        <div class="agt-table">
            <table>
                <thead><tr><th>Nota</th><th>Cliente</th><th>Total</th><th>Data</th><th>Acao</th></tr></thead>
                <tbody>
                    @forelse($pendingCreditNotes as $creditNote)
                        <tr>
                            <td><strong>{{ $creditNote->invoice_number }}</strong></td>
                            <td>{{ $creditNote->customer?->name ?? 'Consumidor final' }}</td>
                            <td>{{ number_format((float) $creditNote->total, 2, ',', '.') }} Kz</td>
                            <td>{{ $creditNote->created_at?->format('d/m/Y H:i') }}</td>
                            <td>
                                <form method="POST" action="{{ route('admin.agt.credit-notes.prepare', $creditNote) }}">
                                    @csrf
                                    <button class="agt-btn" type="submit">Preparar AGT</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="agt-muted">Todas as notas de credito recentes estao preparadas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
