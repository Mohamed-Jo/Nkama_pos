<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Payments extends Model
{
    use HasFactory;

    protected $fillable = [
        'operator_id',
        'shift_id',
        'user_id',
        'payment_method',
        'amount',
        'reference',
        'notes',
        'sale_id',
        'method'
    ];

    protected $casts = [
        'amount' => 'decimal:2'
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    // Pagamento pertence a uma venda
    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    // Pagamento pertence ao turno de caixa
    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    // Operador que recebeu pagamento
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
}