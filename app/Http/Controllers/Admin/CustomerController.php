<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Services\CustomerCardService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    public function index()
    {
        $customers = Customer::with('card')->latest()->paginate(20);

        return view('admin.customers.index', compact('customers'));
    }

    public function create()
    {
        return view('admin.customers.create');
    }

    public function store(Request $request, CustomerCardService $cards)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:180'],
            'phone' => ['nullable', 'string', 'max:60'],
            'email' => ['nullable', 'email', 'max:180', 'unique:customers,email'],
            'address' => ['nullable', 'string', 'max:500'],
        ]);

        $customer = Customer::create([
            'name' => $validated['name'],
            'phone' => $validated['phone'] ?? null,
            'email' => $validated['email'] ?? null,
            'address' => $validated['address'] ?? null,
            'status' => true,
        ]);

        $cards->ensureCard($customer);

        return redirect()
            ->route('admin.customers.index')
            ->with('success', 'Cliente criado com sucesso');
    }

    public function edit(Customer $customer)
    {
        $customer->load('card');

        return view('admin.customers.edit', compact('customer'));
    }

    public function update(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:180'],
            'phone' => ['nullable', 'string', 'max:60'],
            'email' => [
                'nullable',
                'email',
                'max:180',
                Rule::unique('customers', 'email')->ignore($customer->id),
            ],
            'address' => ['nullable', 'string', 'max:500'],
            'status' => ['nullable', 'boolean'],
        ]);

        $customer->update([
            'name' => $validated['name'],
            'phone' => $validated['phone'] ?? null,
            'email' => $validated['email'] ?? null,
            'address' => $validated['address'] ?? null,
            'status' => (bool) ($validated['status'] ?? false),
        ]);

        return redirect()
            ->route('admin.customers.index')
            ->with('success', 'Cliente atualizado com sucesso');
    }
}