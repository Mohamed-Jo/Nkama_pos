<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class AuditLogger
{
    private const HIDDEN_FIELDS = [
        'password',
        'pin',
        'remember_token',
    ];

    public static function log(string $action, string $model, ?int $modelId = null, array $data = []): void
    {
        try {
            if (!Schema::hasTable('audit_logs')) {
                return;
            }

            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => $action,
                'model' => $model,
                'model_id' => $modelId,
                'data' => array_filter([
                    'operator_id' => session('operator_id'),
                    'ip' => request()?->ip(),
                    'url' => request()?->fullUrl(),
                    'method' => request()?->method(),
                    'data' => self::sanitize($data),
                ], fn ($value) => $value !== null && $value !== []),
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    public static function created(Model $model): void
    {
        self::log('created', class_basename($model), $model->getKey(), [
            'after' => self::sanitize($model->getAttributes()),
        ]);
    }

    public static function updated(Model $model): void
    {
        $changes = Arr::except($model->getChanges(), ['updated_at']);

        if ($changes === []) {
            return;
        }

        self::log('updated', class_basename($model), $model->getKey(), [
            'before' => self::onlyKeys($model->getOriginal(), array_keys($changes)),
            'after' => self::onlyKeys($model->getAttributes(), array_keys($changes)),
        ]);
    }

    public static function deleted(Model $model): void
    {
        self::log('deleted', class_basename($model), $model->getKey(), [
            'before' => self::sanitize($model->getOriginal()),
        ]);
    }

    private static function onlyKeys(array $values, array $keys): array
    {
        return self::sanitize(Arr::only($values, $keys));
    }

    private static function sanitize(array $values): array
    {
        foreach (self::HIDDEN_FIELDS as $field) {
            if (array_key_exists($field, $values)) {
                $values[$field] = '[oculto]';
            }
        }

        return $values;
    }
}
