<?php

namespace App\Models\Concerns;

use App\Models\Company;
use App\Services\CurrentCompany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

trait BelongsToCompany
{
    private static array $companyColumnCache = [];

    protected static function bootBelongsToCompany(): void
    {
        static::addGlobalScope('company', function (Builder $builder) {
            if (! static::hasCompanyColumn()) {
                return;
            }

            $companyId = CurrentCompany::id();

            if ($companyId) {
                $builder->where($builder->getModel()->getTable() . '.company_id', $companyId);
            }
        });

        static::creating(function ($model) {
            if (! static::hasCompanyColumn() || $model->company_id) {
                return;
            }

            if ($companyId = CurrentCompany::id()) {
                $model->company_id = $companyId;
            }
        });
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    protected static function hasCompanyColumn(): bool
    {
        $table = (new static())->getTable();

        if (array_key_exists($table, self::$companyColumnCache)) {
            return self::$companyColumnCache[$table];
        }

        try {
            return self::$companyColumnCache[$table] = Schema::hasColumn($table, 'company_id');
        } catch (\Throwable) {
            return self::$companyColumnCache[$table] = false;
        }
    }
}
