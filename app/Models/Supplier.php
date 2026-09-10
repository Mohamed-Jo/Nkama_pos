<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use BelongsToCompany;

   protected $fillable = [
    'company_name',
    'contact_person',
    'phone',
    'email',
    'address',
    'status',
];

    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }
}
