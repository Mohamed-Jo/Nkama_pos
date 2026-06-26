@extends('layouts.admin')

@section('page-title', 'Dashboard')

@section('content')
<style>
    :root {
        --bg-dashboard: #0b0f19;
        --card-bg: rgba(17, 24, 39, 0.7);
        --card-border: rgba(255, 255, 255, 0.06);
        --text-muted: #9ca3af;
        --accent-orange: #f97316;
        --accent-green: #10b981;
        --accent-red: #ef4444;
    }

    body {
        background-color: var(--bg-dashboard);
    }

    .dashboard-container {
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
        background: var(--card-bg);
        backdrop-filter: blur(12px);
        border: 1px solid var(--card-border);
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
        color: var(--text-muted);
        display: block;
        margin-bottom: 8px;
    }

    .card-value {
        font-size: 28px;
        font-weight: 700;
        color: #ffffff;
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
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        font-size: 14px;
        color: #e5e7eb;
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

    @media (max-width: 1024px) {
        .dashboard-layout-bottom {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="dashboard-container">
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
            <div class="card-value" style="color: {{ $lowStock > 5 ? 'var(--accent-red)' : '#ffffff' }}">
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

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const labels = @json($salesChart->pluck('date'));
        const data = @json($salesChart->pluck('total'));
        const ctx = document.getElementById('salesChart').getContext('2d');
        const gradient = ctx.createLinearGradient(0, 0, 0, 300);

        gradient.addColorStop(0, 'rgba(249, 115, 22, 0.25)');
        gradient.addColorStop(1, 'rgba(249, 115, 22, 0.0)');

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Faturação Diária',
                    data: data,
                    borderColor: '#f97316',
                    borderWidth: 3,
                    pointBackgroundColor: '#f97316',
                    pointBorderColor: '#0b0f19',
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
                        backgroundColor: '#111827',
                        titleColor: '#9ca3af',
                        bodyColor: '#ffffff',
                        borderColor: 'rgba(255,255,255,0.1)',
                        borderWidth: 1,
                        padding: 12,
                        boxPadding: 6,
                        displayColors: false
                    }
                },
                scales: {
                    y: {
                        grid: {
                            color: 'rgba(255, 255, 255, 0.05)',
                            drawBorder: false
                        },
                        ticks: {
                            color: '#9ca3af',
                            font: { size: 11 }
                        },
                        beginAtZero: true
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: '#9ca3af',
                            font: { size: 11 }
                        }
                    }
                }
            }
        });
    });
</script>
@endsection
