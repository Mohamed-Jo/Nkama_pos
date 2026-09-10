<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;

use Illuminate\Database\Eloquent\Model;

class PurchaseItem extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'purchase_id',
        'product_id',
        'quantity',
        'received_quantity',
        'returned_quantity',
        'unit_cost',
        'tax_rate',
        'subtotal',
        'tax',
        'total',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'received_quantity' => 'integer',
        'returned_quantity' => 'integer',
        'unit_cost' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function getPendingQuantityAttribute(): int
    {
        return max((int) $this->quantity - (int) $this->received_quantity, 0);
    }

    public function getReturnableQuantityAttribute(): int
    {
        return max((int) $this->received_quantity - (int) $this->returned_quantity, 0);
    }
}
