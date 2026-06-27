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
        'unit',
        'description',
        'image',
        'status',
        'available_restaurant',
        'available_supermarket',
    ];

    protected $casts = [
        'selling_price' => 'decimal:2',
        'tax_rate' => 'decimal:2',
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
}
