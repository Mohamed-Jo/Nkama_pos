<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;

use Illuminate\Database\Eloquent\Model;

class PurchaseExpense extends Model
{
    use BelongsToCompany;

    protected $fillable = ['purchase_id', 'description', 'category', 'amount', 'affects_cost', 'operator_id'];

    protected $casts = ['amount' => 'decimal:2', 'affects_cost' => 'boolean'];

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    public function operator()
    {
        return $this->belongsTo(Operator::class);
    }
}
