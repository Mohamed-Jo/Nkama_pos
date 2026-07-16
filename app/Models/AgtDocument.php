<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AgtDocument extends Model
{
    protected $fillable = [
        'document_model',
        'document_id',
        'document_type_code',
        'invoice_number',
        'status',
        'payload',
        'payload_hash',
        'external_id',
        'last_response',
        'last_error',
        'attempts',
        'submitted_at',
        'accepted_at',
        'rejected_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'last_response' => 'array',
        'attempts' => 'integer',
        'submitted_at' => 'datetime',
        'accepted_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function document()
    {
        return $this->morphTo(__FUNCTION__, 'document_model', 'document_id');
    }

    public function getStatusLabelAttribute(): string
    {
        return [
            'ready' => 'Preparada',
            'pending' => 'Pendente',
            'submitted' => 'Validada',
            'failed' => 'Rejeitada',
        ][$this->status] ?? (string) $this->status;
    }

    public function getValidationMessageAttribute(): string
    {
        return match ($this->status) {
            'submitted' => 'Factura validada pela AGT.',
            'pending' => 'Factura enviada para a AGT e ainda pendente de validacao.',
            'failed' => 'Factura rejeitada pela AGT: ' . ($this->last_error ?: 'erro nao especificado.'),
            default => 'Factura preparada, ainda nao enviada para validacao AGT.',
        };
    }
}