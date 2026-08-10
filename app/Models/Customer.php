<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = [
        'name',
        'phone',
        'email',
        'address',
        'discount_percent',
        'price_table',
        'status'
    ];

    protected $casts = [
        'discount_percent' => 'decimal:2',
        'status' => 'boolean',
    ];

    public function card()
    {
        return $this->hasOne(CustomerCard::class);
    }

    public function coupons()
    {
        return $this->hasMany(CustomerCoupon::class);
    }
}
