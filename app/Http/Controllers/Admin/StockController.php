<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\PurchaseItem;
use App\Models\SaleItem;
use App\Models\StockMovement;
use App\Services\StockWarehouseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class StockController extends Controller
{
    public function index(Request $request): View
    {
        $productsQuery = Product::with('category')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();
                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('barcode', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('category_id'), fn ($query) => $query->where('category_id', $request->integer('category_id')))
            ->when($request->filled('location'), fn ($query) => $query->where('stock_location', $request->input('location')))
            ->when($request->filled('status'), function ($query) use ($request) {
                match ($request->input('status')) {
                    'out' => $query->where('track_stock', true)->where('stock_quantity', '<=', 0),
                    'low' => $query->where('track_stock', true)->where('stock_quantity', '>', 0)->whereColumn('stock_quantity', '<=', 'minimum_stock'),
                    'ok' => $query->where('track_stock', true)->whereColumn('stock_quantity', '>', 'minimum_stock'),
                    'untracked' => $query->where('track_stock', false),
                    default => null,
                };
            });

        $stockWarehouseService = app(StockWarehouseService::class);
        $adjustmentWarehouseId = $request->integer('warehouse_id') ?: ($stockWarehouseService->defaults()['adjustments'] ?? null);

        $products = (clone $productsQuery)
            ->orderByRaw('CASE WHEN track_stock = 1 AND stock_quantity <= 0 THEN 0 WHEN track_stock = 1 AND stock_quantity <= minimum_stock THEN 1 ELSE 2 END')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();
        $stockWarehouseService->attachQuantities($products->getCollection(), 'adjustments', $adjustmentWarehouseId);

        $allProducts = Product::query();
        $last30Days = now()->subDays(30);
        $last60Days = now()->subDays(60);

        $topSellers = SaleItem::select('product_id', DB::raw('SUM(quantity) as sold_qty'), DB::raw('SUM(subtotal) as sold_total'))
            ->with('product')
            ->where('created_at', '>=', $last30Days)
            ->groupBy('product_id')
            ->orderByDesc('sold_qty')
            ->limit(5)
            ->get();

        $soldProductIds = SaleItem::where('created_at', '>=', $last60Days)->pluck('product_id')->unique();
        $dormantProducts = Product::with('category')
            ->where('status', true)
            ->where('stock_quantity', '>', 0)
            ->whereNotIn('id', $soldProductIds)
            ->orderByDesc('stock_quantity')
            ->limit(5)
            ->get();
        $stockWarehouseService->attachQuantities($dormantProducts, 'adjustments', $adjustmentWarehouseId);

        $recentMovements = StockMovement::with(['product', 'operator', 'warehouse'])
            ->latest()
            ->limit(8)
            ->get();

        $totals = [
            'items' => (clone $allProducts)->count(),
            'low' => Product::where('track_stock', true)->where('stock_quantity', '>', 0)->whereColumn('stock_quantity', '<=', 'minimum_stock')->count(),
            'out' => Product::where('track_stock', true)->where('stock_quantity', '<=', 0)->count(),
            'stock_value' => Product::sum(DB::raw('stock_quantity * selling_price')),
            'cost_value' => Product::sum(DB::raw('stock_quantity * purchase_price')),
            'in_today' => StockMovement::where('type', 'IN')->whereDate('created_at', today())->sum('quantity'),
            'out_today' => StockMovement::where('type', 'OUT')->whereDate('created_at', today())->sum('quantity'),
        ];

        return view('admin.stock.index', [
            'products' => $products,
            'categories' => Category::orderBy('name')->get(),
            'locations' => Product::whereNotNull('stock_location')->where('stock_location', '<>', '')->distinct()->orderBy('stock_location')->pluck('stock_location'),
            'recentMovements' => $recentMovements,
            'topSellers' => $topSellers,
            'dormantProducts' => $dormantProducts,
            'totals' => $totals,
            'warehouses' => $stockWarehouseService->warehouses(),
            'warehouseDefaults' => $stockWarehouseService->defaults(),
            'selectedWarehouseId' => $adjustmentWarehouseId,
        ]);
    }

    public function movements(Request $request): View
    {
        $movements = StockMovement::with(['product.category', 'operator', 'warehouse'])
            ->when($request->filled('product_id'), fn ($query) => $query->where('product_id', $request->integer('product_id')))
            ->when($request->filled('type'), fn ($query) => $query->where('type', $request->input('type')))
            ->when($request->filled('date_from'), fn ($query) => $query->whereDate('created_at', '>=', $request->date('date_from')))
            ->when($request->filled('date_to'), fn ($query) => $query->whereDate('created_at', '<=', $request->date('date_to')))
            ->latest()
            ->paginate(30)
            ->withQueryString();

        return view('admin.stock.movements', [
            'movements' => $movements,
            'products' => Product::orderBy('name')->get(['id', 'name', 'barcode']),
        ]);
    }

    public function adjust(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'mode' => ['required', 'in:in,out,set'],
            'quantity' => ['required', 'integer', 'min:0'],
            'reason' => ['required', 'string', 'max:120'],
            'warehouse_id' => ['nullable', 'exists:warehouses,id'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $product = Product::lockForUpdate()->findOrFail($validated['product_id']);
        $requested = (int) $validated['quantity'];
        $warehouseId = $request->integer('warehouse_id') ?: null;
        $stockService = app(StockWarehouseService::class);

        try {
            if ($validated['mode'] === 'in') {
                [$before, $after] = $stockService->increase($product, $requested, 'adjustments', $warehouseId);
            } elseif ($validated['mode'] === 'out') {
                [$before, $after] = $stockService->decrease($product, $requested, 'adjustments', $warehouseId);
            } else {
                [$before, $after] = $stockService->set($product, $requested, 'adjustments', $warehouseId);
            }
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $delta = $after - $before;
        if ($delta === 0) {
            return back()->with('success', 'Stock sem alteração.');
        }

        $movementWarehouseId = $stockService->warehouseIdFor('adjustments', $warehouseId);
        $this->recordMovement($product, $before, $after, abs($delta), $delta > 0 ? 'IN' : 'OUT', $validated['reason'], $validated['notes'] ?? null, 'manual_adjustment', $movementWarehouseId);

        return back()->with('success', 'Ajuste de stock registado.');
    }

    public function inventory(Request $request): View
    {
        $stockWarehouseService = app(StockWarehouseService::class);
        $inventoryWarehouseId = $request->integer('warehouse_id') ?: ($stockWarehouseService->defaults()['inventory'] ?? null);

        $products = Product::with('category')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();
                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('barcode', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('category_id'), fn ($query) => $query->where('category_id', $request->integer('category_id')))
            ->where('track_stock', true)
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();
        $stockWarehouseService->attachQuantities($products->getCollection(), 'inventory', $inventoryWarehouseId);

        return view('admin.stock.inventory', [
            'products' => $products,
            'categories' => Category::orderBy('name')->get(),
            'warehouses' => $stockWarehouseService->warehouses(),
            'warehouseDefaults' => $stockWarehouseService->defaults(),
            'selectedWarehouseId' => $inventoryWarehouseId,
        ]);
    }

    public function applyInventory(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'counts' => ['required', 'array'],
            'counts.*' => ['nullable', 'integer', 'min:0'],
            'warehouse_id' => ['nullable', 'exists:warehouses,id'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $changed = 0;

        DB::transaction(function () use ($validated, &$changed) {
            foreach ($validated['counts'] as $productId => $countedStock) {
                if ($countedStock === null || $countedStock === '') {
                    continue;
                }

                $product = Product::lockForUpdate()->find($productId);
                if (! $product || ! $product->track_stock) {
                    continue;
                }

                $stockService = app(StockWarehouseService::class);
                $warehouseId = (int) ($validated['warehouse_id'] ?? 0) ?: null;
                [$before, $after] = $stockService->set($product, (int) $countedStock, 'inventory', $warehouseId);
                $delta = $after - $before;

                if ($delta === 0) {
                    continue;
                }

                $movementWarehouseId = $stockService->warehouseIdFor('inventory', $warehouseId);
                $this->recordMovement($product, $before, $after, abs($delta), $delta > 0 ? 'IN' : 'OUT', 'Inventário físico', $validated['notes'] ?? null, 'physical_inventory', $movementWarehouseId);
                $changed++;
            }
        });

        return redirect()->route('admin.stock.inventory')->with('success', "Inventário aplicado. {$changed} produto(s) ajustado(s).");
    }

    public function in(Request $request): RedirectResponse
    {
        $request->merge(['mode' => 'in']);
        return $this->adjust($request);
    }

    public function out(Request $request): RedirectResponse
    {
        $request->merge(['mode' => 'out']);
        return $this->adjust($request);
    }

    private function recordMovement(Product $product, int $before, int $after, int $quantity, string $type, string $reason, ?string $notes, string $referenceType, ?int $warehouseId = null): void
    {
        StockMovement::create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouseId,
            'type' => $type,
            'reason' => $reason,
            'quantity' => $quantity,
            'stock_before' => $before,
            'stock_after' => $after,
            'notes' => $notes,
            'reference_type' => $referenceType,
            'user_id' => auth()->id(),
            'operator_id' => session('operator_id'),
        ]);
    }
}
