@extends('layouts.admin')

@section('page-title', 'Cartoes Cliente')

@section('content')
    <style>
        .cc-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:12px; margin-bottom:18px; }
        .cc-stat { background:var(--card); border:1px solid var(--border); border-radius:8px; padding:14px; }
        .cc-stat span { color:var(--muted); display:block; font-size:12px; font-weight:700; text-transform:uppercase; }
        .cc-stat strong { color:var(--text); display:block; font-size:24px; margin-top:6px; }
        .cc-table { background:var(--card); border:1px solid var(--border); border-radius:8px; overflow:hidden; }
        .cc-table th, .cc-table td { padding:12px 14px; border-bottom:1px solid var(--border); text-align:left; }
        .cc-table th { color:var(--muted); font-size:12px; text-transform:uppercase; }
        .cc-link { color:#38bdf8; font-weight:800; text-decoration:none; }
    </style>

    @if($errors->any())
        <div style="background:rgba(239,68,68,.12); border:1px solid #ef4444; color:#fecaca; padding:12px; border-radius:8px; margin-bottom:14px;">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:18px; gap:12px;">
        <h1 style="font-size:24px; font-weight:800; margin:0;">Cartoes Cliente</h1>
        <div style="display:flex; gap:8px; flex-wrap:wrap; justify-content:flex-end;">
            <a href="{{ route('admin.customer-cards.authorizations.index') }}" class="btn-primary">Solicitacoes</a>
            <a href="{{ route('admin.customers.create') }}" class="btn-primary">Novo cliente</a>
        </div>
    </div>

    <div class="cc-grid">
        <div class="cc-stat"><span>Total de cartoes</span><strong>{{ $totalCards }}</strong></div>
        <div class="cc-stat"><span>Ativos</span><strong>{{ $activeCards }}</strong></div>
        <div class="cc-stat"><span>Bloqueados</span><strong>{{ $blockedCards }}</strong></div>
        <div class="cc-stat"><span>Expirados</span><strong>{{ $expiredCards }}</strong></div>
        <div class="cc-stat"><span>Pontos emitidos</span><strong>{{ number_format($pointsIssued, 0, ',', '.') }}</strong></div>
        <div class="cc-stat"><span>Pontos utilizados</span><strong>{{ number_format($pointsUsed, 0, ',', '.') }}</strong></div>
    </div>

    <div class="cc-table">
        <table style="width:100%; border-collapse:collapse;">
            <thead><tr><th>Cartao</th><th>Cliente associado</th><th>Validade</th><th>Pontos</th><th>Saldo</th><th>Nivel</th><th>Estado</th><th>Acao</th></tr></thead>
            <tbody>
                @foreach($cards as $card)
                    <tr>
                        <td><a class="cc-link" href="{{ route('admin.customer-cards.show', $card) }}">{{ $card->card_number }}</a></td>
                        <td>{{ $card->customer->name ?? 'Cliente removido' }}</td>
                        <td>{{ optional($card->expires_at)->format('d/m/Y') ?? '-' }} @if($card->is_expired) Expirado @endif</td>
                        <td>{{ number_format($card->points, 0, ',', '.') }}</td>
                        <td>{{ number_format($card->balance, 2, ',', '.') }} Kz</td>
                        <td>{{ $card->level }}</td>
                        <td>{{ $card->status_label }}</td>
                        <td>
                            <form method="POST" action="{{ route('admin.customer-cards.toggle-status', $card) }}">
                                @csrf
                                @method('PATCH')
                                <input name="supervisor_pin" type="password" inputmode="numeric" minlength="8" maxlength="8" required placeholder="PIN supervisor" style="width:130px; margin-bottom:6px; padding:8px; border-radius:8px;">
                                <input name="supervisor_reason" type="hidden" value="Alteracao de estado do cartao">
                                <button class="btn-primary" type="submit">{{ $card->status === 'active' ? 'Bloquear' : 'Ativar' }}</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div style="margin-top:14px;">{{ $cards->links() }}</div>
@endsection
