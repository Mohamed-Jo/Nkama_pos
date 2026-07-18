<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\SaleItem;
use App\Models\StockMovement;
use App\Services\BusinessSettings;
use App\Services\StockWarehouseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $products = Product::with('category')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();
                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('barcode', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('category_id'), fn ($query) => $query->where('category_id', $request->integer('category_id')))
            ->when($request->filled('availability'), function ($query) use ($request) {
                match ($request->input('availability')) {
                    'restaurant' => $query->where('available_restaurant', true),
                    'supermarket' => $query->where('available_supermarket', true),
                    'inactive' => $query->where('status', false),
                    default => null,
                };
            })
            ->when($request->filled('stock_status'), function ($query) use ($request) {
                match ($request->input('stock_status')) {
                    'out' => $query->where('track_stock', true)->where('stock_quantity', '<=', 0),
                    'low' => $query->where('track_stock', true)->where('stock_quantity', '>', 0)->whereColumn('stock_quantity', '<=', 'minimum_stock'),
                    'ok' => $query->where('track_stock', true)->whereColumn('stock_quantity', '>', 'minimum_stock'),
                    'untracked' => $query->where('track_stock', false),
                    default => null,
                };
            })
            ->orderByRaw('CASE WHEN track_stock = 1 AND stock_quantity <= 0 THEN 0 WHEN track_stock = 1 AND stock_quantity <= minimum_stock THEN 1 ELSE 2 END')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.products.index', [
            'products' => $products,
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        $categories = Category::orderBy('name')->get();
        $defaultTax = BusinessSettings::tax();
        $defaultTaxRate = ($defaultTax['active'] ?? false) ? ($defaultTax['value'] ?? 0) : 0;

        return view('admin.products.create', compact('categories', 'defaultTaxRate'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatedPayload($request);

        $product = Product::create($this->productPayload($request, $validated));

        if ($product->track_stock && app(StockWarehouseService::class)->enabled()) {
            app(StockWarehouseService::class)->set($product, (int) $product->stock_quantity, 'adjustments');
        }

        if ((int) $product->stock_quantity > 0 && $product->track_stock) {
            StockMovement::create([
                'product_id' => $product->id,
                'type' => 'IN',
                'reason' => 'Stock inicial',
                'quantity' => (int) $product->stock_quantity,
                'stock_before' => 0,
                'stock_after' => (int) $product->stock_quantity,
                'notes' => 'Criacao do produto',
                'reference_type' => 'product_create',
                'user_id' => auth()->id(),
                'operator_id' => session('operator_id'),
            ]);
        }

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'Produto criado com sucesso!');
    }

    public function show(Product $product): View
    {
        $product->load('category');
        $movements = $product->stockMovements()->with('operator')->latest()->limit(20)->get();
        $sold30 = (float) SaleItem::where('product_id', $product->id)->where('created_at', '>=', now()->subDays(30))->sum('quantity');
        $soldTotal30 = (float) SaleItem::where('product_id', $product->id)->where('created_at', '>=', now()->subDays(30))->sum('subtotal');
        $avgDaily = $sold30 > 0 ? $sold30 / 30 : 0;
        $daysCoverage = $avgDaily > 0 ? floor((float) $product->stock_quantity / $avgDaily) : null;
        $purchaseAvg = (float) $product->purchaseItems()->avg('unit_cost');

        return view('admin.products.show', compact('product', 'movements', 'sold30', 'soldTotal30', 'daysCoverage', 'purchaseAvg'));
    }

    public function edit(Product $product): View
    {
        $categories = Category::orderBy('name')->get();

        return view('admin.products.edit', compact('product', 'categories'));
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $validated = $this->validatedPayload($request, $product);
        $stockBefore = (int) $product->stock_quantity;

        $product->update($this->productPayload($request, $validated));

        $stockAfter = (int) $product->stock_quantity;
        if ($product->track_stock && $stockAfter !== $stockBefore) {
            if (app(StockWarehouseService::class)->enabled()) {
                $delta = $stockAfter - $stockBefore;
                try {
                    if ($delta > 0) {
                        app(StockWarehouseService::class)->increase($product, $delta, 'adjustments');
                    } else {
                        app(StockWarehouseService::class)->decrease($product, abs($delta), 'adjustments');
                    }
                } catch (\RuntimeException $e) {
                    return back()->withInput()->with('error', $e->getMessage());
                }
                $stockAfter = (int) $product->fresh()->stock_quantity;
            }

            StockMovement::create([
                'product_id' => $product->id,
                'type' => $stockAfter > $stockBefore ? 'IN' : 'OUT',
                'reason' => 'Edicao do produto',
                'quantity' => abs($stockAfter - $stockBefore),
                'stock_before' => $stockBefore,
                'stock_after' => $stockAfter,
                'notes' => 'Alteracao manual no cadastro',
                'reference_type' => 'product_update',
                'user_id' => auth()->id(),
                'operator_id' => session('operator_id'),
            ]);
        }

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

    private function validatedPayload(Request $request, ?Product $product = null): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'barcode' => ['nullable', 'string', 'max:100', Rule::unique('products', 'barcode')->ignore($product?->id)],
            'purchase_price' => 'nullable|numeric|min:0',
            'price' => 'required|numeric|min:0',
            'tax_rate' => 'required|numeric|min:0|max:100',
            'stock' => 'required|integer|min:0',
            'minimum_stock' => 'required|integer|min:0',
            'target_stock' => 'nullable|integer|min:0',
            'unit' => 'required|string|max:20',
            'stock_location' => 'nullable|string|max:120',
            'description' => 'nullable|string',
            'status' => 'sometimes|boolean',
            'track_stock' => 'sometimes|boolean',
            'available_restaurant' => 'sometimes|boolean',
            'available_supermarket' => 'sometimes|boolean',
        ]);
    }

    private function productPayload(Request $request, array $validated): array
    {
        return [
            'name' => $validated['name'],
            'category_id' => $validated['category_id'],
            'barcode' => $validated['barcode'] ?? null,
            'purchase_price' => $validated['purchase_price'] ?? 0,
            'selling_price' => $validated['price'],
            'tax_rate' => $validated['tax_rate'],
            'stock_quantity' => $validated['stock'],
            'minimum_stock' => $validated['minimum_stock'],
            'target_stock' => $validated['target_stock'] ?? 0,
            'unit' => $validated['unit'],
            'stock_location' => $validated['stock_location'] ?? null,
            'description' => $validated['description'] ?? null,
            'status' => $request->has('status'),
            'track_stock' => $request->has('track_stock'),
            'available_restaurant' => $request->has('available_restaurant'),
            'available_supermarket' => $request->has('available_supermarket'),
        ];
    }
}
