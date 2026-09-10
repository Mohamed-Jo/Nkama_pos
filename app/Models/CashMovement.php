<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;

use Illuminate\Database\Eloquent\Model;

class CashMovement extends Model
{
    use BelongsToCompany;

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
