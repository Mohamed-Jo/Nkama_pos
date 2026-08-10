<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommercialPriceRule extends Model
{
    protected $fillable = [
        'name',
        'price_table',
        'customer_id',
        'product_id',
        'unit_price',
        'starts_at',
        'ends_at',
        'active',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'starts_at' => 'date',
        'ends_at' => 'date',
        'active' => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}