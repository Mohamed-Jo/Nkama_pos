<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FiscalYear extends Model
{
    use BelongsToCompany;

    use HasFactory;

    public const STATUS_OPEN = 'open';
    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'name',
        'year',
        'start_date',
        'end_date',
        'status',
        'is_active',
        'opened_at',
        'opened_by',
        'closed_at',
        'closed_by',
        'notes',
    ];

    protected $casts = [
        'year' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function opener(): BelongsTo
    {
        return $this->belongsTo(Operator::class, 'opened_by');
    }

    public function closer(): BelongsTo
    {
        return $this->belongsTo(Operator::class, 'closed_by');
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->status === self::STATUS_CLOSED ? 'Fechado' : 'Aberto';
    }
}