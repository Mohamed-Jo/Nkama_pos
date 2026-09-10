<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;

use Illuminate\Database\Eloquent\Model;

class CommercialPromotion extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'name',
        'product_id',
        'category_id',
        'discount_percent',
        'fixed_price',
        'starts_at',
        'ends_at',
        'active',
    ];

    protected $casts = [
        'discount_percent' => 'decimal:2',
        'fixed_price' => 'decimal:2',
        'starts_at' => 'date',
        'ends_at' => 'date',
        'active' => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}