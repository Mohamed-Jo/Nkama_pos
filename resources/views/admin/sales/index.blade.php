@extends('layouts.admin')

@section('content')
    @php
        $agtBadgeClass = fn ($status) => match ($status) {
            'submitted' => 'ok',
            'pending' => 'warn',
            'failed' => 'danger',
            default => '',
        };
    @endphp
    <style>
        /* CONFIGURAÇÕES GERAIS E PALETA NKAMA */
        :root {
            --bg-panel: rgba(15, 23, 42, 0.4);
            --border-color: rgba(255, 255, 255, 0.05);
            --text-main: #f1f5f9;
            --text-muted: #94a3b8;
            --orange-brand: #f97316;
            --green-brand: #22c55e;
            --red-brand: #ef4444;
        }

        .dashboard-container {
            padding: 4px;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, sans-serif;
        }

        /* PAINÉIS MODERNOS */
        .panel-custom {
            background: var(--bg-panel);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 20px;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.2);
        }

        .panel-title {
            font-size: 15px;
            font-weight: 600;
            color: var(--text-main);
            letter-spacing: -0.01em;
            margin-top: 0;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* GRELHAS INTELIGENTES */
        .sap-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 20px;
        }

        .sap-grid-5 {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 16px;
            margin-bottom: 20px;
        }

        @media (max-width: 1024px) {
            .sap-grid, .sap-grid-5 { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 640px) {
            .sap-grid, .sap-grid-5 { grid-template-columns: 1fr; }
        }

        /* CARD DE KPIS */
        .kpi-card {
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.04);
            padding: 20px;
            border-radius: 14px;
            transition: border-color 0.3s, transform 0.3s;
        }

        .kpi-card:hover {
            border-color: rgba(249, 115, 22, 0.15);
            transform: translateY(-2px);
        }

        .kpi-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }

        .kpi-label {
            font-size: 11px;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .kpi-num {
            font-size: 22px;
            font-weight: 700;
            color: var(--text-main);
            letter-spacing: -0.02em;
        }

        /* DUAS COLUNAS: GRÁFICO + RANKING */
        .dashboard-row {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        @media (max-width: 1024px) {
            .dashboard-row { grid-template-columns: 1fr; }
        }

        /* TOP RANKING LIST */
        .ranking-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-top: 12px;
        }

        .ranking-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 12px;
            background: rgba(255, 255, 255, 0.01);
            border: 1px solid rgba(255, 255, 255, 0.03);
            border-radius: 10px;
            transition: all 0.2s ease;
        }

        .ranking-item:hover {
            background: rgba(249, 115, 22, 0.03);
            border-color: rgba(249, 115, 22, 0.1);
            transform: translateX(4px);
        }

        .product-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .position-badge {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.05);
            color: var(--text-muted);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: 700;
        }

        .ranking-item:nth-child(1) .position-badge { background: rgba(249, 115, 22, 0.15); color: var(--orange-brand); }
        .ranking-item:nth-child(2) .position-badge { background: rgba(255, 255, 255, 0.1); color: #cbd5e1; }
        .ranking-item:nth-child(3) .position-badge { background: rgba(180, 83, 9, 0.15); color: #b45309; }

        .product-name {
            font-size: 13px;
            font-weight: 500;
            color: #e2e8f0;
        }

        .qty-tag {
            font-size: 12px;
            font-weight: 600;
            color: var(--orange-brand);
            background: rgba(249, 115, 22, 0.08);
            padding: 2px 8px;
            border-radius: 6px;
            border: 1px solid rgba(249, 115, 22, 0.1);
        }

        /* FILTROS */
        .filter-flex {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            align-items: center;
        }

        .input-dark {
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.08);
            padding: 10px 14px;
            border-radius: 10px;
            color: #ffffff;
            font-size: 13.5px;
            outline: none;
            transition: border-color 0.2s;
        }

        .input-dark:focus { border-color: var(--orange-brand); }

        .btn-orange {
            background: var(--orange-brand);
            color: #ffffff;
            padding: 10px 20px;
            border-radius: 10px;
            font-size: 13.5px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: background 0.2s;
        }

        .btn-orange:hover { background: #ea580c; }

        /* TABELA PREMIUM */
        .table-responsive {
            width: 100%;
            overflow-x: auto;
        }

        .table-modern {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 14px;
        }

        .table-modern th {
            padding: 14px 16px;
            color: var(--text-muted);
            font-weight: 600;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
        }

        .table-modern td {
            padding: 16px;
            color: #cbd5e1;
            border-bottom: 1px solid rgba(255, 255, 255, 0.03);
        }

        .table-modern tr:hover td {
            background: rgba(255, 255, 255, 0.01);
            color: #ffffff;
        }

        .action-link {
            color: var(--orange-brand);
            text-decoration: none;
            font-weight: 600;
            font-size: 13px;
            transition: color 0.2s;
        }

        .action-link:hover { color: #ff974d; text-decoration: underline; }

        .invoice-actions {
            display: flex;
            gap: 8px;
            justify-content: flex-end;
            flex-wrap: wrap;
        }

        .invoice-action {
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 8px;
            color: #e2e8f0;
            display: inline-flex;
            font-size: 12px;
            font-weight: 800;
            min-height: 32px;
            padding: 7px 10px;
            text-decoration: none;
            white-space: nowrap;
        }

        .invoice-action.primary {
            background: rgba(249, 115, 22, 0.14);
            border-color: rgba(249, 115, 22, 0.28);
            color: #fdba74;
        }

        .invoice-action.danger {
            background: rgba(239, 68, 68, 0.12);
            border-color: rgba(239, 68, 68, 0.28);
            color: #fca5a5;
        }

        .invoice-badge {
            border-radius: 999px;
            display: inline-flex;
            font-size: 11px;
            font-weight: 900;
            padding: 4px 8px;
        }

        .invoice-badge.ok {
            background: rgba(34, 197, 94, .12);
            color: #86efac;
        }

        .invoice-badge.warn {
            background: rgba(249, 115, 22, .14);
            color: #fdba74;
        }

        .invoice-badge.danger {
            background: rgba(239, 68, 68, .12);
            color: #fca5a5;
        }

        .nc-links {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-top: 7px;
        }

        .invoice-doc-meta {
            color: var(--text-muted);
            font-size: 12px;
            margin-top: 3px;
        }

        .invoice-agt-box {
            align-items: flex-start;
            background: rgba(255, 255, 255, .025);
            border: 1px solid rgba(255, 255, 255, .055);
            border-radius: 8px;
            display: inline-flex;
            flex-direction: column;
            gap: 4px;
            margin-top: 9px;
            max-width: 260px;
            padding: 7px 9px;
        }

        .invoice-agt-title {
            color: var(--text-muted);
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .invoice-agt-id {
            color: var(--text-muted);
            font-family: ui-monospace, SFMono-Regular, Consolas, monospace;
            font-size: 11px;
            word-break: break-word;
        }

        .nc-link small {
            color: #fca5a5;
            font-size: 10px;
            margin-left: 4px;
            opacity: .9;
        }

        .nc-link {
            background: rgba(239, 68, 68, 0.10);
            border: 1px solid rgba(239, 68, 68, 0.24);
            border-radius: 999px;
            color: #fca5a5;
            font-size: 11px;
            font-weight: 900;
            padding: 4px 8px;
            text-decoration: none;
        }

        /* INSIGHTS ASSISTENTE */
        .insight-box {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 16px;
            background: rgba(15, 23, 42, 0.4);
            border: 1px solid rgba(255, 255, 255, 0.03);
            border-radius: 10px;
            margin-bottom: 8px;
            font-size: 13.5px;
            color: #cbd5e1;
        }
    </style>

    <div class="dashboard-container">
        @php
            $viewTicket = \App\Services\ModuleSettings::enabled('view_ticket');
        @endphp

        <div class="sap-grid">
            <div class="kpi-card">
                <div class="kpi-header"><span class="kpi-label">Faturação Total</span><span>💰</span></div>
                <div class="kpi-num">AOA {{ number_format($totalSales, 2) }}</div>
            </div>

            <div class="kpi-card">
                <div class="kpi-header"><span class="kpi-label">Vendas Hoje</span><span>📅</span></div>
                <div class="kpi-num" style="color: var(--orange-brand);">AOA {{ number_format($todaySales, 2) }}</div>
            </div>

            <div class="kpi-card">
                <div class="kpi-header"><span class="kpi-label">Crescimento</span><span>📈</span></div>
                <div class="kpi-num" style="color:{{ $growth >= 0 ? 'var(--green-brand)' : 'var(--red-brand)' }}">
                    {{ $growth >= 0 ? '+' : '' }}{{ number_format($growth, 1) }}%
                </div>
            </div>

            <div class="kpi-card">
                <div class="kpi-header"><span class="kpi-label">Ticket Médio</span><span>🎟</span></div>
                <div class="kpi-num">AOA {{ number_format($avgTicket, 2) }}</div>
            </div>
        </div>

        <div class="sap-grid-5">
            <div class="kpi-card">
                <div class="kpi-header"><span class="kpi-label">Físico (Cash)</span><span>💵</span></div>
                <div class="kpi-num" style="font-size: 18px;">AOA {{ number_format($paymentCash, 2) }}</div>
            </div>

            <div class="kpi-card">
                <div class="kpi-header"><span class="kpi-label">Cartão (Card)</span><span>💳</span></div>
                <div class="kpi-num" style="font-size: 18px;">AOA {{ number_format($paymentCard, 2) }}</div>
            </div>

            <div class="kpi-card">
                <div class="kpi-header"><span class="kpi-label">Multicaixa</span><span>🏦</span></div>
                <div class="kpi-num" style="font-size: 18px;">AOA {{ number_format($paymentMulti, 2) }}</div>
            </div>

            <div class="kpi-card">
                <div class="kpi-header"><span class="kpi-label">Transferência</span><span>🏦</span></div>
                <div class="kpi-num" style="font-size: 18px;">AOA {{ number_format($paymentTransf, 2) }}</div>
            </div>

            <div class="kpi-card">
                <div class="kpi-header"><span class="kpi-label">Total Faturas</span><span>🧾</span></div>
                <div class="kpi-num" style="font-size: 18px;">{{ $totalInvoices }}</div>
            </div>
        </div>

        <div class="dashboard-row">
            
            <div class="panel-custom" style="margin-bottom: 0;">
                <h3 class="panel-title">📊 Histórico de Faturação (Últimos 7 dias)</h3>
                <div style="height:290px; margin-top: 10px;">
                    <canvas id="chart"></canvas>
                </div>
            </div>

            <div class="panel-custom" id="top-produtos" style="margin-bottom: 0;">
                <h3 class="panel-title">🏆 Mais Vendidos (Top 5)</h3>
                <div class="ranking-list">
                    @foreach($topProducts as $index => $item)
                        <div class="ranking-item">
                            <div class="product-info">
                                <div class="position-badge">{{ $index + 1 }}</div>
                                <span class="product-name" title="{{ $item->product->name ?? 'Produto Eliminado' }}">
                                    {{ \Illuminate\Support\Str::limit($item->product->name ?? 'Produto Eliminado', 18) }}
                                </span>
                            </div>
                            <div class="qty-tag">
                                {{ number_format($item->total_qty, 0) }} un
                            </div>
                        </div>
                    @endforeach

                    @if($topProducts->isEmpty())
                        <div style="text-align: center; color: var(--text-muted); padding: 50px 0; font-size: 13px;">
                            Sem histórico de saídas.
                        </div>
                    @endif
                </div>
            </div>

        </div>

   

        <div class="panel-custom">
            <div style="align-items:center; display:flex; gap:12px; justify-content:space-between; margin-bottom:16px;">
                <h3 class="panel-title" style="margin-bottom:0;">Facturas emitidas</h3>
                @if(\App\Services\OperatorPermissions::allows(session('operator_role'), 'sales.create'))
                    <a class="invoice-action primary" href="{{ route('admin.sales.create') }}">Nova venda</a>
                @endif
            </div>

            <form method="GET" action="{{ route('admin.sales.index') }}" class="filter-flex" style="margin-bottom: 16px;">
                <input class="input-dark" type="text" name="search" value="{{ request('search') }}" placeholder="No. factura ou cliente">
                <input class="input-dark" type="date" name="from" value="{{ request('from') }}">
                <input class="input-dark" type="date" name="to" value="{{ request('to') }}">
                <button class="btn-orange" type="submit">Filtrar</button>
                <a class="invoice-action" href="{{ route('admin.sales.index') }}">Limpar</a>
            </form>

            <div class="table-responsive">
                <table class="table-modern">
                    <thead>
                        <tr>
                            <th>Documento</th>
                            <th>Cliente</th>
                            <th>Data</th>
                            <th>Total</th>
                            <th>Estado</th>
                            <th style="text-align:right;">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($sales as $sale)
                            @php
                                $creditedTotal = (float) $sale->creditNotes->sum('total');
                                $availableToCredit = max((float) $sale->total - $creditedTotal, 0);
                            @endphp
                            <tr>
                                <td>
                                    <strong style="color:#fff;">{{ $sale->invoice_number }}</strong>
                                    <div class="invoice-doc-meta">{{ $sale->document_type_code ?? 'FR' }} · {{ strtoupper($sale->payment_method ?? '-') }}</div>
                                    <div class="invoice-agt-box">
                                        <span class="invoice-agt-title">Estado AGT</span>
                                        <span class="invoice-badge {{ $agtBadgeClass($sale->agtDocument?->status) }}">{{ $sale->agtDocument?->status_label ?? 'Nao enviada' }}</span>
                                        @if($sale->agtDocument?->external_id)
                                            <span class="invoice-agt-id">{{ $sale->agtDocument->external_id }}</span>
                                        @endif
                                    </div>
                                    @if($sale->creditNotes->isNotEmpty())
                                        <div class="nc-links">
                                            @foreach($sale->creditNotes as $note)
                                                @php
                                                    $noteTicketUrl = route('admin.credit-notes.ticket', $note);
                                                    $notePrintUrl = route('admin.print.credit-notes', $note);
                                                @endphp
                                                <a class="nc-link"
                                                    href="{{ $viewTicket ? $noteTicketUrl : $notePrintUrl }}"
                                                    @if($viewTicket) target="_blank" @else data-direct-print-url="{{ $notePrintUrl }}" @endif>
                                                    {{ $viewTicket ? 'NC' : 'Imprimir NC' }} {{ $note->invoice_number }} <small>AGT: {{ $note->agtDocument?->status_label ?? 'Nao enviada' }}</small>
                                                </a>
                                            @endforeach
                                        </div>
                                    @endif
                                </td>
                                <td>{{ $sale->customer->name ?? 'Consumidor Final' }}</td>
                                <td>{{ optional($sale->created_at)->format('d/m/Y H:i') }}</td>
                                <td>AOA {{ number_format((float) $sale->total, 2, ',', '.') }}</td>
                                <td>
                                    @if($creditedTotal >= (float) $sale->total && (float) $sale->total > 0)
                                        <span class="invoice-badge danger">Anulada por NC</span>
                                    @elseif($creditedTotal > 0)
                                        <span class="invoice-badge warn">NC parcial</span>
                                    @else
                                        <span class="invoice-badge ok">Emitida</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="invoice-actions">
                                        <a class="invoice-action" href="{{ route('admin.sales.show', $sale) }}">Ver</a>
                                        @php
                                            $saleTicketUrl = route('admin.sales.ticket', $sale);
                                            $salePrintUrl = route('admin.print.sales', $sale);
                                        @endphp
                                        <a class="invoice-action primary"
                                            href="{{ $viewTicket ? $saleTicketUrl : $salePrintUrl }}"
                                            @if($viewTicket) target="_blank" @else data-direct-print-url="{{ $salePrintUrl }}" @endif>
                                            {{ $viewTicket ? 'Ticket' : 'Imprimir' }}
                                        </a>
                                        @if(\App\Services\OperatorPermissions::allows(session('operator_role'), 'sales.credit_note') && $availableToCredit > 0)
                                            <a class="invoice-action danger" href="{{ route('admin.sales.credit-notes.create', $sale) }}">Anular/NC</a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" style="text-align:center; color:var(--text-muted); padding:28px;">Nenhuma factura encontrada.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div style="margin-top: 16px;">
                {{ $sales->links() }}
            </div>
        </div>

        <div class="panel-custom" style="margin-bottom: 0;">
            <h3 class="panel-title">🧠 Assistente de Insights MARIA ERP</h3>
            <div style="margin-top: 12px;">
                @foreach ($insights as $i)
                    <div class="insight-box">
                        <span>{{ $i }}</span>
                    </div>
                @endforeach
            </div>
        </div>

    </div>

    <script src="{{ asset('vendor/offline/chart.umd.min.js') }}"></script>
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const labels = @json($chartLabels);
            const data = @json($chartData);
            const el = document.getElementById('chart');

            if (!el) return;

            new Chart(el, {
                type: 'line',
                data: {
                    labels,
                    datasets: [{
                        label: 'Faturação (AOA)',
                        data,
                        borderColor: '#f97316',
                        backgroundColor: 'rgba(249, 115, 22, 0.05)',
                        borderWidth: 3,
                        pointBackgroundColor: '#f97316',
                        pointHoverRadius: 6,
                        fill: true,
                        tension: 0.38
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            ticks: { color: '#64748b', font: { size: 11 } }
                        },
                        y: {
                            grid: { color: 'rgba(255,255,255,0.01)' },
                            ticks: { color: '#64748b', font: { size: 11 } }
                        }
                    }
                }
            });
        });
    </script>
@endsection
