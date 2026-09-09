@extends('layouts.admin')

@section('page-title', 'Exercicios Fiscais')

@section('content')
<style>
    .fy-page { display: grid; gap: 14px; color: var(--text); font-size: .9rem; }
    .fy-header { display: flex; justify-content: space-between; gap: 12px; align-items: flex-end; flex-wrap: wrap; }
    .fy-header h1 { margin: 0; font-size: clamp(1.25rem, 2vw, 1.7rem); }
    .fy-muted { color: var(--muted); font-size: .78rem; }
    .fy-grid { display: grid; grid-template-columns: minmax(260px, .8fr) minmax(420px, 1.2fr); gap: 12px; align-items: start; }
    .fy-panel { background: var(--card); border: 1px solid var(--border); border-radius: 8px; padding: 14px; box-shadow: 0 10px 24px rgba(15, 23, 42, .08); }
    .fy-panel h2 { margin: 0 0 10px; font-size: 1rem; }
    .fy-active { display: grid; grid-template-columns: 1fr auto; gap: 8px; align-items: center; }
    .fy-badge { display: inline-flex; align-items: center; justify-content: center; min-height: 24px; padding: 3px 8px; border-radius: 999px; font-size: .72rem; font-weight: 700; background: rgba(22, 163, 74, .14); color: #15803d; }
    .fy-badge.closed { background: rgba(220, 38, 38, .12); color: #b91c1c; }
    .fy-form { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 9px; }
    .fy-field { display: grid; gap: 4px; }
    .fy-field label { font-size: .72rem; color: var(--muted); font-weight: 700; }
    .fy-field input, .fy-field textarea { width: 100%; border: 1px solid var(--border); border-radius: 6px; background: var(--input-bg, var(--card)); color: var(--text); min-height: 34px; padding: 7px 9px; font-size: .86rem; }
    .fy-field textarea { min-height: 64px; resize: vertical; }
    .fy-span-2 { grid-column: 1 / -1; }
    .fy-check { display: flex; gap: 8px; align-items: center; min-height: 34px; color: var(--text); font-size: .84rem; }
    .fy-check input { width: 16px; height: 16px; }
    .fy-btn { border: 0; border-radius: 6px; padding: 8px 12px; font-weight: 800; cursor: pointer; font-size: .82rem; min-height: 34px; }
    .fy-btn-primary { background: var(--primary); color: #fff; }
    .fy-btn-ghost { background: transparent; color: var(--text); border: 1px solid var(--border); }
    .fy-btn-danger { background: #dc2626; color: #fff; }
    .fy-table-wrap { overflow-x: auto; }
    .fy-table { width: 100%; border-collapse: collapse; min-width: 760px; }
    .fy-table th, .fy-table td { padding: 9px 8px; border-bottom: 1px solid var(--border); text-align: left; vertical-align: top; font-size: .84rem; }
    .fy-table th { color: var(--muted); font-size: .72rem; text-transform: uppercase; letter-spacing: .02em; }
    .fy-actions { display: flex; gap: 6px; flex-wrap: wrap; align-items: center; }
    .fy-close-form { display: flex; gap: 6px; align-items: center; }
    .fy-close-form input { width: 76px; min-height: 34px; border: 1px solid var(--border); border-radius: 6px; background: var(--input-bg, var(--card)); color: var(--text); padding: 6px 8px; }
    @media (max-width: 900px) { .fy-grid { grid-template-columns: 1fr; } .fy-form { grid-template-columns: 1fr; } }
</style>

<div class="fy-page">
    <div class="fy-header">
        <div>
            <h1>Exercicios Fiscais</h1>
            <div class="fy-muted">Controle de abertura, ativacao e fecho do ano contabil/fiscal.</div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert-success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert-error">{{ $errors->first() }}</div>
    @endif

    <div class="fy-grid">
        <div class="fy-panel">
            <h2>Ano ativo</h2>
            @if($activeFiscalYear)
                <div class="fy-active">
                    <div>
                        <strong>{{ $activeFiscalYear->name }}</strong>
                        <div class="fy-muted">{{ $activeFiscalYear->start_date?->format('d/m/Y') }} a {{ $activeFiscalYear->end_date?->format('d/m/Y') }}</div>
                    </div>
                    <span class="fy-badge">Ativo</span>
                </div>
            @else
                <div class="fy-muted">Nenhum exercicio ativo. Crie ou ative um exercicio aberto antes de lancar documentos.</div>
            @endif
        </div>

        <div class="fy-panel">
            <h2>Novo exercicio</h2>
            <form method="POST" action="{{ route('admin.fiscal-years.store') }}" class="fy-form">
                @csrf
                <div class="fy-field">
                    <label>Ano</label>
                    <input type="number" name="year" min="2000" max="2100" value="{{ old('year', $nextYear) }}" required>
                </div>
                <div class="fy-field">
                    <label>Nome</label>
                    <input name="name" value="{{ old('name', 'Exercicio ' . $nextYear) }}" maxlength="80">
                </div>
                <div class="fy-field">
                    <label>Inicio</label>
                    <input type="date" name="start_date" value="{{ old('start_date', $nextYear . '-01-01') }}" required>
                </div>
                <div class="fy-field">
                    <label>Fim</label>
                    <input type="date" name="end_date" value="{{ old('end_date', $nextYear . '-12-31') }}" required>
                </div>
                <label class="fy-check fy-span-2">
                    <input type="checkbox" name="activate" value="1" @checked(old('activate', true))>
                    Ativar este exercicio apos criar
                </label>
                <div class="fy-field fy-span-2">
                    <label>Notas</label>
                    <textarea name="notes" maxlength="2000">{{ old('notes') }}</textarea>
                </div>
                <button class="fy-btn fy-btn-primary fy-span-2">Criar exercicio</button>
            </form>
        </div>
    </div>

    <div class="fy-panel">
        <h2>Exercicios cadastrados</h2>
        <div class="fy-table-wrap">
            <table class="fy-table">
                <thead>
                    <tr>
                        <th>Ano</th>
                        <th>Periodo</th>
                        <th>Estado</th>
                        <th>Abertura</th>
                        <th>Fecho</th>
                        <th>Acoes</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($fiscalYears as $year)
                        <tr>
                            <td><strong>{{ $year->name }}</strong><div class="fy-muted">{{ $year->year }}</div></td>
                            <td>{{ $year->start_date?->format('d/m/Y') }}<br>{{ $year->end_date?->format('d/m/Y') }}</td>
                            <td>
                                <span class="fy-badge {{ $year->status === 'closed' ? 'closed' : '' }}">{{ $year->status_label }}</span>
                                @if($year->is_active)<div class="fy-muted">Ativo</div>@endif
                            </td>
                            <td>{{ $year->opened_at?->format('d/m/Y H:i') ?: '-' }}<div class="fy-muted">{{ $year->opener?->name }}</div></td>
                            <td>{{ $year->closed_at?->format('d/m/Y H:i') ?: '-' }}<div class="fy-muted">{{ $year->closer?->name }}</div></td>
                            <td>
                                <div class="fy-actions">
                                    @if($year->status !== 'closed' && ! $year->is_active)
                                        <form method="POST" action="{{ route('admin.fiscal-years.activate', $year) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button class="fy-btn fy-btn-ghost">Ativar</button>
                                        </form>
                                    @endif
                                    @if($year->status !== 'closed')
                                        <form method="POST" action="{{ route('admin.fiscal-years.close', $year) }}" class="fy-close-form">
                                            @csrf
                                            @method('PATCH')
                                            <input name="confirm_year" placeholder="{{ $year->year }}" title="Digite {{ $year->year }} para confirmar">
                                            <button class="fy-btn fy-btn-danger">Fechar</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6">Nenhum exercicio fiscal cadastrado.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection