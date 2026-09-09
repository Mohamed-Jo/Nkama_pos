<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    protected $fillable = [
        'code',
        'name',
        'type',
        'bank_account_id',
        'active',
        'show_in_pos',
        'show_in_sales',
        'show_in_expenses',
        'show_in_current_account',
        'requires_bank_account',
        'sort_order',
    ];

    protected $casts = [
        'active' => 'boolean',
        'show_in_pos' => 'boolean',
        'show_in_sales' => 'boolean',
        'show_in_expenses' => 'boolean',
        'show_in_current_account' => 'boolean',
        'requires_bank_account' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }
}