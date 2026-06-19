<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\Shift;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $today = Carbon::today();
        $now = Carbon::now();

        // =====================
        // 💰 VENDAS & TRANSAÇÕES
        // =====================
        $todaySales = Sale::whereDate('created_at', $today)->sum('total');
        $totalSales = Sale::sum('total');
        $totalTransactions = Sale::count();

        // Média diária real dos últimos 30 dias
        $salesAverage = Sale::whereDate('created_at', '>=', $now->copy()->subDays(30))
            ->avg('total') ?? 0;

        // =====================
        // 📦 PRODUTOS
        // =====================
        $productsCount = Product::count();
        
        // Stock menor ou igual ao stock mínimo estipulado
        $lowStock = Product::whereColumn('stock_quantity', '<=', 'minimum_stock')->count();

        // =====================
        // 👥 CLIENTES
        // =====================
        $customers = Customer::count();

        // =====================
        // 🏦 CAIXA / SHIFT
        // =====================
        $shiftOpen = Shift::where('status', 'open')
            ->where('operator_id', session('operator_id'))
            ->exists();

        // =====================
        // 📊 SALES CHART (Últimos 7 dias cronológicos)
        // =====================
        $salesChart = Sale::selectRaw('DATE(created_at) as date, SUM(total) as total')
            ->whereDate('created_at', '>=', $now->copy()->subDays(6))
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get()
            ->values();

        // =====================
        // 🚚 FORNECEDORES & CRESCIMENTO
        // =====================
        $activeSuppliers = Supplier::where('status', 1)->count();
        $inactiveSuppliers = Supplier::where('status', 0)->count();
        $totalSuppliers = $activeSuppliers + $inactiveSuppliers;

        // Correção de Datas para o Crescimento Mensal (Mês e Ano validados)
        $startOfThisMonth = $now->copy()->startOfMonth();
        $endOfThisMonth = $now->copy()->endOfMonth();
        $startOfLastMonth = $now->copy()->subMonth()->startOfMonth();
        $endOfLastMonth = $now->copy()->subMonth()->endOfMonth();

        $thisMonth = Supplier::whereBetween('created_at', [$startOfThisMonth, $endOfThisMonth])->count();
        $lastMonth = Supplier::whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])->count();

        $growth = $lastMonth > 0
            ? (($thisMonth - $lastMonth) / $lastMonth) * 100
            : ($thisMonth > 0 ? 100 : 0);

        // =====================
        // 🧠 INSIGHTS INTELIGENTES
        // =====================
        $insights = [];

        if ($lowStock > 5) {
            $insights[] = "📦 Atenção: Tens $lowStock produtos com stock crítico ou rutura.";
        }

        if ($inactiveSuppliers > $activeSuppliers && $totalSuppliers > 0) {
            $insights[] = "⚠️ Alerta: A tua lista tem mais fornecedores inativos do que ativos.";
        }

        if ($todaySales < ($salesAverage * 0.5) && $todaySales > 0) {
            $insights[] = "📉 O volume de faturação de hoje está abaixo de 50% da tua média mensal.";
        }

        if ($growth > 20) {
            $insights[] = "📈 Ritmo forte: A tua base de fornecedores cresceu " . number_format($growth, 1) . "% em relação ao mês passado.";
        }

        if (empty($insights)) {
            $insights[] = "📊 Tudo operacional. O sistema apresenta um comportamento saudável e estável.";
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