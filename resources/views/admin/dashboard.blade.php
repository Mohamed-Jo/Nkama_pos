@extends('layouts.admin')

@section('page-title', 'Dashboard')

@section('content')
<style>
    .dashboard-container {
        --dashboard-card-bg: var(--card);
        --dashboard-card-border: var(--border);
        --dashboard-muted: var(--muted);
        --dashboard-text: var(--text);
        --accent-orange: var(--primary);
        --accent-green: #10b981;
        --accent-red: #ef4444;
        padding: 24px;
        max-width: 1600px;
        margin: 0 auto;
        font-family: system-ui, -apple-system, sans-serif;
    }

    .metrics-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 16px;
        margin-bottom: 24px;
    }

    .custom-card {
        background: var(--dashboard-card-bg);
        backdrop-filter: blur(12px);
        border: 1px solid var(--dashboard-card-border);
        border-radius: 8px;
        padding: 20px;
        transition: all 0.2s ease-in-out;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    }

    .custom-card:hover {
        transform: translateY(-2px);
        border-color: rgba(249, 115, 22, 0.3);
        box-shadow: 0 12px 20px -3px rgba(0, 0, 0, 0.3), 0 4px 6px -2px rgba(249, 115, 22, 0.05);
    }

    .card-label {
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--dashboard-muted);
        display: block;
        margin-bottom: 8px;
    }

    .card-value {
        font-size: 28px;
        font-weight: 700;
        color: var(--dashboard-text);
        line-height: 1.2;
    }

    .dashboard-layout-bottom {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 24px;
    }

    .insight-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .insight-item {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        padding: 12px 0;
        border-bottom: 1px solid var(--dashboard-card-border);
        font-size: 14px;
        color: var(--dashboard-text);
        line-height: 1.5;
    }

    .insight-item:last-child {
        border-bottom: none;
        padding-bottom: 0;
    }

    .insight-icon {
        background: rgba(249, 115, 22, 0.1);
        color: var(--accent-orange);
        padding: 4px;
        border-radius: 6px;
        font-size: 14px;
        flex-shrink: 0;
    }


    .dashboard-filter {
        align-items: center;
        background: var(--dashboard-card-bg);
        border: 1px solid var(--dashboard-card-border);
        border-radius: 8px;
        display: flex;
        gap: 10px;
        margin-bottom: 18px;
        padding: 12px;
    }

    .dashboard-filter label { color: var(--dashboard-muted); font-size: 12px; font-weight: 800; text-transform: uppercase; }
    .dashboard-filter select { background: var(--input-bg); border: 1px solid var(--border); border-radius: 8px; color: var(--input-text); padding: 9px 10px; min-width: 220px; }
    .dashboard-filter a { color: var(--accent-orange); font-weight: 800; text-decoration: none; }
    @media (max-width: 1024px) {
        .dashboard-layout-bottom {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="dashboard-container">
    <form method="GET" action="{{ route('admin.dashboard') }}" class="dashboard-filter">
        <label for="warehouse_id">Filial</label>
        <select id="warehouse_id" name="warehouse_id" onchange="this.form.submit()">
            <option value="">Todas filiais</option>
            @foreach ($warehouses as $warehouse)
                <option value="{{ $warehouse->id }}" @selected((int) $selectedWarehouseId === (int) $warehouse->id)>{{ $warehouse->name }}</option>
            @endforeach
        </select>
        @if ($selectedWarehouseId)
            <a href="{{ route('admin.dashboard') }}">Limpar</a>
        @endif
    </form>
    <div class="metrics-grid">
        <div class="custom-card">
            <span class="card-label">Vendas Hoje</span>
            <div class="card-value">{{ number_format($todaySales, 2) }} <span class="text-xs font-normal text-gray-500">Kz</span></div>
        </div>

        <div class="custom-card">
            <span class="card-label">Receita Total</span>
            <div class="card-value" style="color: var(--accent-green);">{{ number_format($totalSales, 2) }} <span class="text-xs font-normal text-gray-500">Kz</span></div>
        </div>

        <div class="custom-card">
            <span class="card-label">Transações</span>
            <div class="card-value">{{ $totalTransactions }}</div>
        </div>

        <div class="custom-card">
            <span class="card-label">Estado do Caixa</span>
            <div class="card-value flex items-center gap-2" style="color: {{ $shiftOpen ? 'var(--accent-green)' : 'var(--accent-red)' }}">
                <span class="inline-block w-2.5 h-2.5 rounded-full" style="background-color: {{ $shiftOpen ? 'var(--accent-green)' : 'var(--accent-red)' }}"></span>
                {{ $shiftOpen ? 'Aberto' : 'Fechado' }}
            </div>
        </div>

        <div class="custom-card">
            <span class="card-label">Produtos em Catálogo</span>
            <div class="card-value">{{ $productsCount }}</div>
        </div>

        <div class="custom-card">
            <span class="card-label">Stock Crítico</span>
            <div class="card-value" style="color: {{ $lowStock > 5 ? 'var(--accent-red)' : 'var(--dashboard-text)' }}">
                {{ $lowStock }}
            </div>
        </div>

        <div class="custom-card">
            <span class="card-label">Clientes Registados</span>
            <div class="card-value">{{ $customers }}</div>
        </div>

        <div class="custom-card">
            <span class="card-label">Crescimento Fornecedores</span>
            <div class="card-value" style="color: var(--accent-orange);">+{{ number_format($growth, 1) }}%</div>
        </div>
    </div>

    <div class="dashboard-layout-bottom">
        <div class="custom-card">
            <span class="card-label" style="margin-bottom: 20px;">Evolução de Vendas (Últimos 7 Dias)</span>
            <div style="position: relative; width: 100%; height: 340px;">
                <canvas id="salesChart"></canvas>
            </div>
        </div>

        <div class="custom-card flex flex-col">
            <span class="card-label" style="margin-bottom: 16px;">Insights do Sistema</span>
            <div class="flex-1 overflow-y-auto pr-1">
                <ul class="insight-list">
                    @foreach ($insights as $insight)
                        <li class="insight-item">
                            <span class="insight-icon">⚡</span>
                            <span>{{ $insight }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</div>

<script src="{{ asset('vendor/offline/chart.umd.min.js') }}"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const labels = @json($salesChart->pluck('date'));
        const data = @json($salesChart->pluck('total'));
        const ctx = document.getElementById('salesChart').getContext('2d');
        function chartTheme() {
            const styles = getComputedStyle(document.documentElement);
            const isLight = document.documentElement.dataset.theme === 'light';
            return {
                card: styles.getPropertyValue('--card').trim() || (isLight ? '#ffffff' : '#111827'),
                text: styles.getPropertyValue('--text').trim() || (isLight ? '#111827' : '#ffffff'),
                muted: styles.getPropertyValue('--muted').trim() || '#9ca3af',
                border: styles.getPropertyValue('--border').trim() || 'rgba(255,255,255,0.1)',
                grid: isLight ? 'rgba(17, 24, 39, 0.08)' : 'rgba(255, 255, 255, 0.05)',
            };
        }
        const gradient = ctx.createLinearGradient(0, 0, 0, 300);

        gradient.addColorStop(0, 'rgba(249, 115, 22, 0.25)');
        gradient.addColorStop(1, 'rgba(249, 115, 22, 0.0)');

        const salesChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Faturação Diária',
                    data: data,
                    borderColor: '#f97316',
                    borderWidth: 3,
                    pointBackgroundColor: '#f97316',
                    pointBorderColor: chartTheme().card,
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    backgroundColor: gradient,
                    fill: true,
                    tension: 0.38
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: chartTheme().card,
                        titleColor: chartTheme().muted,
                        bodyColor: chartTheme().text,
                        borderColor: chartTheme().border,
                        borderWidth: 1,
                        padding: 12,
                        boxPadding: 6,
                        displayColors: false
                    }
                },
                scales: {
                    y: {
                        grid: {
                            color: chartTheme().grid,
                            drawBorder: false
                        },
                        ticks: {
                            color: chartTheme().muted,
                            font: { size: 11 }
                        },
                        beginAtZero: true
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: chartTheme().muted,
                            font: { size: 11 }
                        }
                    }
                }
            }
        });
        function refreshChartTheme() {
            const theme = chartTheme();
            salesChart.data.datasets[0].pointBorderColor = theme.card;
            salesChart.options.plugins.tooltip.backgroundColor = theme.card;
            salesChart.options.plugins.tooltip.titleColor = theme.muted;
            salesChart.options.plugins.tooltip.bodyColor = theme.text;
            salesChart.options.plugins.tooltip.borderColor = theme.border;
            salesChart.options.scales.y.grid.color = theme.grid;
            salesChart.options.scales.y.ticks.color = theme.muted;
            salesChart.options.scales.x.ticks.color = theme.muted;
            salesChart.update('none');
        }

        new MutationObserver(refreshChartTheme).observe(document.documentElement, {
            attributes: true,
            attributeFilter: ['data-theme']
        });
    });
</script>
@endsection
