<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PointTransaction extends Model
{
    protected $fillable = [
        'customer_card_id',
        'sale_id',
        'type',
        'points',
        'balance_after',
        'description',
    ];

    protected $casts = [
        'points' => 'integer',
        'balance_after' => 'integer',
    ];

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'earn' => 'Ganho',
            'redeem' => 'Resgate',
            'adjust' => 'Ajuste',
            default => ucfirst((string) $this->type),
        };
    }
    public function card()
    {
        return $this->belongsTo(CustomerCard::class, 'customer_card_id');
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }
}