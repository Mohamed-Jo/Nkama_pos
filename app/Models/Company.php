<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $fillable = [
        'name',
        'location',
        'nif',
        'iban',
        'account_number',
        'bank_name',
        'swift',
        'logo_path',
        'login_background_path',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }

    public function operators()
    {
        return $this->hasMany(Operator::class);
    }

    public function profile(): array
    {
        return [
            'name' => $this->name ?? '',
            'location' => $this->location ?? '',
            'nif' => $this->nif ?? '',
            'iban' => $this->iban ?? '',
            'account_number' => $this->account_number ?? '',
            'bank_name' => $this->bank_name ?? '',
            'swift' => $this->swift ?? '',
            'logo_path' => $this->logo_path ?? '',
            'login_background_path' => $this->login_background_path ?? '',
        ];
    }
}
