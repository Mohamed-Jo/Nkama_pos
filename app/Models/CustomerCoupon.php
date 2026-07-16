<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerCoupon extends Model
{
    protected $fillable = [
        'customer_id',
        'coupon_code',
        'discount_type',
        'discount_value',
        'valid_until',
        'status',
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'valid_until' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}