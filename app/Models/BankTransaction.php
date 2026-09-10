<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;

use Illuminate\Database\Eloquent\Model;

class BankTransaction extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'bank_account_id',
        'operator_id',
        'expense_id',
        'type',
        'method',
        'amount',
        'balance_before',
        'balance_after',
        'transaction_date',
        'reference',
        'description',
        'reconciled',
        'reconciled_at',
        'reconciled_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'transaction_date' => 'date',
        'reconciled' => 'boolean',
        'reconciled_at' => 'datetime',
    ];

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function operator()
    {
        return $this->belongsTo(Operator::class);
    }

    public function expense()
    {
        return $this->belongsTo(Expense::class);
    }

    public function reconciler()
    {
        return $this->belongsTo(Operator::class, 'reconciled_by');
    }
}