<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cash_Register extends Model
{
    //



    protected $fillable = [
        'id',
        'user_id',
        'opening_amount',
        'closing_amount',
        'total_sales',
        'total_cash',
        'total_card',
        'total_multi',
        'status (open/closed)',
        'opened_at',
        'closed_at'
    ];
}

