@extends('layouts.admin')

@section('page-title', 'Solicitacoes do Cartao Cliente')

@section('content')
    @php
        $labels = [
            'pending' => 'Pendentes',
            'approved' => 'Aprovadas',
            'rejected' => 'Rejeitadas',
            'expired' => 'Expiradas',
            'used' => 'Usadas',
        ];
    @endphp
    <style>
        .cc-auth-header { display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom:18px; }
        .cc-auth-tabs { display:flex; flex-wrap:wrap; gap:8px; margin-bottom:14px; }
        .cc-auth-tab { display:inline-flex; align-items:center; gap:6px; padding:9px 12px; border-radius:8px; border:1px solid var(--border); color:var(--text); text-decoration:none; font-weight:800; font-size:13px; background:var(--card); }
        .cc-auth-tab.active { border-color:#38bdf8; color:#e0f2fe; background:rgba(56,189,248,.12); }
        .cc-auth-table { background:var(--card); border:1px solid var(--border); border-radius:8px; overflow:hidden; }
        .cc-auth-table th, .cc-auth-table td { padding:12px; border-bottom:1px solid var(--border); text-align:left; vertical-align:top; }
        .cc-auth-table th { color:var(--muted); font-size:11px; text-transform:uppercase; }
        .cc-auth-badge { display:inline-flex; padding:4px 8px; border-radius:999px; font-size:11px; font-weight:900; background:rgba(148,163,184,.15); color:#cbd5e1; }
        .cc-auth-badge.pending { background:rgba(250,204,21,.14); color:#fde68a; }
        .cc-auth-badge.approved { background:rgba(34,197,94,.14); color:#bbf7d0; }
        .cc-auth-badge.rejected { background:rgba(239,68,68,.14); color:#fecaca; }
        .cc-auth-badge.expired { background:rgba(148,163,184,.14); color:#cbd5e1; }
        .cc-auth-badge.used { background:rgba(56,189,248,.14); color:#bae6fd; }
        .cc-auth-actions { display:grid; gap:8px; min-width:180px; }
        .cc-auth-actions input { width:100%; box-sizing:border-box; padding:8px; border-radius:8px; }
        .cc-auth-button { border:0; border-radius:8px; padding:8px 10px; font-weight:900; cursor:pointer; margin-top:6px; }
        .cc-auth-approve { background:#22c55e; color:#052e16; }
        .cc-auth-reject { background:#ef4444; color:#fff; }
    </style>

    @if(session('success'))
        <div style="background:rgba(16,185,129,.15); border:1px solid #10b981; color:#86efac; padding:12px; border-radius:8px; margin-bottom:14px;">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div style="background:rgba(239,68,68,.12); border:1px solid #ef4444; color:#fecaca; padding:12px; border-radius:8px; margin-bottom:14px;">{{ session('error') }}</div>
    @endif

    <div class="cc-auth-header">
        <h1 style="font-size:24px; font-weight:800; margin:0;">Solicitacoes do Cartao Cliente</h1>
        <a href="{{ route('admin.customer-cards.index') }}" class="btn-primary">Ver cartoes</a>
    </div>

    <div class="cc-auth-tabs">
        @foreach($labels as $key => $label)
            <a class="cc-auth-tab {{ $status === $key ? 'active' : '' }}" href="{{ route('admin.customer-cards.authorizations.index', ['status' => $key]) }}">
                {{ $label }}
                <span data-count-status="{{ $key }}">{{ (int) ($statusCounts[$key] ?? 0) }}</span>
            </a>
        @endforeach
    </div>

    <div id="cc-auth-live-status" style="color:var(--muted); font-size:12px; margin-bottom:10px;">Atualizacao automatica ativa.</div>

    <div class="cc-auth-table">
        <table style="width:100%; border-collapse:collapse;">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Cartao / Cliente</th>
                    <th>Operador</th>
                    <th>Valor</th>
                    <th>Estado</th>
                    <th>Motivo</th>
                    <th>Gestor</th>
                    <th>Acao</th>
                </tr>
            </thead>
            <tbody id="cc-auth-tbody">
                @forelse($authorizations as $authorization)
                    <tr>
                        <td>
                            <strong>{{ $authorization->created_at?->format('d/m/Y H:i') }}</strong>
                            <div style="color:var(--muted); font-size:12px;">Expira: {{ $authorization->expires_at?->format('d/m/Y H:i') ?? '-' }}</div>
                        </td>
                        <td>
                            <strong>{{ $authorization->card?->card_number ?? '-' }}</strong>
                            <div style="color:var(--muted);">{{ $authorization->card?->customer?->name ?? 'Cliente removido' }}</div>
                        </td>
                        <td>{{ $authorization->requester?->name ?? '-' }}</td>
                        <td>{{ number_format((float) $authorization->amount, 2, ',', '.') }} Kz</td>
                        <td><span class="cc-auth-badge {{ $authorization->status }}">{{ $labels[$authorization->status] ?? ucfirst($authorization->status) }}</span></td>
                        <td>{{ $authorization->reason ?: '-' }}</td>
                        <td>
                            {{ $authorization->reviewer?->name ?? '-' }}
                            @if($authorization->decision_note)
                                <div style="color:var(--muted); font-size:12px;">{{ $authorization->decision_note }}</div>
                            @endif
                        </td>
                        <td>
                            @if($authorization->status === 'pending')
                                <div class="cc-auth-actions">
                                    <form class="cc-auth-action-form" method="POST" action="{{ route('admin.customer-cards.authorizations.approve', $authorization) }}">
                                        @csrf
                                        <input name="note" maxlength="180" placeholder="Nota opcional">
                                        <button class="cc-auth-button cc-auth-approve" type="submit">Aprovar</button>
                                    </form>
                                    <form class="cc-auth-action-form" method="POST" action="{{ route('admin.customer-cards.authorizations.reject', $authorization) }}">
                                        @csrf
                                        <input name="note" maxlength="180" placeholder="Motivo da rejeicao">
                                        <button class="cc-auth-button cc-auth-reject" type="submit">Rejeitar</button>
                                    </form>
                                </div>
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" style="color:var(--muted);">Sem solicitacoes neste estado.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top:14px;">{{ $authorizations->links() }}</div>

    <script>
        (() => {
            const labels = @json($labels);
            const currentStatus = @json($status);
            const tbody = document.getElementById('cc-auth-tbody');
            const statusEl = document.getElementById('cc-auth-live-status');
            const pageUrl = @json(route('admin.customer-cards.authorizations.index', ['status' => $status]));
            const approveTemplate = @json(route('admin.customer-cards.authorizations.approve', ['authorization' => '__ID__']));
            const rejectTemplate = @json(route('admin.customer-cards.authorizations.reject', ['authorization' => '__ID__']));
            let latestId = {{ (int) ($authorizations->max('id') ?? 0) }};
            let firstLoad = true;

            const escapeHtml = value => String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');

            const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';

            function formatMoney(value) {
                return `${Number(value || 0).toLocaleString('pt-PT', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} Kz`;
            }

            function actionForms(item) {
                if (item.status !== 'pending') return '-';
                const approveUrl = approveTemplate.replace('__ID__', encodeURIComponent(item.id));
                const rejectUrl = rejectTemplate.replace('__ID__', encodeURIComponent(item.id));

                return `
                    <div class="cc-auth-actions">
                        <form class="cc-auth-action-form" method="POST" action="${approveUrl}">
                            <input type="hidden" name="_token" value="${csrf()}">
                            <input name="note" maxlength="180" placeholder="Nota opcional">
                            <button class="cc-auth-button cc-auth-approve" type="submit">Aprovar</button>
                        </form>
                        <form class="cc-auth-action-form" method="POST" action="${rejectUrl}">
                            <input type="hidden" name="_token" value="${csrf()}">
                            <input name="note" maxlength="180" placeholder="Motivo da rejeicao">
                            <button class="cc-auth-button cc-auth-reject" type="submit">Rejeitar</button>
                        </form>
                    </div>`;
            }

            function renderRows(items) {
                if (!tbody) return;

                if (!items.length) {
                    tbody.innerHTML = '<tr><td colspan="8" style="color:var(--muted);">Sem solicitacoes neste estado.</td></tr>';
                    return;
                }

                tbody.innerHTML = items.map(item => `
                    <tr>
                        <td>
                            <strong>${escapeHtml(item.created_at || '-')}</strong>
                            <div style="color:var(--muted); font-size:12px;">Expira: ${escapeHtml(item.expires_at || '-')}</div>
                        </td>
                        <td>
                            <strong>${escapeHtml(item.card_number || '-')}</strong>
                            <div style="color:var(--muted);">${escapeHtml(item.customer_name || 'Cliente removido')}</div>
                        </td>
                        <td>${escapeHtml(item.operator_name || '-')}</td>
                        <td>${formatMoney(item.amount)}</td>
                        <td><span class="cc-auth-badge ${escapeHtml(item.status)}">${escapeHtml(labels[item.status] || item.status)}</span></td>
                        <td>${escapeHtml(item.reason || '-')}</td>
                        <td>
                            ${escapeHtml(item.supervisor_name || '-')}
                            ${item.decision_note ? `<div style="color:var(--muted); font-size:12px;">${escapeHtml(item.decision_note)}</div>` : ''}
                        </td>
                        <td>${actionForms(item)}</td>
                    </tr>`).join('');

                bindActionForms();
            }

            function updateCounts(counts) {
                Object.keys(labels).forEach(status => {
                    const el = document.querySelector(`[data-count-status="${status}"]`);
                    if (el) el.textContent = Number(counts?.[status] || 0);
                });
            }

            async function refreshAuthorizations() {
                try {
                    const response = await fetch(pageUrl, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    const data = await response.json();

                    if (!response.ok || !data.success) {
                        throw new Error(data.message || 'Falha ao atualizar solicitacoes.');
                    }

                    renderRows(data.authorizations || []);
                    updateCounts(data.counts || {});

                    if (!firstLoad && Number(data.latest_id || 0) > latestId && currentStatus === 'pending') {
                        statusEl.textContent = 'Nova solicitacao recebida agora.';
                    } else {
                        statusEl.textContent = `Atualizado em ${data.server_time || new Date().toLocaleTimeString('pt-PT')}.`;
                    }

                    latestId = Math.max(latestId, Number(data.latest_id || 0));
                    firstLoad = false;
                } catch (error) {
                    statusEl.textContent = error.message;
                }
            }

            function bindActionForms() {
                document.querySelectorAll('.cc-auth-action-form').forEach(form => {
                    if (form.dataset.bound === '1') return;
                    form.dataset.bound = '1';
                    form.addEventListener('submit', async event => {
                        event.preventDefault();

                        try {
                            const response = await fetch(form.action, {
                                method: 'POST',
                                headers: {
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'X-CSRF-Token': csrf()
                                },
                                body: new FormData(form)
                            });
                            const data = await response.json();

                            if (!response.ok || !data.success) {
                                throw new Error(data.message || 'Nao foi possivel processar a solicitacao.');
                            }

                            statusEl.textContent = data.message || 'Solicitacao processada.';
                            refreshAuthorizations();
                        } catch (error) {
                            statusEl.textContent = error.message;
                        }
                    });
                });
            }

            bindActionForms();
            refreshAuthorizations();
            setInterval(refreshAuthorizations, 3000);
        })();
    </script>
@endsection