@extends('layouts.admin')

@section('page-title', 'Documentos & Series')

@section('content')
    <style>
        .doc-wrap { display: grid; gap: 18px; }
        .doc-panel {
            background: rgba(15, 23, 42, 0.76);
            border: 1px solid rgba(255,255,255,.07);
            border-radius: 8px;
            padding: 18px;
        }
        .doc-title { color:#fff; font-size:22px; font-weight:900; margin:0; }
        .doc-muted { color:#94a3b8; font-size:13px; margin-top:5px; }
        .doc-grid { display:grid; gap:12px; grid-template-columns: repeat(4, minmax(0, 1fr)); }
        .doc-field { display:flex; flex-direction:column; gap:6px; }
        .doc-field label, .doc-check {
            color:#cbd5e1; font-size:11px; font-weight:900; letter-spacing:.06em; text-transform:uppercase;
        }
        .doc-field input, .doc-field select {
            background:#070a12; border:1px solid rgba(255,255,255,.09); border-radius:8px;
            color:#e5e7eb; min-height:42px; padding:10px 12px; width:100%;
        }
        .doc-check { align-items:center; display:flex; gap:8px; min-height:42px; }
        .doc-check input { height:17px; width:17px; }
        .doc-btn {
            background:#f97316; border:none; border-radius:8px; color:#111827; cursor:pointer;
            font-weight:900; min-height:42px; padding:0 14px;
        }
        .doc-btn-soft {
            background:rgba(255,255,255,.06); border:1px solid rgba(255,255,255,.08); color:#e5e7eb;
        }
        .doc-table-wrap { overflow-x:auto; }
        .doc-table { border-collapse:collapse; min-width:920px; width:100%; }
        .doc-table th {
            background:rgba(255,255,255,.04); color:#94a3b8; font-size:11px; letter-spacing:.06em;
            padding:12px; text-align:left; text-transform:uppercase;
        }
        .doc-table td { border-top:1px solid rgba(255,255,255,.06); color:#e5e7eb; padding:12px; vertical-align:top; }
        .doc-code { color:#38bdf8; font-weight:900; }
        .doc-pill { border-radius:999px; display:inline-flex; font-size:11px; font-weight:900; padding:4px 8px; }
        .doc-pill-on { background:rgba(52,211,153,.12); color:#86efac; }
        .doc-pill-off { background:rgba(248,113,113,.12); color:#fecaca; }
        .doc-series { color:#94a3b8; display:grid; gap:4px; font-size:12px; }
        .doc-actions { display:flex; gap:8px; justify-content:flex-end; }
        @media (max-width: 980px) { .doc-grid { grid-template-columns: 1fr 1fr; } }
        @media (max-width: 640px) { .doc-grid { grid-template-columns: 1fr; } .doc-actions { justify-content:flex-start; } }
    </style>

    <div class="doc-wrap">
        <div>
            <h1 class="doc-title">Documentos & Series</h1>
            <div class="doc-muted">Configure os tipos fiscais e a numeração anual usada nas faturas.</div>
        </div>

        <div class="doc-panel">
            <form method="POST" action="{{ route('admin.document-settings.types.store') }}" class="doc-grid">
                @csrf
                <div class="doc-field">
                    <label>Codigo</label>
                    <input name="code" maxlength="10" placeholder="FR, FT, NC" value="{{ old('code') }}" required>
                </div>
                <div class="doc-field">
                    <label>Nome</label>
                    <input name="name" placeholder="Fatura Recibo" value="{{ old('name') }}" required>
                </div>
                <div class="doc-field">
                    <label>Descricao</label>
                    <input name="description" maxlength="255" value="{{ old('description') }}">
                </div>
                <div style="display:grid; gap:8px;">
                    <label class="doc-check"><input type="checkbox" name="affects_current_account" value="1"> Conta corrente</label>
                    <label class="doc-check"><input type="checkbox" name="is_credit_note" value="1"> Nota de credito</label>
                </div>
                <div>
                    <button class="doc-btn" type="submit">Criar tipo</button>
                </div>
            </form>
        </div>

        <div class="doc-panel">
            <form method="POST" action="{{ route('admin.document-settings.series.store') }}" class="doc-grid">
                @csrf
                <div class="doc-field">
                    <label>Tipo</label>
                    <select name="document_type_id" required>
                        @foreach($types as $type)
                            <option value="{{ $type->id }}">{{ $type->code }} - {{ $type->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="doc-field">
                    <label>Ano</label>
                    <input type="number" name="year" min="2000" max="2100" value="{{ old('year', now()->year) }}" required>
                </div>
                <div class="doc-field">
                    <label>Serie</label>
                    <input name="code" maxlength="20" value="{{ old('code', now()->year) }}" required>
                </div>
                <div class="doc-field">
                    <label>Numero inicial</label>
                    <input type="number" name="start_number" min="1" value="{{ old('start_number', 1) }}" required>
                </div>
                <div>
                    <button class="doc-btn" type="submit">Criar serie</button>
                </div>
            </form>
        </div>

        <div class="doc-panel">
            <div class="doc-table-wrap">
                <table class="doc-table">
                    <thead>
                        <tr>
                            <th>Tipo</th>
                            <th>Uso</th>
                            <th>Series</th>
                            <th>Estado</th>
                            <th style="text-align:right;">Acoes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($types as $type)
                            <tr>
                                <td>
                                    <div class="doc-code">{{ $type->code }}</div>
                                    <strong>{{ $type->name }}</strong>
                                    <div class="doc-muted">{{ $type->description ?: 'Sem descricao' }}</div>
                                </td>
                                <td>
                                    <div>{{ $type->affects_current_account ? 'Lanca em conta corrente' : 'Sem conta corrente automatica' }}</div>
                                    <div>{{ $type->is_credit_note ? 'Nota de credito' : 'Documento de faturacao' }}</div>
                                </td>
                                <td>
                                    <div class="doc-series">
                                        @forelse($type->series as $series)
                                            <div>
                                                <strong>{{ $series->code }}/{{ $series->year }}</strong>
                                                atual: {{ $series->current_number }}
                                                inicial: {{ $series->start_number }}
                                                <span class="doc-pill {{ $series->active ? 'doc-pill-on' : 'doc-pill-off' }}">{{ $series->active ? 'Ativa' : 'Inativa' }}</span>
                                                <form method="POST" action="{{ route('admin.document-settings.series.toggle', $series) }}" style="display:inline;">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button class="doc-btn doc-btn-soft" type="submit" style="min-height:26px; padding:0 8px;">{{ $series->active ? 'Desativar' : 'Ativar' }}</button>
                                                </form>
                                            </div>
                                        @empty
                                            <span>Sem series.</span>
                                        @endforelse
                                    </div>
                                </td>
                                <td>
                                    <span class="doc-pill {{ $type->active ? 'doc-pill-on' : 'doc-pill-off' }}">{{ $type->active ? 'Ativo' : 'Inativo' }}</span>
                                </td>
                                <td>
                                    <div class="doc-actions">
                                        <form method="POST" action="{{ route('admin.document-settings.types.toggle', $type) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button class="doc-btn doc-btn-soft" type="submit">{{ $type->active ? 'Desativar' : 'Ativar' }}</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" style="text-align:center;">Nenhum tipo configurado.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        @if(session('success'))
            nkamaAlert(@json(session('success')), 'success');
        @endif

        @if($errors->any())
            nkamaAlert(@json($errors->first()), 'error');
        @endif
    </script>
@endsection
