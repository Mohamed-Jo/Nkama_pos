<?php

namespace App\Observers;

use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Model;

class AuditableObserver
{
    public function created(Model $model): void
    {
        AuditLogger::created($model);
    }

    public function updated(Model $model): void
    {
        AuditLogger::updated($model);
    }

    public function deleted(Model $model): void
    {
        AuditLogger::deleted($model);
    }
}
