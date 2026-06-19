<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    protected $fillable = [
        'operator_id',
        'opening_cash',
        'closing_cash',
        'expected_cash',
        'difference',
        'status',
        'cash_sales_total',
        'card_sales_total',
        'multi_sales_total',
        'transf_sales_total',
        'opened_at',
        'closed_at'
    ];

    public function operator()
    {
        return $this->belongsTo(Operator::class);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }
}