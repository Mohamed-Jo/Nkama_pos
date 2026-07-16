<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgtSeries extends Model
{
    protected $fillable = [
        'document_series_id',
        'environment',
        'document_type_code',
        'series_year',
        'series_code',
        'start_number',
        'current_number',
        'status',
        'request_id',
        'request_payload',
        'response_payload',
        'requested_at',
        'accepted_at',
        'rejected_at',
        'last_error',
    ];

    protected $casts = [
        'series_year' => 'integer',
        'start_number' => 'integer',
        'current_number' => 'integer',
        'request_payload' => 'array',
        'response_payload' => 'array',
        'requested_at' => 'datetime',
        'accepted_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function documentSeries(): BelongsTo
    {
        return $this->belongsTo(DocumentSeries::class, 'document_series_id');
    }
}