<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CurrentAccountEntry;
use App\Models\Customer;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CurrentAccountController extends Controller
{
    public function index(Request $request): View
    {
        $customers = Customer::where('status', true)->orderBy('name')->get();
        $suppliers = Supplier::where('status', true)->orderBy('company_name')->get();

        $query = CurrentAccountEntry::query()->with('operator')->latest('entry_date')->latest('id');

        if ($request->filled('entity_type')) {
            $query->where('entity_type', $request->entity_type);
        }

        if ($request->filled('entity_type') && $request->filled('entity_id')) {
            $query->where('entity_id', $request->integer('entity_id'));
        }

        $entries = $query->paginate(15)->withQueryString();

        $totalsQuery = CurrentAccountEntry::query();

        if ($request->filled('entity_type')) {
            $totalsQuery->where('entity_type', $request->entity_type);
        }

        if ($request->filled('entity_type') && $request->filled('entity_id')) {
            $totalsQuery->where('entity_id', $request->integer('entity_id'));
        }

        $totals = $totalsQuery
            ->selectRaw('COALESCE(SUM(debit), 0) as debit, COALESCE(SUM(credit), 0) as credit')
            ->first();

        $balances = CurrentAccountEntry::query()
            ->select('entity_type', 'entity_id')
            ->selectRaw('COALESCE(SUM(debit), 0) as debit')
            ->selectRaw('COALESCE(SUM(credit), 0) as credit')
            ->groupBy('entity_type', 'entity_id')
            ->orderBy('entity_type')
            ->get()
            ->map(function ($row) {
                $row->name = $row->entity_type === 'customer'
                    ? Customer::find($row->entity_id)?->name
                    : Supplier::find($row->entity_id)?->company_name;

                $row->balance = (float) $row->debit - (float) $row->credit;

                return $row;
            })
            ->filter(fn ($row) => $row->name)
            ->values();

        return view('admin.current-accounts.index', [
            'customers' => $customers,
            'suppliers' => $suppliers,
            'entries' => $entries,
            'balances' => $balances,
            'totalDebit' => (float) $totals->debit,
            'totalCredit' => (float) $totals->credit,
            'balance' => (float) $totals->debit - (float) $totals->credit,
            'filters' => $request->only(['entity_type', 'entity_id']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'entity_type' => ['required', Rule::in(['customer', 'supplier'])],
            'entity_id' => ['required', 'integer', 'min:1'],
            'movement_type' => ['required', Rule::in(['debit', 'credit'])],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'entry_date' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $exists = $validated['entity_type'] === 'customer'
            ? Customer::whereKey($validated['entity_id'])->exists()
            : Supplier::whereKey($validated['entity_id'])->exists();

        if (! $exists) {
            return back()
                ->withInput()
                ->withErrors(['entity_id' => 'A entidade selecionada não existe.']);
        }

        DB::transaction(function () use ($validated) {
            CurrentAccountEntry::create([
                'entity_type' => $validated['entity_type'],
                'entity_id' => $validated['entity_id'],
                'entry_date' => $validated['entry_date'],
                'movement_type' => $validated['movement_type'],
                'debit' => $validated['movement_type'] === 'debit' ? $validated['amount'] : 0,
                'credit' => $validated['movement_type'] === 'credit' ? $validated['amount'] : 0,
                'description' => $validated['description'] ?? null,
                'operator_id' => session('operator_id'),
            ]);
        });

        return back()->with('success', 'Movimento registado na conta corrente.');
    }
}
