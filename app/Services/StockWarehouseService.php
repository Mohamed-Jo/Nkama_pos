<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\Product;
use App\Models\ProductWarehouseStock;
use App\Models\Warehouse;
use Illuminate\Support\Collection;

class StockWarehouseService
{
    public const SETTINGS_KEY = 'stock_warehouse_defaults';

    public const OPERATIONS = [
        'supermarket' => 'Supermercado',
        'restaurant' => 'Restaurante',
        'sales' => 'Vendas administrativas',
        'purchases' => 'Compras/recebimentos',
        'adjustments' => 'Ajustes manuais',
        'inventory' => 'Inventario fisico',
        'credit_notes' => 'Notas de credito',
    ];

    public function enabled(): bool
    {
        return ModuleSettings::enabled('stock_warehouses');
    }

    public function warehouses(): Collection
    {
        $this->defaultWarehouse();

        return Warehouse::where('active', true)
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();
    }

    public function defaults(): array
    {
        $setting = AppSetting::where('key', self::SETTINGS_KEY)->first();
        $defaults = $setting?->value ?? [];
        $warehouse = $this->defaultWarehouse();

        foreach (array_keys(self::OPERATIONS) as $operation) {
            $defaults[$operation] = (int) ($defaults[$operation] ?? $warehouse->id);
        }

        return $defaults;
    }

    public function updateDefaults(array $defaults): array
    {
        $values = [];
        $fallback = $this->defaultWarehouse()->id;

        foreach (array_keys(self::OPERATIONS) as $operation) {
            $values[$operation] = (int) ($defaults[$operation] ?? $fallback);
        }

        AppSetting::updateOrCreate(
            ['key' => self::SETTINGS_KEY],
            ['value' => $values]
        );

        return $values;
    }

    public function warehouseFor(string $operation, ?int $warehouseId = null): Warehouse
    {
        if ($warehouseId) {
            $warehouse = Warehouse::where('active', true)->find($warehouseId);
            if ($warehouse) {
                return $warehouse;
            }
        }

        $defaults = $this->defaults();
        $defaultId = (int) ($defaults[$operation] ?? 0);

        return Warehouse::where('active', true)->find($defaultId) ?: $this->defaultWarehouse();
    }

    public function quantityFor(Product $product, string $operation, ?int $warehouseId = null): int
    {
        if (! $product->track_stock || ! $this->enabled()) {
            return (int) $product->stock_quantity;
        }

        $warehouse = $this->warehouseFor($operation, $warehouseId);
        $stock = $this->stockRow($product, $warehouse);

        return (int) $stock->quantity;
    }

    public function warehouseIdFor(string $operation, ?int $warehouseId = null): ?int
    {
        return $this->enabled() ? $this->warehouseFor($operation, $warehouseId)->id : null;
    }

    public function attachQuantities($products, string $operation, ?int $warehouseId = null): void
    {
        foreach ($products as $product) {
            $product->setAttribute('operation_stock_quantity', $this->quantityFor($product, $operation, $warehouseId));
        }
    }

    public function available(Product $product, int $quantity, string $operation, ?int $warehouseId = null): bool
    {
        if (! $product->track_stock) {
            return true;
        }

        if (! $this->enabled()) {
            return (int) $product->stock_quantity >= $quantity;
        }

        $warehouse = $this->warehouseFor($operation, $warehouseId);
        $stock = $this->stockRow($product, $warehouse);

        return (int) $stock->quantity >= $quantity;
    }

    public function increase(Product $product, int $quantity, string $operation, ?int $warehouseId = null): array
    {
        $before = (int) $product->stock_quantity;
        $after = $before;

        if ($product->track_stock) {
            if ($this->enabled()) {
                $warehouse = $this->warehouseFor($operation, $warehouseId);
                $stock = $this->stockRow($product, $warehouse);
                $before = (int) $stock->quantity;
                $stock->increment('quantity', $quantity);
                $after = (int) $stock->fresh()->quantity;
                $this->syncProductTotal($product);
            } else {
                $product->increment('stock_quantity', $quantity);
                $after = (int) $product->fresh()->stock_quantity;
            }
        }

        return [$before, $after];
    }

    public function decrease(Product $product, int $quantity, string $operation, ?int $warehouseId = null): array
    {
        $before = (int) $product->stock_quantity;
        $after = $before;

        if ($product->track_stock) {
            if (! $this->available($product, $quantity, $operation, $warehouseId)) {
                throw new \RuntimeException('Stock insuficiente ' . $product->name);
            }

            if ($this->enabled()) {
                $warehouse = $this->warehouseFor($operation, $warehouseId);
                $stock = $this->stockRow($product, $warehouse);
                $before = (int) $stock->quantity;
                $stock->decrement('quantity', $quantity);
                $after = (int) $stock->fresh()->quantity;
                $this->syncProductTotal($product);
            } else {
                $product->decrement('stock_quantity', $quantity);
                $after = (int) $product->fresh()->stock_quantity;
            }
        }

        return [$before, $after];
    }

    public function set(Product $product, int $quantity, string $operation, ?int $warehouseId = null): array
    {
        $before = (int) $product->stock_quantity;
        $after = $before;

        if ($product->track_stock) {
            if ($this->enabled()) {
                $warehouse = $this->warehouseFor($operation, $warehouseId);
                $stock = $this->stockRow($product, $warehouse);
                $before = (int) $stock->quantity;
                $stock->update(['quantity' => $quantity]);
                $after = (int) $stock->fresh()->quantity;
                $this->syncProductTotal($product);
            } else {
                $product->update(['stock_quantity' => $quantity]);
                $after = (int) $product->fresh()->stock_quantity;
            }
        }

        return [$before, $after];
    }

    public function stockRow(Product $product, Warehouse $warehouse): ProductWarehouseStock
    {
        return ProductWarehouseStock::firstOrCreate(
            ['product_id' => $product->id, 'warehouse_id' => $warehouse->id],
            ['quantity' => 0, 'minimum_stock' => (int) $product->minimum_stock]
        );
    }

    public function syncProductTotal(Product $product): void
    {
        $total = ProductWarehouseStock::where('product_id', $product->id)->sum('quantity');
        $product->update(['stock_quantity' => (int) $total]);
    }

    public function defaultWarehouse(): Warehouse
    {
        $warehouse = Warehouse::where('is_default', true)->first();

        if ($warehouse) {
            return $warehouse;
        }

        return Warehouse::create([
            'name' => 'Armazem Geral',
            'code' => 'GERAL',
            'location' => 'Principal',
            'is_default' => true,
            'active' => true,
        ]);
    }
}
