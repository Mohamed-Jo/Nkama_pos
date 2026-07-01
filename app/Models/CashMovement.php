<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashMovement extends Model
{
    protected $fillable = [
        'shift_id',
        'operator_id',
        'current_account_entry_id',
        'type',
        'method',
        'amount',
        'description',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    public function operator()
    {
        return $this->belongsTo(Operator::class);
    }

    public function currentAccountEntry()
    {
        return $this->belongsTo(CurrentAccountEntry::class);
    }
}
