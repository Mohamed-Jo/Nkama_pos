<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DocumentSeries;
use App\Models\DocumentType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DocumentSettingController extends Controller
{
    public function index(): View
    {
        return view('admin.document-settings.index', [
            'types' => DocumentType::with(['series' => fn ($query) => $query->orderByDesc('year')->orderByDesc('id')])
                ->orderBy('code')
                ->get(),
        ]);
    }

    public function storeType(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:10', 'alpha_num', Rule::unique('document_types', 'code')],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
            'affects_current_account' => ['nullable', 'boolean'],
            'is_credit_note' => ['nullable', 'boolean'],
        ]);

        DocumentType::create([
            'code' => strtoupper($validated['code']),
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'affects_current_account' => (bool) ($validated['affects_current_account'] ?? false),
            'is_credit_note' => (bool) ($validated['is_credit_note'] ?? false),
            'active' => true,
        ]);

        return back()->with('success', 'Tipo de documento criado com sucesso.');
    }

    public function storeSeries(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'document_type_id' => ['required', 'exists:document_types,id'],
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'code' => ['required', 'string', 'max:20'],
            'start_number' => ['required', 'integer', 'min:1'],
        ]);

        $exists = DocumentSeries::where('document_type_id', $validated['document_type_id'])
            ->where('year', $validated['year'])
            ->where('code', $validated['code'])
            ->exists();

        if ($exists) {
            return back()
                ->withInput()
                ->withErrors(['code' => 'Ja existe uma serie igual para este tipo de documento e ano.']);
        }

        DocumentSeries::create([
            'document_type_id' => $validated['document_type_id'],
            'year' => $validated['year'],
            'code' => strtoupper($validated['code']),
            'start_number' => $validated['start_number'],
            'current_number' => $validated['start_number'] - 1,
            'active' => true,
        ]);

        return back()->with('success', 'Serie criada com sucesso.');
    }

    public function toggleType(DocumentType $type): RedirectResponse
    {
        $type->update(['active' => ! $type->active]);

        return back()->with('success', 'Estado do tipo de documento atualizado.');
    }

    public function toggleSeries(DocumentSeries $series): RedirectResponse
    {
        $series->update(['active' => ! $series->active]);

        return back()->with('success', 'Estado da serie atualizado.');
    }
}
