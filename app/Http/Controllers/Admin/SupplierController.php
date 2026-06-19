<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SupplierController extends Controller
{
    /**
     * Exibe a listagem de fornecedores com KPIs, Insights e Paginação.
     */
    public function index(): View
    {
        // Paginação para manter a performance alta no ERP
        $suppliers = Supplier::latest()->paginate(10);

        $totalSuppliers = Supplier::count();
        $activeSuppliers = Supplier::where('status', 1)->count();
        $inactiveSuppliers = Supplier::where('status', 0)->count();

        // 📊 Crescimento mensal comparativo
        $thisMonth = Supplier::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
            
        $lastMonth = Supplier::whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)
            ->count();

        $growth = $lastMonth > 0
            ? (($thisMonth - $lastMonth) / $lastMonth) * 100
            : ($thisMonth > 0 ? 100 : 0);

        // 🧠 INSIGHT INTELIGENTE (ERP STYLE)
        $insight = match (true) {
            $totalSuppliers == 0
                => "📦 Nenhum fornecedor registado no sistema.",
                
            $inactiveSuppliers > ($activeSuppliers * 0.5)
                => "⚠️ Alerta: Muitos fornecedores inativos — revisão de contratos necessária.",
                
            $growth > 20
                => "📈 Crescimento forte: A rede de fornecedores expandiu de forma acentuada este mês.",
                
            default
                => "📊 Sistema estável. A distribuição de fornecedores está equilibrada."
        };

        return view('admin.suppliers.index', compact(
            'suppliers',
            'totalSuppliers',
            'activeSuppliers',
            'inactiveSuppliers',
            'growth',
            'insight'
        ));
    }

    /**
     * Exibe o formulário de criação de fornecedor.
     */
    public function create(): View
    {
        return view('admin.suppliers.create');
    }

    /**
     * Valida e armazena um novo fornecedor no sistema.
     */
    public function store(Request $request): RedirectResponse
    {
        // Segurança Robusta: Validação integral dos dados corporativos
        $validated = $request->validate([
            'company_name'   => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'phone'          => 'nullable|string|max:50',
            'email'          => 'nullable|email|max:255',
            'address'        => 'nullable|string|max:500',
        ]);

        // Criação segura com Mass Assignment ativo (Forçando status inicial ativo)
        Supplier::create(array_merge($validated, ['status' => 1]));

        // Rota corrigida para bater com o prefixo 'admin.' do teu grupo
        return redirect()
            ->route('admin.suppliers.index')
            ->with('success', 'Fornecedor registado com sucesso no ERP!');
    }
}