<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SupplierController extends Controller
{
    public function index(): View
    {
        $suppliers = Supplier::latest()->paginate(10);

        $totalSuppliers = Supplier::count();
        $activeSuppliers = Supplier::where('status', 1)->count();
        $inactiveSuppliers = Supplier::where('status', 0)->count();

        $thisMonth = Supplier::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        $lastMonthDate = now()->subMonth();
        $lastMonth = Supplier::whereMonth('created_at', $lastMonthDate->month)
            ->whereYear('created_at', $lastMonthDate->year)
            ->count();

        $growth = $lastMonth > 0
            ? (($thisMonth - $lastMonth) / $lastMonth) * 100
            : ($thisMonth > 0 ? 100 : 0);

        $insight = match (true) {
            $totalSuppliers === 0 => 'Nenhum fornecedor registado no sistema.',
            $inactiveSuppliers > ($activeSuppliers * 0.5) => 'Alerta: muitos fornecedores inativos. Reveja contratos e condicoes comerciais.',
            $growth > 20 => 'Crescimento forte: a rede de fornecedores expandiu este mes.',
            default => 'Sistema estavel. A distribuicao de fornecedores esta equilibrada.',
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

    public function create(): View
    {
        return view('admin.suppliers.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateSupplier($request);

        Supplier::create(array_merge($validated, ['status' => 1]));

        return redirect()
            ->route('admin.suppliers.index')
            ->with('success', 'Fornecedor registado com sucesso.');
    }

    public function show(Supplier $supplier): RedirectResponse
    {
        return redirect()->route('admin.suppliers.edit', $supplier);
    }

    public function edit(Supplier $supplier): View
    {
        return view('admin.suppliers.edit', compact('supplier'));
    }

    public function update(Request $request, Supplier $supplier): RedirectResponse
    {
        $validated = $this->validateSupplier($request, $supplier);
        $supplier->update($validated);

        return redirect()
            ->route('admin.suppliers.index')
            ->with('success', 'Fornecedor atualizado com sucesso.');
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        if ($supplier->purchases()->exists()) {
            return redirect()
                ->route('admin.suppliers.index')
                ->with('error', 'Este fornecedor ja possui compras associadas e nao pode ser removido. Pode inativa-lo na edicao.');
        }

        $supplier->delete();

        return redirect()
            ->route('admin.suppliers.index')
            ->with('success', 'Fornecedor removido com sucesso.');
    }

    private function validateSupplier(Request $request, ?Supplier $supplier = null): array
    {
        return $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('suppliers', 'email')->ignore($supplier?->id)],
            'address' => ['nullable', 'string', 'max:500'],
            'status' => ['sometimes', 'boolean'],
        ]);
    }
}