<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Services\AuditLogger;
use App\Services\BusinessSettings;
use App\Services\CurrentCompany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class CompanyController extends Controller
{
    public function index(): View
    {
        $companies = Company::withCount('operators')->latest()->paginate(20);
        $currentCompanyId = CurrentCompany::id();

        return view('admin.companies.index', compact('companies', 'currentCompanyId'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateCompany($request);

        $company = Company::create($this->companyPayload($request, $validated));

        AuditLogger::log('company_registered', 'Company', $company->id, [
            'name' => $company->name,
            'nif' => $company->nif,
        ], 'warning');

        return redirect()->route('admin.companies.index')->with('success', 'Empresa registada com sucesso.');
    }

    public function update(Request $request, Company $company): RedirectResponse
    {
        $validated = $this->validateCompany($request, $company);
        $before = $company->only(['name', 'location', 'nif', 'iban', 'account_number', 'bank_name', 'swift', 'active']);

        $company->update($this->companyPayload($request, $validated, $company));

        if ((int) session('company_id') === $company->id) {
            BusinessSettings::forgetAll();
        }

        AuditLogger::log('company_updated', 'Company', $company->id, [
            'before' => $before,
            'after' => $company->only(['name', 'location', 'nif', 'iban', 'account_number', 'bank_name', 'swift', 'active']),
        ], 'warning');

        return redirect()->route('admin.companies.index')->with('success', 'Empresa atualizada com sucesso.');
    }

    public function switch(Company $company): RedirectResponse
    {
        if (! $company->active) {
            return back()->withErrors(['company' => 'Nao e possivel entrar numa empresa inativa.']);
        }

        session(['company_id' => $company->id, 'company_name' => $company->name]);
        BusinessSettings::forgetAll();

        return back()->with('success', 'Empresa ativa alterada para ' . $company->name . '.');
    }

    private function validateCompany(Request $request, ?Company $company = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'nif' => ['nullable', 'string', 'max:80'],
            'iban' => ['nullable', 'string', 'max:80'],
            'account_number' => ['nullable', 'string', 'max:80'],
            'bank_name' => ['nullable', 'string', 'max:120'],
            'swift' => ['nullable', 'string', 'max:80'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'login_background' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'remove_logo' => ['nullable', 'boolean'],
            'remove_login_background' => ['nullable', 'boolean'],
            'active' => ['nullable', 'boolean'],
        ]);
    }

    private function companyPayload(Request $request, array $validated, ?Company $company = null): array
    {
        $payload = [
            'name' => trim((string) $validated['name']),
            'location' => trim((string) ($validated['location'] ?? '')),
            'nif' => trim((string) ($validated['nif'] ?? '')),
            'iban' => trim((string) ($validated['iban'] ?? '')),
            'account_number' => trim((string) ($validated['account_number'] ?? '')),
            'bank_name' => trim((string) ($validated['bank_name'] ?? '')),
            'swift' => trim((string) ($validated['swift'] ?? '')),
            'active' => $request->boolean('active', true),
        ];

        if ($company) {
            $payload['logo_path'] = $company->logo_path;
            $payload['login_background_path'] = $company->login_background_path;
        }

        if ($request->boolean('remove_logo') && $company?->logo_path) {
            Storage::disk('public')->delete($company->logo_path);
            $payload['logo_path'] = null;
        }

        if ($request->boolean('remove_login_background') && $company?->login_background_path) {
            Storage::disk('public')->delete($company->login_background_path);
            $payload['login_background_path'] = null;
        }

        if ($request->hasFile('logo')) {
            if ($company?->logo_path) {
                Storage::disk('public')->delete($company->logo_path);
            }

            $payload['logo_path'] = $request->file('logo')->store('company', 'public');
        }

        if ($request->hasFile('login_background')) {
            if ($company?->login_background_path) {
                Storage::disk('public')->delete($company->login_background_path);
            }

            $payload['login_background_path'] = $request->file('login_background')->store('company', 'public');
        }

        return $payload;
    }
}
