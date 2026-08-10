<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\CommercialPriceRule;
use App\Models\CommercialPromotion;
use App\Models\Customer;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CommercialController extends Controller
{
    public function index(): View
    {
        return view('admin.commercial.index', [
            'priceRules' => CommercialPriceRule::with('customer', 'product')->latest()->paginate(12, ['*'], 'prices'),
            'promotions' => CommercialPromotion::with('product', 'category')->latest()->paginate(12, ['*'], 'promos'),
            'customers' => Customer::where('status', true)->orderBy('name')->get(['id', 'name', 'price_table']),
            'products' => Product::where('status', true)->orderBy('name')->get(['id', 'name', 'selling_price']),
            'categories' => Category::where('status', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function storePriceRule(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:180'],
            'price_table' => ['nullable', 'string', 'max:40'],
            'customer_id' => ['nullable', 'exists:customers,id'],
            'product_id' => ['required', 'exists:products,id'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'active' => ['nullable', 'boolean'],
        ]);

        CommercialPriceRule::create([
            ...$validated,
            'active' => $request->boolean('active', true),
        ]);

        return back()->with('success', 'Preco comercial guardado.');
    }

    public function storePromotion(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:180'],
            'product_id' => ['nullable', 'exists:products,id'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'fixed_price' => ['nullable', 'numeric', 'min:0'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'active' => ['nullable', 'boolean'],
        ]);

        if (empty($validated['product_id']) && empty($validated['category_id'])) {
            return back()->withInput()->with('error', 'Escolha um produto ou uma categoria para a promocao.');
        }

        if ((float) ($validated['discount_percent'] ?? 0) <= 0 && (float) ($validated['fixed_price'] ?? 0) <= 0) {
            return back()->withInput()->with('error', 'Informe desconto percentual ou preco fixo.');
        }

        CommercialPromotion::create([
            ...$validated,
            'discount_percent' => $validated['discount_percent'] ?? 0,
            'active' => $request->boolean('active', true),
        ]);

        return back()->with('success', 'Promocao guardada.');
    }

    public function destroyPriceRule(CommercialPriceRule $priceRule): RedirectResponse
    {
        $priceRule->delete();
        return back()->with('success', 'Preco comercial removido.');
    }

    public function destroyPromotion(CommercialPromotion $promotion): RedirectResponse
    {
        $promotion->delete();
        return back()->with('success', 'Promocao removida.');
    }
}