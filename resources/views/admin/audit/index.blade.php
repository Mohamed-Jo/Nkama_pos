@extends('layouts.admin')

@section('page-title', 'Auditoria')

@section('content')
    <style>
        .audit-page {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .audit-panel {
            background: rgba(15, 23, 42, 0.55);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 8px;
            padding: 16px;
        }

        .audit-title {
            margin: 0 0 12px;
            font-size: 18px;
            color: #f8fafc;
        }

        .audit-filters {
            display: grid;
            grid-template-columns: 1.4fr repeat(4, minmax(130px, 1fr)) auto;
            gap: 10px;
            align-items: end;
        }

        .audit-field label {
            display: block;
            margin-bottom: 6px;
            color: #94a3b8;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .audit-field input,
        .audit-field select {
            width: 100%;
            box-sizing: border-box;
            padding: 10px 12px;
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.08);
            background: #070a12;
            color: #e5e7eb;
            margin: 0;
        }

        .audit-actions {
            display: flex;
            gap: 8px;
        }

        .audit-button,
        .audit-link {
            min-height: 40px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0 14px;
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.08);
            font-weight: 700;
            font-size: 13px;
            text-decoration: none;
            cursor: pointer;
        }

        .audit-button {
            background: #f97316;
            color: #111827;
        }

        .audit-link {
            color: #cbd5e1;
            background: rgba(255, 255, 255, 0.04);
        }

        .audit-table-wrap {
            overflow-x: auto;
        }

        .audit-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        .audit-table th {
            text-align: left;
            padding: 12px;
            color: #94a3b8;
            font-size: 11px;
            text-transform: uppercase;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        .audit-table td {
            vertical-align: top;
            padding: 12px;
            color: #e2e8f0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.04);
        }

        .audit-badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 8px;
            border-radius: 999px;
            background: rgba(249, 115, 22, 0.12);
            color: #fdba74;
            font-weight: 800;
            font-size: 11px;
            text-transform: uppercase;
        }

        .audit-muted {
            color: #94a3b8;
            font-size: 12px;
        }

        .audit-details {
            max-width: 560px;
        }

        .audit-details summary {
            color: #fdba74;
            cursor: pointer;
            font-weight: 700;
        }

        .audit-json {
            max-height: 260px;
            overflow: auto;
            margin: 10px 0 0;
            padding: 12px;
            border-radius: 8px;
            background: #05070d;
            color: #cbd5e1;
            font-size: 12px;
            white-space: pre-wrap;
        }

        .audit-empty {
            padding: 32px;
            text-align: center;
            color: #94a3b8;
        }

        .audit-pagination {
            margin-top: 14px;
        }

        @media (max-width: 1100px) {
            .audit-filters {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .audit-actions {
                grid-column: span 2;
            }
        }

        @media (max-width: 640px) {
            .audit-filters {
                grid-template-columns: 1fr;
            }

            .audit-actions {
                grid-column: auto;
            }
        }
    </style>

    <div class="audit-page">
        <section class="audit-panel">
            <h1 class="audit-title">Trilha de auditoria</h1>

            <form method="GET" action="{{ route('admin.audit.index') }}" class="audit-filters">
                <div class="audit-field">
                    <label for="search">Pesquisar</label>
                    <input id="search" name="search" value="{{ request('search') }}" placeholder="ID, operador, IP ou detalhe">
                </div>

                <div class="audit-field">
                    <label for="action">Ação</label>
                    <select id="action" name="action">
                        <option value="">Todas</option>
                        @foreach($actions as $action)
                            <option value="{{ $action }}" @selected(request('action') === $action)>{{ ucfirst($action) }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="audit-field">
                    <label for="model">Modelo</label>
                    <select id="model" name="model">
                        <option value="">Todos</option>
                        @foreach($models as $model)
                            <option value="{{ $model }}" @selected(request('model') === $model)>{{ $model }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="audit-field">
                    <label for="from">De</label>
                    <input id="from" name="from" type="date" value="{{ request('from') }}">
                </div>

                <div class="audit-field">
                    <label for="to">Até</label>
                    <input id="to" name="to" type="date" value="{{ request('to') }}">
                </div>

                <div class="audit-actions">
                    <button type="submit" class="audit-button">Filtrar</button>
                    <a href="{{ route('admin.audit.index') }}" class="audit-link">Limpar</a>
                </div>
            </form>
        </section>

        <section class="audit-panel">
            <div class="audit-table-wrap">
                <table class="audit-table">
                    <thead>
                        <tr>
                            <th>Quando</th>
                            <th>Ação</th>
                            <th>Registo</th>
                            <th>Responsável</th>
                            <th>Origem</th>
                            <th>Detalhes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                            @php
                                $payload = $log->data ?? [];
                                $detail = $payload['data'] ?? [];
                            @endphp
                            <tr>
                                <td>
                                    <strong>{{ $log->created_at?->format('d/m/Y H:i') }}</strong>
                                    <div class="audit-muted">{{ $log->created_at?->diffForHumans() }}</div>
                                </td>
                                <td><span class="audit-badge">{{ $log->action }}</span></td>
                                <td>
                                    <strong>{{ $log->model }}</strong>
                                    <div class="audit-muted">ID: {{ $log->model_id ?? '-' }}</div>
                                </td>
                                <td>
                                    {{ $log->user?->name ?? 'Sistema/POS' }}
                                    <div class="audit-muted">Operador: {{ $payload['operator_id'] ?? '-' }}</div>
                                </td>
                                <td>
                                    <div>{{ $payload['method'] ?? '-' }}</div>
                                    <div class="audit-muted">{{ $payload['ip'] ?? '-' }}</div>
                                </td>
                                <td class="audit-details">
                                    <details>
                                        <summary>Ver alterações</summary>
                                        <pre class="audit-json">{{ json_encode($detail, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                                    </details>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="audit-empty">Ainda não existem eventos de auditoria para estes filtros.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="audit-pagination">
                {{ $logs->links() }}
            </div>
        </section>
    </div>
@endsection
