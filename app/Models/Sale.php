<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    protected $fillable = [
        'operator_id',
        'user_id',
        'customer_id',
        'customer_card_id',
        'shift_id',

        'invoice_number',
        'document_type_code',
        'document_series_id',
        'document_number',

        'subtotal',
        'tax',
        'tax_rate',
        'discount',

        'total',
        'paid',
        'change',
        'payment_method',
        'payment_status',
        'status',
        'currency',
        'exchange_rate',
        'exemption_reason',
        'commercial_discount',
        'payment_condition',
        'due_date'
    ];

    protected $casts = [
        'total' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'paid' => 'decimal:2',
        'change' => 'decimal:2',
        'exchange_rate' => 'decimal:6',
        'commercial_discount' => 'decimal:2',
        'due_date' => 'date',
    ];


    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function payments()
    {
        return $this->hasMany(Payments::class, 'sale_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function customerCard()
    {
        return $this->belongsTo(CustomerCard::class);
    }

    public function pointTransactions()
    {
        return $this->hasMany(PointTransaction::class);
    }

    public function operator()
    {
        return $this->belongsTo(Operator::class);
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    public function documentSeries()
    {
        return $this->belongsTo(DocumentSeries::class, 'document_series_id');
    }

    public function creditNotes()
    {
        return $this->hasMany(CreditNote::class, 'original_sale_id');
    }
    public function agtDocument()
    {
        return $this->morphOne(AgtDocument::class, 'document', 'document_model', 'document_id');
    }
}
