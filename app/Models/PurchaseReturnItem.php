<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;

use Illuminate\Database\Eloquent\Model;

class PurchaseReturnItem extends Model
{
    use BelongsToCompany;

    protected $fillable = ['purchase_return_id', 'purchase_item_id', 'product_id', 'quantity', 'unit_cost', 'tax_rate', 'total'];

    protected $casts = ['quantity' => 'integer', 'unit_cost' => 'decimal:2', 'tax_rate' => 'decimal:2', 'total' => 'decimal:2'];

    public function purchaseReturn()
    {
        return $this->belongsTo(PurchaseReturn::class);
    }

    public function purchaseItem()
    {
        return $this->belongsTo(PurchaseItem::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
