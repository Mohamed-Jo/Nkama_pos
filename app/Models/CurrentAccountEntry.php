<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CurrentAccountEntry extends Model
{
    protected $fillable = [
        'entity_type',
        'entity_id',
        'entry_date',
        'movement_type',
        'debit',
        'credit',
        'document_type',
        'document_id',
        'description',
        'operator_id',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
    ];

    public function operator(): BelongsTo
    {
        return $this->belongsTo(Operator::class);
    }

    public function getEntityNameAttribute(): string
    {
        if ($this->entity_type === 'customer') {
            return Customer::find($this->entity_id)?->name ?? 'Cliente removido';
        }

        if ($this->entity_type === 'supplier') {
            return Supplier::find($this->entity_id)?->company_name ?? 'Fornecedor removido';
        }

        return 'Entidade desconhecida';
    }

    public function getSignedAmountAttribute(): float
    {
        return (float) $this->debit - (float) $this->credit;
    }
}
