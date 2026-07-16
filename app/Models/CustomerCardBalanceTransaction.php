<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerCardBalanceTransaction extends Model
{
    protected $fillable = [
        'customer_card_id',
        'sale_id',
        'shift_id',
        'operator_id',
        'type',
        'method',
        'amount',
        'balance_after',
        'description',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_after' => 'decimal:2',
    ];

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'recharge' => 'Recarga',
            'purchase' => 'Compra',
            'adjust' => 'Ajuste',
            default => ucfirst((string) $this->type),
        };
    }

    public function getMethodLabelAttribute(): string
    {
        return match ($this->method) {
            'cash' => 'Dinheiro',
            'card' => 'Multicaixa',
            'transf' => 'Transferencia',
            'customer_card' => 'Cartao Cliente',
            null, '' => '-',
            default => ucfirst((string) $this->method),
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

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    public function operator()
    {
        return $this->belongsTo(Operator::class);
    }
}