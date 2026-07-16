<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerCard extends Model
{
    protected $fillable = [
        'customer_id',
        'card_number',
        'barcode',
        'qr_code',
        'points',
        'balance',
        'level',
        'status',
        'issued_at',
        'expires_at',
    ];

    protected $casts = [
        'points' => 'integer',
        'balance' => 'decimal:2',
        'issued_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function transactions()
    {
        return $this->hasMany(PointTransaction::class);
    }
    public function balanceTransactions()
    {
        return $this->hasMany(CustomerCardBalanceTransaction::class);
    }

    public function authorizationRequests()
    {
        return $this->hasMany(CustomerCardAuthorizationRequest::class);
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at !== null && $this->expires_at->endOfDay()->isPast();
    }
    public function getStatusLabelAttribute(): string
    {
        if ($this->is_expired) {
            return 'Expirado';
        }

        return match ($this->status) {
            'active' => 'Ativo',
            'blocked' => 'Bloqueado',
            default => ucfirst((string) $this->status),
        };
    }
    public function getNextLevelAttribute(): ?string
    {
        return match (true) {
            $this->points <= 500 => 'Prata',
            $this->points <= 2000 => 'Ouro',
            $this->points <= 5000 => 'Platina',
            default => null,
        };
    }

    public function getNextLevelPointsAttribute(): ?int
    {
        return match (true) {
            $this->points <= 500 => 501,
            $this->points <= 2000 => 2001,
            $this->points <= 5000 => 5001,
            default => null,
        };
    }
}