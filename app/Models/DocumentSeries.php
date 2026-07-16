<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DocumentSeries extends Model
{
    protected $fillable = [
        'document_type_id',
        'year',
        'code',
        'start_number',
        'current_number',
        'active',
    ];

    protected $casts = [
        'year' => 'integer',
        'start_number' => 'integer',
        'current_number' => 'integer',
        'active' => 'boolean',
    ];

    public function type(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class, 'document_type_id');
    }

    public function agtSeries(): HasOne
    {
        return $this->hasOne(AgtSeries::class, 'document_series_id');
    }
}