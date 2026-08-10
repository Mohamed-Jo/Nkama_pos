<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PAID = 'paid';

    protected $fillable = [
        'supplier_id',
        'operator_id',
        'bank_account_id',
        'shift_id',
        'category',
        'description',
        'document_number',
        'expense_date',
        'due_date',
        'payment_method',
        'amount',
        'status',
        'paid_at',
        'notes',
    ];

    protected $casts = [
        'expense_date' => 'date',
        'due_date' => 'date',
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function operator()
    {
        return $this->belongsTo(Operator::class);
    }

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    public function bankTransaction()
    {
        return $this->hasOne(BankTransaction::class);
    }
}