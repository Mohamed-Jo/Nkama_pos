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
        'status'
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