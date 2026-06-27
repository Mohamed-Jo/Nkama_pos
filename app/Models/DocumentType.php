<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentType extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description',
        'affects_current_account',
        'is_credit_note',
        'active',
    ];

    protected $casts = [
        'affects_current_account' => 'boolean',
        'is_credit_note' => 'boolean',
        'active' => 'boolean',
    ];

    public function series(): HasMany
    {
        return $this->hasMany(DocumentSeries::class);
    }
}
