<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;

use Illuminate\Database\Eloquent\Model;

class AccountingJournalEntry extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'entry_date',
        'document_number',
        'source_type',
        'source_id',
        'description',
        'status',
        'posted_by',
    ];

    protected $casts = [
        'entry_date' => 'date',
    ];

    public function lines()
    {
        return $this->hasMany(AccountingJournalLine::class, 'journal_entry_id');
    }

    public function poster()
    {
        return $this->belongsTo(Operator::class, 'posted_by');
    }
}