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
        .agt-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:12px; margin-bottom:16px; }
        .agt-card { background:var(--card); border:1px solid var(--border); border-radius:8px; padding:14px; }
        .agt-card span { color:var(--muted); display:block; font-size:12px; font-weight:800; text-transform:uppercase; }
        .agt-card strong { color:var(--text); display:block; font-size:22px; margin-top:6px; }
        .agt-tabs { display:flex; flex-wrap:wrap; gap:8px; margin-bottom:14px; }
        .agt-tab { background:var(--card); border:1px solid var(--border); border-radius:8px; color:var(--text); font-weight:800; padding:8px 12px; text-decoration:none; }
        .agt-tab.active { border-color:#38bdf8; color:#bae6fd; }
        .agt-table { background:var(--card); border:1px solid var(--border); border-radius:8px; overflow:hidden; margin-bottom:18px; }
        .agt-table th, .agt-table td { border-bottom:1px solid var(--border); padding:12px; text-align:left; vertical-align:top; }
        .agt-table th { color:var(--muted); font-size:11px; text-transform:uppercase; }
        .agt-status { border-radius:999px; display:inline-flex; font-size:11px; font-weight:900; padding:4px 8px; background:rgba(148,163,184,.14); color:#cbd5e1; }
        .agt-status.ready { background:rgba(56,189,248,.14); color:#bae6fd; }
        .agt-status.pending { background:rgba(251,191,36,.14); color:#fde68a; }
        .agt-status.submitted { background:rgba(34,197,94,.14); color:#bbf7d0; }
        .agt-status.failed { background:rgba(239,68,68,.14); color:#fecaca; }
        .agt-btn { border:0; border-radius:8px; cursor:pointer; font-weight:900; padding:8px 10px; background:#f97316; color:#111827; }
    </style>

    @if(session('success'))
        <div style="background:rgba(16,185,129,.15); border:1px solid #10b981; color:#86efac; padding:12px; border-radius:8px; margin-bottom:14px;">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div style="background:rgba(239,68,68,.12); border:1px solid #ef4444; color:#fecaca; padding:12px; border-radius:8px; margin-bottom:14px;">{{ session('error') }}</div>
    @endif

    <div style="display:flex; justify-content:flex-end; margin-bottom:12px;">
        <a class="agt-tab" href="{{ route('admin.agt.settings') }}">Configuracoes AGT</a>
    </div>

    <div class="agt-grid">
        <div class="agt-card"><span>AGT envio</span><strong>{{ $agtEnabled ? 'Ativo' : 'Inativo' }}</strong></div>
        <div class="agt-card"><span>Preparadas</span><strong>{{ (int) ($counts['ready'] ?? 0) }}</strong></div>
        <div class="agt-card"><span>Pendentes</span><strong>{{ (int) ($counts['pending'] ?? 0) }}</strong></div>
        <div class="agt-card"><span>Validadas</span><strong>{{ (int) ($counts['submitted'] ?? 0) }}</strong></div>
        <div class="agt-card"><span>Rejeitadas</span><strong>{{ (int) ($counts['failed'] ?? 0) }}</strong></div>
    </div>

    <div style="color:var(--muted); font-size:13px; margin-bottom:14px;">
        Endpoint: {{ $agtEndpoint ?: 'nao configurado' }}. Esta fase prepara e controla documentos sem SAF-T.
    </div>

    <div class="agt-table" style="padding:14px;">
        <h2 style="font-size:18px; font-weight:800; margin:0 0 12px;">Solicitar serie AGT</h2>
        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:12px; align-items:end;">
            <form method="POST" action="{{ route('admin.agt.series.request') }}" style="display:grid; grid-template-columns:1fr 1fr auto; gap:8px; align-items:end;">
                @csrf
                <label>Tipo
                    <select name="document_type" required style="width:100%; margin-top:6px; padding:10px; border-radius:8px; background:var(--input-bg); color:var(--input-text); border:1px solid var(--border);">
                        <option value="FR">FR</option>
                        <option value="FT">FT</option>
                        <option value="NC">NC</option>
                    </select>
                </label>
                <label>Ano
                    <input type="number" name="year" value="{{ now()->year }}" min="2000" max="2100" required>
                </label>
                <button class="agt-btn" type="submit">Solicitar</button>
            </form>

            <form method="POST" action="{{ route('admin.agt.series.list') }}" style="display:grid; grid-template-columns:1fr 1fr auto; gap:8px; align-items:end;">
                @csrf
                <label>Tipo
                    <select name="document_type" required style="width:100%; margin-top:6px; padding:10px; border-radius:8px; background:var(--input-bg); color:var(--input-text); border:1px solid var(--border);">
                        <option value="FR">FR</option>
                        <option value="FT">FT</option>
                        <option value="NC">NC</option>
                    </select>
                </label>
                <label>Ano
                    <input type="number" name="year" value="{{ now()->year }}" min="2000" max="2100">
                </label>
                <button class="agt-btn" type="submit">Listar</button>
            </form>
        </div>
        <div style="color:var(--muted); font-size:12px; margin-top:10px;">NIF: {{ $agtNif ?: 'nao configurado' }} | Ambiente: {{ strtoupper((string) $agtEnvironment) }}</div>
    </div>

    @if(session('agt_series_response'))
        <div class="agt-table" style="padding:14px;">
            <h2 style="font-size:16px; font-weight:800; margin:0 0 10px;">Ultima resposta de series AGT</h2>
            <pre style="white-space:pre-wrap; word-break:break-word; color:var(--text); font-size:12px; margin:0;">{{ json_encode(session('agt_series_response'), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
        </div>
    @endif

    <h2 style="font-size:18px; font-weight:800; margin:20px 0 10px;">Registo AGT de series</h2>
    <div class="agt-table">
        <table style="width:100%; border-collapse:collapse;">
            <thead><tr><th>Ambiente</th><th>Tipo</th><th>Serie</th><th>Ano</th><th>Estado</th><th>Pedido</th><th>Erro</th><th>Ultima tentativa</th></tr></thead>
            <tbody>
                @forelse(($agtSeries ?? collect()) as $agtSerie)
                    <tr>
                        <td>{{ strtoupper((string) $agtSerie->environment) }}</td>
                        <td>{{ $agtSerie->document_type_code }}</td>
                        <td><strong>{{ $agtSerie->series_code }}</strong></td>
                        <td>{{ $agtSerie->series_year }}</td>
                        <td><span class="agt-status {{ $seriesStatusClass($agtSerie->status) }}">{{ $seriesStatusLabels[$agtSerie->status] ?? ($agtSerie->status ?: '-') }}</span></td>
                        <td style="font-family:monospace; font-size:12px;">{{ $agtSerie->request_id ?: '-' }}</td>
                        <td style="max-width:360px; word-break:break-word;">{{ $agtSerie->last_error ?: '-' }}</td>
                        <td>{{ $agtSerie->requested_at?->format('d/m/Y H:i') ?: '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" style="color:var(--muted);">Sem registos AGT de series.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <h2 style="font-size:18px; font-weight:800; margin:20px 0 10px;">Series locais</h2>
    <div class="agt-table">
        <table style="width:100%; border-collapse:collapse;">
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
                    <tr><td colspan="6" style="color:var(--muted);">Sem series locais criadas.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="agt-tabs">
        @foreach(['ready' => 'Preparadas', 'pending' => 'Pendentes', 'submitted' => 'Validadas', 'failed' => 'Rejeitadas', 'all' => 'Todos'] as $key => $label)
            <a class="agt-tab {{ $status === $key ? 'active' : '' }}" href="{{ route('admin.agt.index', ['status' => $key]) }}">{{ $label }}</a>
        @endforeach
    </div>

    <h2 style="font-size:18px; font-weight:800; margin:0 0 10px;">Documentos AGT</h2>
    <div class="agt-table">
        <table style="width:100%; border-collapse:collapse;">
            <thead><tr><th>Documento</th><th>Estado</th><th>Hash Payload</th><th>Tentativas</th><th>Ultimo erro</th><th>Acao</th></tr></thead>
            <tbody>
                @forelse($documents as $document)
                    <tr>
                        <td>
                            <strong>{{ $document->invoice_number }}</strong>
                            <div style="color:var(--muted);">{{ $document->document_type_code }} | {{ $document->created_at?->format('d/m/Y H:i') }}</div>
                        </td>
                        <td><span class="agt-status {{ $document->status }}">{{ $document->status_label }}</span></td>
                        <td style="font-family:monospace; font-size:12px;">{{ $document->payload_hash }}</td>
                        <td>{{ $document->attempts }}</td>
                        <td>{{ $document->last_error ?: '-' }}</td>
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
                    <tr><td colspan="6" style="color:var(--muted);">Sem documentos neste estado.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $documents->links() }}

    <h2 style="font-size:18px; font-weight:800; margin:20px 0 10px;">Faturas ainda nao preparadas</h2>
    <div class="agt-table">
        <table style="width:100%; border-collapse:collapse;">
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
                    <tr><td colspan="5" style="color:var(--muted);">Todas as faturas recentes estao preparadas.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <h2 style="font-size:18px; font-weight:800; margin:20px 0 10px;">Notas de credito ainda nao preparadas</h2>
    <div class="agt-table">
        <table style="width:100%; border-collapse:collapse;">
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
                    <tr><td colspan="5" style="color:var(--muted);">Todas as notas de credito recentes estao preparadas.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
