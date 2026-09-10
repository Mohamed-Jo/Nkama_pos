<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;

use Illuminate\Database\Eloquent\Model;

class AccountingAccount extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'code',
        'name',
        'type',
        'parent_id',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function journalLines()
    {
        return $this->hasMany(AccountingJournalLine::class);
    }
}