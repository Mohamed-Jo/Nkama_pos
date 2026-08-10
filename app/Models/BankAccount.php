<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BankAccount extends Model
{
    protected $fillable = [
        'name',
        'bank_name',
        'account_number',
        'iban',
        'currency',
        'opening_balance',
        'current_balance',
        'active',
        'notes',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'active' => 'boolean',
    ];

    public function transactions()
    {
        return $this->hasMany(BankTransaction::class);
    }
}