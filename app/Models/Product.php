<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'category_id',
        'name',
        'barcode',
        'purchase_price',
        'selling_price',
        'tax_rate',
        'stock_quantity',
        'minimum_stock',
        'target_stock',
        'unit',
        'stock_location',
        'description',
        'image',
        'status',
        'track_stock',
        'available_restaurant',
        'available_supermarket',
    ];

    protected $casts = [
        'purchase_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'track_stock' => 'boolean',
        'status' => 'boolean',
        'available_restaurant' => 'boolean',
        'available_supermarket' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function saleItems()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }

    public function purchaseItems()
    {
        return $this->hasMany(PurchaseItem::class);
    }



    public function warehouseStocks()
    {
        return $this->hasMany(ProductWarehouseStock::class);
    }

    public function stockStatus(): string
    {
        if (! $this->track_stock) {
            return 'untracked';
        }

        if ((float) $this->stock_quantity <= 0) {
            return 'out';
        }

        if ((float) $this->stock_quantity <= (float) $this->minimum_stock) {
            return 'low';
        }

        return 'ok';
    }

    public function stockStatusLabel(): string
    {
        return match ($this->stockStatus()) {
            'out' => 'Rutura',
            'low' => 'Stock baixo',
            'untracked' => 'Sem controlo',
            default => 'OK',
        };
    }

    public function stockStatusClass(): string
    {
        return match ($this->stockStatus()) {
            'out' => 'stock-out',
            'low' => 'stock-low',
            'untracked' => 'stock-muted',
            default => 'stock-ok',
        };
    }
}
