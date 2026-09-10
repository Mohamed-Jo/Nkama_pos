<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FiscalYear;
use App\Services\AuditLogger;
use App\Services\FiscalYearService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class FiscalYearController extends Controller
{
    public function index(): View
    {
        return view('admin.fiscal-years.index', [
            'fiscalYears' => FiscalYear::with(['opener', 'closer'])->orderByDesc('year')->get(),
            'activeFiscalYear' => FiscalYearService::active(),
            'nextYear' => ((int) (FiscalYear::max('year') ?: now()->year)) + 1,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'year' => ['required', 'integer', 'min:2000', 'max:2100', Rule::unique('fiscal_years', 'year')->where('company_id', session('company_id'))],
            'name' => ['nullable', 'string', 'max:80'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'activate' => ['nullable', 'boolean'],
        ]);

        $year = (int) $validated['year'];

        $fiscalYear = DB::transaction(function () use ($request, $validated, $year) {
            $fiscalYear = FiscalYear::create([
                'year' => $year,
                'name' => trim($validated['name'] ?? '') ?: 'Exercicio ' . $year,
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'status' => FiscalYear::STATUS_OPEN,
                'is_active' => false,
                'opened_at' => now(),
                'opened_by' => session('operator_id'),
                'notes' => $validated['notes'] ?? null,
            ]);

            if ($request->boolean('activate')) {
                FiscalYearService::open($fiscalYear, session('operator_id'));
            }

            return $fiscalYear->refresh();
        });

        AuditLogger::log('fiscal_year_created', 'FiscalYear', $fiscalYear->id, [
            'year' => $fiscalYear->year,
            'start_date' => $fiscalYear->start_date?->toDateString(),
            'end_date' => $fiscalYear->end_date?->toDateString(),
            'is_active' => $fiscalYear->is_active,
        ], 'warning');

        return back()->with('success', 'Exercicio fiscal criado.');
    }

    public function activate(FiscalYear $fiscalYear): RedirectResponse
    {
        FiscalYearService::open($fiscalYear, session('operator_id'));

        AuditLogger::log('fiscal_year_activated', 'FiscalYear', $fiscalYear->id, [
            'year' => $fiscalYear->year,
        ], 'warning');

        return back()->with('success', 'Exercicio fiscal ativo alterado.');
    }

    public function close(Request $request, FiscalYear $fiscalYear): RedirectResponse
    {
        $request->validate([
            'confirm_year' => ['required', Rule::in([(string) $fiscalYear->year])],
        ], [
            'confirm_year.in' => 'Confirme digitando o ano que pretende fechar.',
        ]);

        $hasOpenShifts = Shift::where('status', 'open')
            ->whereBetween('opened_at', [$fiscalYear->start_date, $fiscalYear->end_date])
            ->exists();

        if ($hasOpenShifts) {
            return back()->withErrors(['confirm_year' => 'Existem caixas abertos neste exercicio. Feche os caixas antes de fechar o ano.']);
        }

        FiscalYearService::close($fiscalYear, session('operator_id'));

        AuditLogger::log('fiscal_year_closed', 'FiscalYear', $fiscalYear->id, [
            'year' => $fiscalYear->year,
        ], 'critical');

        return back()->with('success', 'Exercicio fiscal fechado. Lancamentos nesse periodo ficam bloqueados.');
    }
}