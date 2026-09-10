<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Operator extends Model
{
    protected $fillable = [
        'name',
        'company_id',
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

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function shifts()
    {
        return $this->hasMany(Shift::class);
    }

    public function openShift()
    {
        return $this->hasOne(Shift::class)
            ->where('status', 'open');
    }
}