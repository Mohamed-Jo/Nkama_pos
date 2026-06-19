<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RestaurantOrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'product_id',
        'qty',
        'price',
        'subtotal',
        'notes'
    ];

    public function order()
    {
        return $this->belongsTo(RestaurantOrder::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}