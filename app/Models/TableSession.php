<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TableSession extends Model
{
    protected $fillable = [
        'table_id',
        'operator_id',
        'status',
        'opened_at',
        'closed_at',
        'total',
        'paid',
        'balance'
    ];
    //
    public function table()
    {
        return $this->belongsTo(RestaurantTable::class);
    }
}
