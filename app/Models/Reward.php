<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reward extends Model
{
    protected $fillable = [
        'name',
        'required_points',
        'reward_type',
        'reward_value',
        'status',
    ];

    protected $casts = [
        'required_points' => 'integer',
        'reward_value' => 'decimal:2',
    ];
}