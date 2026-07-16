<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerCardAuthorizationRequest extends Model
{
    protected $fillable = [
        'customer_card_id',
        'sale_id',
        'requested_by_operator_id',
        'reviewed_by_operator_id',
        'amount',
        'reason',
        'status',
        'token_hash',
        'context',
        'decision_note',
        'approved_at',
        'rejected_at',
        'expires_at',
        'used_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'context' => 'array',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    public function card()
    {
        return $this->belongsTo(CustomerCard::class, 'customer_card_id');
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function requester()
    {
        return $this->belongsTo(Operator::class, 'requested_by_operator_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(Operator::class, 'reviewed_by_operator_id');
    }
}
