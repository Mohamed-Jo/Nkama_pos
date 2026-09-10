<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;

use Illuminate\Database\Eloquent\Model;

class StockTransferItem extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'stock_transfer_id',
        'product_id',
        'lot_number',
        'expires_at',
        'serial_number',
        'quantity',
        'from_stock_before',
        'from_stock_after',
        'to_stock_before',
        'to_stock_after',
    ];

    protected $casts = [
        'expires_at' => 'date',
    ];

    public function transfer()
    {
        return $this->belongsTo(StockTransfer::class, 'stock_transfer_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
