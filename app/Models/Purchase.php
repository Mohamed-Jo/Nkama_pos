<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    protected $fillable = [
        'supplier_id',
        'operator_id',
        'document_number',
        'purchase_date',
        'status',
        'subtotal',
        'tax',
        'total',
        'payment_type',
        'payment_status',
        'current_account_entry_id',
        'notes',
        'received_at',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'received_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function operator()
    {
        return $this->belongsTo(Operator::class);
    }

    public function items()
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function currentAccountEntry()
    {
        return $this->belongsTo(CurrentAccountEntry::class);
    }
}
