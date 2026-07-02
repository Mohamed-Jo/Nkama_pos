<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_ORDERED = 'ordered';
    public const STATUS_PARTIAL = 'partial';
    public const STATUS_RECEIVED = 'received';
    public const APPROVAL_PENDING = 'pending';
    public const APPROVAL_APPROVED = 'approved';
    public const APPROVAL_REJECTED = 'rejected';

    protected $fillable = [
        'supplier_id',
        'operator_id',
        'approved_by',
        'rejected_by',
        'document_number',
        'purchase_date',
        'due_date',
        'status',
        'approval_status',
        'approved_at',
        'rejected_at',
        'rejection_reason',
        'subtotal',
        'tax',
        'total',
        'paid_amount',
        'payment_type',
        'payment_status',
        'current_account_entry_id',
        'notes',
        'received_at',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'due_date' => 'date',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'received_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
        'paid_amount' => 'decimal:2',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function operator()
    {
        return $this->belongsTo(Operator::class);
    }

    public function approver()
    {
        return $this->belongsTo(Operator::class, 'approved_by');
    }

    public function rejecter()
    {
        return $this->belongsTo(Operator::class, 'rejected_by');
    }

    public function items()
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function currentAccountEntry()
    {
        return $this->belongsTo(CurrentAccountEntry::class);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_ORDERED => 'Pedido enviado',
            self::STATUS_PARTIAL => 'Parcial',
            self::STATUS_RECEIVED => 'Recebida',
            default => 'Por enviar',
        };
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_ORDERED => 'badge-ordered',
            self::STATUS_PARTIAL => 'badge-partial',
            self::STATUS_RECEIVED => 'badge-received',
            default => 'badge-draft',
        };
    }

    public function isClosedForReceiving(): bool
    {
        return $this->status === self::STATUS_RECEIVED;
    }

    public function approvalLabel(): string
    {
        return match ($this->approval_status) {
            self::APPROVAL_APPROVED => 'Aprovada',
            self::APPROVAL_REJECTED => 'Rejeitada',
            default => 'Pendente',
        };
    }

    public function approvalBadgeClass(): string
    {
        return match ($this->approval_status) {
            self::APPROVAL_APPROVED => 'badge-received',
            self::APPROVAL_REJECTED => 'badge-rejected',
            default => 'badge-draft',
        };
    }

    public function isApproved(): bool
    {
        return $this->approval_status === self::APPROVAL_APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->approval_status === self::APPROVAL_REJECTED;
    }

    public function getBalanceAttribute(): float
    {
        return round(max((float) $this->total - (float) $this->paid_amount, 0), 2);
    }

    public function paymentStatusLabel(): string
    {
        return match ($this->payment_status) {
            'paid' => 'Pago',
            'partial' => 'Parcial',
            default => 'Em aberto',
        };
    }

    public function paymentBadgeClass(): string
    {
        return match ($this->payment_status) {
            'paid' => 'badge-received',
            'partial' => 'badge-partial',
            default => 'badge-draft',
        };
    }

    public function isOverdue(): bool
    {
        return $this->payment_status !== 'paid'
            && $this->payment_type === 'credit'
            && $this->isApproved()
            && $this->due_date
            && $this->due_date->isPast()
            && ! $this->due_date->isToday();
    }
}
