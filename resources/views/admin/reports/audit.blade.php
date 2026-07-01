<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    @include('admin.reports.partials.pdf-style')
</head>
<body>
    @include('admin.reports.partials.header')

    <table class="summary">
        <tr>
            <td><span>Eventos</span><strong>{{ $totals['count'] }}</strong></td>
            <td><span>Acoes</span><strong>{{ $totals['actions'] }}</strong></td>
            <td><span>Modelos</span><strong>{{ $totals['models'] }}</strong></td>
        </tr>
    </table>

    <table class="data">
        <thead>
            <tr>
                <th>Data</th>
                <th>Acao</th>
                <th>Modelo</th>
                <th>ID</th>
                <th>Utilizador</th>
                <th>Dados</th>
            </tr>
        </thead>
        <tbody>
            @forelse($logs as $log)
                <tr>
                    <td>{{ optional($log->created_at)->format('d/m/Y H:i') }}</td>
                    <td>{{ $log->action }}</td>
                    <td>{{ $log->model }}</td>
                    <td>{{ $log->model_id ?: '-' }}</td>
                    <td>{{ $log->user->name ?? 'Sistema' }}</td>
                    <td>{{ \Illuminate\Support\Str::limit(json_encode($log->data, JSON_UNESCAPED_UNICODE), 140) }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="muted">Sem eventos de auditoria neste periodo.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">Documento gerado pelo Nkama ERP.</div>
</body>
</html>
