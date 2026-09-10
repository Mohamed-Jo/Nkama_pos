<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;

use Illuminate\Database\Eloquent\Model;

class ProductStockBatch extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'product_id',
        'warehouse_id',
        'lot_number',
        'expires_at',
        'serial_number',
        'quantity',
        'reserved_quantity',
        'operator_id',
    ];

    protected $casts = [
        'expires_at' => 'date',
        'quantity' => 'integer',
        'reserved_quantity' => 'integer',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function operator()
    {
        return $this->belongsTo(Operator::class);
    }

    public function getAvailableQuantityAttribute(): int
    {
        return max((int) $this->quantity - (int) $this->reserved_quantity, 0);
    }
}
