<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index(): View
    {
        $products = Product::with('category')
            ->orderBy('name')
            ->paginate(20);

        return view('admin.products.index', compact('products'));
    }

    public function create(): View
    {
        $categories = Category::orderBy('name')->get();

        return view('admin.products.create', compact('categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'barcode' => 'nullable|string|max:100|unique:products,barcode',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'description' => 'nullable|string',
            'available_restaurant' => 'sometimes|boolean',
            'available_supermarket' => 'sometimes|boolean',
        ]);

        Product::create([
            'name' => $validated['name'],
            'category_id' => $validated['category_id'],
            'barcode' => $validated['barcode'] ?? null,
            'selling_price' => $validated['price'],
            'stock_quantity' => $validated['stock'],
            'description' => $validated['description'] ?? null,
            'available_restaurant' => $request->has('available_restaurant'),
            'available_supermarket' => $request->has('available_supermarket'),
        ]);

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'Produto criado com sucesso!');
    }

    public function show(Product $product): View
    {
        return view('admin.products.show', compact('product'));
    }

    public function edit(Product $product): View
    {
        $categories = Category::orderBy('name')->get();

        return view('admin.products.edit', compact('product', 'categories'));
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'barcode' => ['nullable', 'string', 'max:100', Rule::unique('products', 'barcode')->ignore($product->id)],
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'description' => 'nullable|string',
            'available_restaurant' => 'sometimes|boolean',
            'available_supermarket' => 'sometimes|boolean',
        ]);

        $product->update([
            'name' => $validated['name'],
            'category_id' => $validated['category_id'],
            'barcode' => $validated['barcode'] ?? null,
            'selling_price' => $validated['price'],
            'stock_quantity' => $validated['stock'],
            'description' => $validated['description'] ?? null,
            'available_restaurant' => $request->has('available_restaurant'),
            'available_supermarket' => $request->has('available_supermarket'),
        ]);

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'Produto atualizado com sucesso!');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'Produto removido com sucesso!');
    }
}
