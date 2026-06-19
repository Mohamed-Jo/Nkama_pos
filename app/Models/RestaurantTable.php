<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RestaurantTable extends Model
{
    protected $fillable = [
        'name',
        'capacity',
        'status',
        'current_order_id'
    ];

    public function orders()
    {
        return $this->hasMany(RestaurantOrder::class, 'table_id');
    }
}