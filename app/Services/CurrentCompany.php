<?php

namespace App\Services;

use App\Models\Company;
use Illuminate\Support\Facades\Schema;

class CurrentCompany
{
    private static ?bool $hasCompaniesTable = null;

    public static function id(): ?int
    {
        $sessionId = session('company_id');

        if ($sessionId) {
            return (int) $sessionId;
        }

        return self::defaultId();
    }

    public static function get(): ?Company
    {
        $id = self::id();

        if (! $id || ! self::hasCompaniesTable()) {
            return null;
        }

        return Company::whereKey($id)->where('active', true)->first();
    }

    public static function defaultId(): ?int
    {
        if (! self::hasCompaniesTable()) {
            return null;
        }

        return Company::where('active', true)->orderBy('id')->value('id');
    }

    public static function hasCompaniesTable(): bool
    {
        if (self::$hasCompaniesTable !== null) {
            return self::$hasCompaniesTable;
        }

        try {
            return self::$hasCompaniesTable = Schema::getConnection()->getSchemaBuilder()->hasTable('companies');
        } catch (\Throwable) {
            return self::$hasCompaniesTable = false;
        }
    }
}
