@extends('layouts.admin')

@section('page-title', 'Backups')

@section('content')
<div class="backup-page">
    <div class="backup-header">
        <div>
            <h1>Backups</h1>
            <p>Backup manual, agendamento automatico e historico local da base de dados.</p>
        </div>
        <div class="backup-state {{ $settings['enabled'] ? 'is-on' : 'is-off' }}">
            <span></span>
            {{ $settings['enabled'] ? 'Automatico ativo' : 'Automatico pausado' }}
        </div>
    </div>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-error">{{ session('error') }}</div>@endif
    @if($errors->any())
        <div class="alert alert-error">Verifique os campos da configuracao antes de guardar.</div>
    @endif

    <div class="backup-grid">
        <section class="panel backup-manual">
            <div class="panel-title-row">
                <div>
                    <h2>Backup manual</h2>
                    <p>Cria uma copia SQL imediata no armazenamento local do sistema.</p>
                </div>
            </div>

            @if($lastBackup)
                <div class="last-backup">
                    <span>Ultimo backup</span>
                    <strong>{{ $lastBackup['name'] }}</strong>
                    <small>{{ number_format($lastBackup['size'] / 1024, 1, ',', '.') }} KB · {{ $lastBackup['created_at'] }}</small>
                </div>
            @else
                <div class="last-backup muted-box">Ainda nao ha backups registados.</div>
            @endif

            <form method="POST" action="{{ route('admin.backups.run') }}">
                @csrf
                <button class="btn-primary" type="submit">Executar Backup Agora</button>
            </form>
        </section>

        <section class="panel">
            <div class="panel-title-row">
                <div>
                    <h2>Agendamento</h2>
                    <p>Define a hora diaria em que o Laravel Scheduler deve executar o backup.</p>
                </div>
            </div>

            <form method="POST" action="{{ route('admin.backups.schedule') }}" class="schedule-form">
                @csrf
                @method('PUT')

                <label class="switch-row">
                    <input type="checkbox" name="enabled" value="1" @checked(old('enabled', $settings['enabled']))>
                    <span>
                        <strong>Backup automatico</strong>
                        <small>Quando ativo, o comando diario usa o horario abaixo.</small>
                    </span>
                </label>

                <div class="form-grid">
                    <div>
                        <label for="time">Horario diario</label>
                        <input id="time" type="time" name="time" value="{{ old('time', $settings['time']) }}" required>
                        @error('time') <small class="error-text">{{ $message }}</small> @enderror
                    </div>
                    <div>
                        <label for="keep">Copias a manter</label>
                        <input id="keep" type="number" name="keep" min="1" max="60" value="{{ old('keep', $settings['keep']) }}" required>
                        @error('keep') <small class="error-text">{{ $message }}</small> @enderror
                    </div>
                </div>

                <button class="btn-secondary" type="submit">Guardar Configuracao</button>
            </form>

            <p class="scheduler-note">
                Nota: para o agendamento funcionar no servidor, o cron do sistema deve chamar <code>php artisan schedule:run</code> a cada minuto.
            </p>
        </section>
    </div>

    <section class="panel table-panel">
        <div class="panel-title-row table-title">
            <div>
                <h2>Historico local</h2>
                <p>Ficheiros guardados em <code>storage/app/backups</code>.</p>
            </div>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Ficheiro</th>
                    <th>Tamanho</th>
                    <th>Criado em</th>
                    <th>Local</th>
                </tr>
            </thead>
            <tbody>
                @forelse($backups as $backup)
                    <tr>
                        <td>{{ $backup['name'] }}</td>
                        <td>{{ number_format($backup['size'] / 1024, 1, ',', '.') }} KB</td>
                        <td>{{ $backup['created_at'] }}</td>
                        <td><code>{{ $backup['path'] }}</code></td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="empty">Sem backups.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>
</div>

