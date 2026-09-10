<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;

use Illuminate\Database\Eloquent\Model;

class PurchaseAttachment extends Model
{
    use BelongsToCompany;

    protected $fillable = ['purchase_id', 'label', 'original_name', 'path', 'mime_type', 'size', 'operator_id'];

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    public function operator()
    {
        return $this->belongsTo(Operator::class);
    }
}
