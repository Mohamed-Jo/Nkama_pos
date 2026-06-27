<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
}
