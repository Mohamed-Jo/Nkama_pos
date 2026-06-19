@extends('layouts.admin')

@section('page-title', 'Histórico de Caixas')

@section('content')
    <style>
        .history-container {
            padding: 24px;
            max-width: 1600px;
            margin: 0 auto;
        }

        .table-card {
            background: rgba(17, 24, 39, 0.7);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .custom-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 14px;
        }

        .custom-table th {
            padding: 14px 16px;
            color: #9ca3af;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 0.05em;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        .custom-table td {
            padding: 16px;
            color: #e5e7eb;
            border-bottom: 1px solid rgba(255, 255, 255, 0.04);
        }

        .custom-table tr:hover td {
            background: rgba(255, 255, 255, 0.02);
        }

        .badge {
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-success {
            background: rgba(16, 185, 129, 0.15);
            color: #10b981;
        }

        .badge-danger {
            background: rgba(239, 68, 68, 0.15);
            color: #ef4444;
        }

        .badge-neutral {
            background: rgba(156, 163, 175, 0.15);
            color: #9ca3af;
        }

        .btn-view {
            background: rgba(249, 115, 22, 0.1);
            color: #f97316;
            border: 1px solid rgba(249, 115, 22, 0.2);
            padding: 6px 12px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.2s;
        }

        .btn-view:hover {
            background: #f97316;
            color: #000;
        }
    </style>

    <div class="history-container">
        <div class="table-card">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>ID Turno</th>
                        <th>Operador</th>
                        <th>Abertura</th>
                        <th>Fecho</th>
                        <th>Faturação (Sistema)</th>
                        <th>Declarado (Operador)</th>
                        <th>Balanço</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($shifts as $shift)
                        @php
                            // Cálculo de diferença entre o esperado e o declarado
                            $dif = $shift->reported_cash - $shift->estimated_cash;
                        @endphp
                        <tr>
                            <td>#{{ $shift->id }}</td>
                            <td>{{ $shift->operator->name ?? 'Operador ID: ' . $shift->operator_id }}</td>
                            <td>{{ \Carbon\Carbon::parse($shift->opened_at)->format('d/m/Y H:i') }}</td>
                            <td>{{ \Carbon\Carbon::parse($shift->closed_at)->format('d/m/Y H:i') }}</td>
                            <td>{{ number_format($shift->estimated_cash, 2) }} Kz</td>
                            <td>{{ number_format($shift->reported_cash, 2) }} Kz</td>
                            <td>
                                @if ($dif == 0)
                                    <span class="badge badge-success">✓ Correto</span>
                                @elseif($dif > 0)
                                    <span class="badge badge-neutral">+{{ number_format($dif, 2) }} Kz (Sobra)</span>
                                @else
                                    <span class="badge badge-danger">{{ number_format($dif, 2) }} Kz (Quebra)</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('admin.shifts.show', $shift->id) }}" class="btn-view">Auditar</a>
                            </td>
                        </tr>
                    @endforeach

                    @if ($shifts->isEmpty())
                        <tr>
                            <td colspan="8" style="text-align: center; color: #9ca3af; padding: 40px;">
                                Nenhum registo de caixa fechado encontrado no histórico.
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>

            <div style="margin-top: 20px;">
                {{ $shifts->links() }}
            </div>
        </div>
    </div>
@endsection
