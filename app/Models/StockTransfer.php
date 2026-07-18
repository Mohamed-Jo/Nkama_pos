<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockTransfer extends Model
{
    protected $fillable = [
        'reference',
        'from_warehouse_id',
        'to_warehouse_id',
        'operator_id',
        'status',
        'notes',
    ];

    public function fromWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    public function toWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }

    public function operator()
    {
        return $this->belongsTo(Operator::class);
    }

    public function items()
    {
        return $this->hasMany(StockTransferItem::class);
    }
}
