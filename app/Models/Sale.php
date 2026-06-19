<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    protected $fillable = [
        'operator_id',
        'customer_id',
        'shift_id',

        'invoice_number',

        'subtotal',
        'tax',
        'discount',

        'total',
        'paid',
        'change',
        'payment_method',
        'status'
    ];

    protected $casts = [
        'total' => 'decimal:2',
        'paid' => 'decimal:2',
        'change' => 'decimal:2',
    ];


    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function payments()
    {
        return $this->hasMany(Payments::class, 'sale_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function operator()
    {
        return $this->belongsTo(Operator::class);
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }
}