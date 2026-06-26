<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Shift;

class Operator extends Model
{
    protected $fillable = [
        'name',
        'email',
        'pin',
        'pin_fingerprint',
        'password',
        'recovery_code',
        'recovery_code_used_at',
        'role',
        'active',
    ];

    protected $hidden = [
        'pin',
        'pin_fingerprint',
        'password',
        'recovery_code',
    ];

    protected function casts(): array
    {
        return [
            'pin' => 'hashed',
            'password' => 'hashed',
            'recovery_code' => 'hashed',
            'recovery_code_used_at' => 'datetime',
            'active' => 'boolean',
        ];
    }

    public static function pinFingerprint(string $pin): string
    {
        return hash_hmac('sha256', $pin, (string) config('app.key'));
    }

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
