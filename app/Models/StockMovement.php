<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    protected $fillable = [
        'product_id',
        'warehouse_id',
        'type',
        'reason',
        'quantity',
        'stock_before',
        'stock_after',
        'notes',
        'reference_type',
        'reference_id',
        'user_id',
        'operator_id'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function operator()
    {
        return $this->belongsTo(Operator::class);
    }
}
