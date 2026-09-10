<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;

use Illuminate\Database\Eloquent\Model;

class StockTransfer extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'reference',
        'from_warehouse_id',
        'to_warehouse_id',
        'operator_id',
        'status',
        'notes',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
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

    public function approver()
    {
        return $this->belongsTo(Operator::class, 'approved_by');
    }

    public function rejecter()
    {
        return $this->belongsTo(Operator::class, 'rejected_by');
    }
}
