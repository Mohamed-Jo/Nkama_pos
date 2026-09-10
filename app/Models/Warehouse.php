<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;

use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'name',
        'code',
        'location',
        'is_default',
        'active',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'active' => 'boolean',
    ];

    public function productStocks()
    {
        return $this->hasMany(ProductWarehouseStock::class);
    }
}
