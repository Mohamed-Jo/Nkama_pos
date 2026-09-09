<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\PaymentMethod;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PaymentMethodController extends Controller
{
    public function index(): View
    {
        return view('admin.payment-methods.index', [
            'methods' => PaymentMethod::with('bankAccount')->orderBy('sort_order')->orderBy('name')->get(),
            'bankAccounts' => BankAccount::where('active', true)->orderBy('name')->get(),
            'types' => $this->types(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateMethod($request);
        $method = PaymentMethod::create($this->payload($request, $validated));

        AuditLogger::log('payment_method_created', 'PaymentMethod', $method->id, $method->only(['code', 'name', 'type', 'active']), 'warning');

        return back()->with('success', 'Forma de pagamento criada.');
    }

    public function update(Request $request, PaymentMethod $paymentMethod): RedirectResponse
    {
        $validated = $this->validateMethod($request, $paymentMethod);
        $before = $paymentMethod->only(['code', 'name', 'type', 'active', 'show_in_pos', 'show_in_sales', 'show_in_expenses', 'show_in_current_account']);
        $paymentMethod->update($this->payload($request, $validated));

        AuditLogger::log('payment_method_updated', 'PaymentMethod', $paymentMethod->id, [
            'before' => $before,
            'after' => $paymentMethod->only(['code', 'name', 'type', 'active', 'show_in_pos', 'show_in_sales', 'show_in_expenses', 'show_in_current_account']),
        ], 'warning');

        return back()->with('success', 'Forma de pagamento atualizada.');
    }

    private function validateMethod(Request $request, ?PaymentMethod $method = null): array
    {
        return $request->validate([
            'code' => ['required', 'string', 'max:40', 'regex:/^[a-z0-9_]+$/', Rule::unique('payment_methods', 'code')->ignore($method?->id)],
            'name' => ['required', 'string', 'max:120'],
            'type' => ['required', Rule::in(array_keys($this->types()))],
            'bank_account_id' => ['nullable', 'exists:bank_accounts,id'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'active' => ['nullable', 'boolean'],
            'show_in_pos' => ['nullable', 'boolean'],
            'show_in_sales' => ['nullable', 'boolean'],
            'show_in_expenses' => ['nullable', 'boolean'],
            'show_in_current_account' => ['nullable', 'boolean'],
            'requires_bank_account' => ['nullable', 'boolean'],
        ]);
    }

    private function payload(Request $request, array $validated): array
    {
        return [
            'code' => strtolower(trim($validated['code'])),
            'name' => trim($validated['name']),
            'type' => $validated['type'],
            'bank_account_id' => $validated['bank_account_id'] ?? null,
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
            'active' => $request->boolean('active'),
            'show_in_pos' => $request->boolean('show_in_pos'),
            'show_in_sales' => $request->boolean('show_in_sales'),
            'show_in_expenses' => $request->boolean('show_in_expenses'),
            'show_in_current_account' => $request->boolean('show_in_current_account'),
            'requires_bank_account' => $request->boolean('requires_bank_account'),
        ];
    }

    private function types(): array
    {
        return [
            'cash' => 'Numerario/Caixa',
            'card' => 'Cartao/TPA',
            'bank_transfer' => 'Transferencia',
            'bank' => 'Banco',
            'mixed' => 'Misto',
            'credit' => 'Credito/Pendente',
            'customer_card' => 'Cartao Cliente',
            'other' => 'Outro',
        ];
    }
}