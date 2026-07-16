<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerCardOtp extends Model
{
    protected $fillable = [
        'customer_card_id',
        'sale_id',
        'purpose',
        'amount',
        'code_hash',
        'expires_at',
        'used_at',
        'attempts',
        'sent_to',
        'requested_by_operator_id',
        'verified_by_operator_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
        'attempts' => 'integer',
    ];

    public function card()
    {
        return $this->belongsTo(CustomerCard::class, 'customer_card_id');
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }
}