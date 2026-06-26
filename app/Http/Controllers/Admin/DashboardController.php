<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Shift;
use App\Models\Supplier;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $today = Carbon::today();
        $now = Carbon::now();
        $operatorId = session('operator_id');
        $isCashier = session('operator_role') === 'cashier';

        $paidSales = Sale::where('status', 'paid');
        if ($isCashier) {
            $paidSales->where('operator_id', $operatorId);
        }

        $todaySales = (clone $paidSales)->whereDate('created_at', $today)->sum('total');
        $totalSales = (clone $paidSales)->sum('total');
        $totalTransactions = (clone $paidSales)->count();

        $dailyTotalsLast30 = Sale::selectRaw('DATE(created_at) as sale_date, SUM(total) as total')
            ->where('status', 'paid')
            ->when($isCashier, fn ($query) => $query->where('operator_id', $operatorId))
            ->whereDate('created_at', '>=', $now->copy()->subDays(29)->toDateString())
            ->groupBy('sale_date')
            ->pluck('total', 'sale_date');

        $salesAverage = collect(range(29, 0))
            ->map(fn ($daysAgo) => (float) ($dailyTotalsLast30[$now->copy()->subDays($daysAgo)->toDateString()] ?? 0))
            ->avg() ?? 0;

        $productsCount = Product::count();
        $lowStock = Product::whereColumn('stock_quantity', '<=', 'minimum_stock')->count();
        $customers = Customer::count();

        $shiftOpen = $operatorId
            ? Shift::where('status', 'open')->where('operator_id', $operatorId)->exists()
            : false;

        $salesChartSource = Sale::selectRaw('DATE(created_at) as sale_date, SUM(total) as total')
            ->where('status', 'paid')
            ->when($isCashier, fn ($query) => $query->where('operator_id', $operatorId))
            ->whereDate('created_at', '>=', $now->copy()->subDays(6)->toDateString())
            ->groupBy('sale_date')
            ->pluck('total', 'sale_date');

        $salesChart = collect(range(6, 0))->map(function ($daysAgo) use ($now, $salesChartSource) {
            $date = $now->copy()->subDays($daysAgo);

            return [
                'date' => $date->format('d/m'),
                'total' => (float) ($salesChartSource[$date->toDateString()] ?? 0),
            ];
        });

        $activeSuppliers = Supplier::where('status', 1)->count();
        $inactiveSuppliers = Supplier::where('status', 0)->count();
        $totalSuppliers = $activeSuppliers + $inactiveSuppliers;

        $startOfThisMonth = $now->copy()->startOfMonth();
        $endOfThisMonth = $now->copy()->endOfMonth();
        $startOfLastMonth = $now->copy()->subMonth()->startOfMonth();
        $endOfLastMonth = $now->copy()->subMonth()->endOfMonth();

        $thisMonth = Supplier::whereBetween('created_at', [$startOfThisMonth, $endOfThisMonth])->count();
        $lastMonth = Supplier::whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])->count();

        $growth = $lastMonth > 0
            ? (($thisMonth - $lastMonth) / $lastMonth) * 100
            : ($thisMonth > 0 ? 100 : 0);

        $insights = [];

        if ($lowStock > 5) {
            $insights[] = "Atenção: Tens $lowStock produtos com stock crítico ou rutura.";
        }

        if ($inactiveSuppliers > $activeSuppliers && $totalSuppliers > 0) {
            $insights[] = 'Alerta: A tua lista tem mais fornecedores inativos do que ativos.';
        }

        if ($salesAverage > 0 && $todaySales < ($salesAverage * 0.5)) {
            $insights[] = 'O volume de faturação de hoje está abaixo de 50% da média diária dos últimos 30 dias.';
        }

        if ($growth > 20) {
            $insights[] = 'Ritmo forte: A tua base de fornecedores cresceu ' . number_format($growth, 1) . '% em relação ao mês passado.';
        }

        if (empty($insights)) {
            $insights[] = 'Tudo operacional. O sistema apresenta um comportamento saudável e estável.';
        }

        return view('admin.dashboard', compact(
            'todaySales',
            'totalSales',
            'salesAverage',
            'totalTransactions',
            'productsCount',
            'lowStock',
            'customers',
            'shiftOpen',
            'salesChart',
            'totalSuppliers',
            'activeSuppliers',
            'inactiveSuppliers',
            'growth',
            'insights'
        ));
    }
}