<style>
    .backup-page { color: var(--text); margin: 0 auto; max-width: 1180px; padding: 22px; }
    .backup-header { align-items: flex-start; display: flex; gap: 16px; justify-content: space-between; margin-bottom: 18px; }
    .backup-header h1 { color: var(--text); font-size: 28px; font-weight: 800; margin: 0; }
    .backup-header p, .panel p, .scheduler-note { color: var(--muted); font-size: 13px; margin: 6px 0 0; }
    .backup-state { align-items: center; background: var(--card); border: 1px solid var(--border); border-radius: 999px; color: var(--muted); display: inline-flex; font-size: 12px; font-weight: 800; gap: 8px; padding: 8px 11px; white-space: nowrap; }
    .backup-state span { border-radius: 50%; display: block; height: 8px; width: 8px; }
    .backup-state.is-on span { background: #16a34a; }
    .backup-state.is-off span { background: #ef4444; }
    .backup-grid { display: grid; gap: 16px; grid-template-columns: minmax(0, .9fr) minmax(0, 1.1fr); margin-bottom: 16px; }
    .panel { background: var(--card); border: 1px solid var(--border); border-radius: 8px; padding: 16px; }
    .panel-title-row { align-items: flex-start; display: flex; justify-content: space-between; gap: 12px; margin-bottom: 14px; }
    .panel h2 { color: var(--text); font-size: 16px; font-weight: 800; margin: 0; }
    .last-backup { background: var(--input-bg); border: 1px solid var(--border); border-radius: 8px; display: grid; gap: 5px; margin-bottom: 14px; padding: 12px; }
    .last-backup span, .last-backup small { color: var(--muted); font-size: 12px; }
    .last-backup strong { color: var(--text); font-size: 13px; word-break: break-all; }
    .muted-box { color: var(--muted); font-size: 13px; }
    .schedule-form { display: grid; gap: 14px; }
    .switch-row { align-items: center; background: var(--input-bg); border: 1px solid var(--border); border-radius: 8px; cursor: pointer; display: flex; gap: 10px; padding: 12px; }
    .switch-row input { height: 18px; width: 18px; }
    .switch-row strong { color: var(--text); display: block; font-size: 13px; }
    .switch-row small { color: var(--muted); display: block; font-size: 12px; margin-top: 2px; }
    .form-grid { display: grid; gap: 12px; grid-template-columns: repeat(2, minmax(0, 1fr)); }
    label { color: var(--muted); display: block; font-size: 11px; font-weight: 800; margin-bottom: 6px; text-transform: uppercase; }
    input[type="time"], input[type="number"] { background: var(--input-bg); border: 1px solid var(--border); border-radius: 8px; color: var(--input-text); font-size: 13px; min-height: 40px; padding: 8px 10px; width: 100%; }
    .btn-primary, .btn-secondary { border: 0; border-radius: 8px; cursor: pointer; font-size: 13px; font-weight: 900; min-height: 40px; padding: 10px 14px; }
    .btn-primary { background: var(--primary); color: #fff; width: 100%; }
    .btn-secondary { background: var(--input-bg); border: 1px solid var(--border); color: var(--text); }
    .scheduler-note { border-top: 1px solid var(--border); line-height: 1.5; margin-top: 14px; padding-top: 12px; }
    .alert { border-radius: 8px; font-size: 13px; margin-bottom: 14px; padding: 11px 13px; }
    .alert-success { background: rgba(22, 163, 74, .12); border: 1px solid rgba(22, 163, 74, .35); color: #86efac; }
    .alert-error { background: rgba(220, 38, 38, .12); border: 1px solid rgba(220, 38, 38, .35); color: #fecaca; }
    .error-text { color: #fca5a5; display: block; font-size: 12px; margin-top: 5px; }
    .table-panel { overflow: hidden; padding: 0; }
    .table-title { padding: 16px 16px 0; }
    table { border-collapse: collapse; width: 100%; }
    th { border-bottom: 1px solid var(--border); color: var(--muted); font-size: 11px; padding: 12px; text-align: left; text-transform: uppercase; }
    td { border-bottom: 1px solid var(--border); color: var(--text); font-size: 13px; padding: 12px; }
    td code, .scheduler-note code, .table-title code { color: var(--muted); font-size: 12px; }
    .empty { color: var(--muted); padding: 28px; text-align: center; }
    @media (max-width: 900px) { .backup-grid, .form-grid { grid-template-columns: 1fr; } .backup-header { flex-direction: column; } }
</style>
@endsection