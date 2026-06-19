<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Shift;

class Operator extends Model
{
    protected $fillable = [
        'name',
        'email',
        'pin'
    ];

    // CAIXAS DO OPERADOR
    public function shifts()
    {
        return $this->hasMany(Shift::class);
    }

    // CAIXA ABERTO
    public function openShift()
    {
        return $this->hasOne(Shift::class)
            ->where('status', 'open');
    }
}