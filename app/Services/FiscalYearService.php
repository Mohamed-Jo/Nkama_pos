<?php

namespace App\Services;

use App\Models\FiscalYear;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FiscalYearService
{
    public static function active(): ?FiscalYear
    {
        if (! self::available()) {
            return null;
        }

        return FiscalYear::where('is_active', true)->first()
            ?: FiscalYear::where('status', FiscalYear::STATUS_OPEN)->orderByDesc('year')->first();
    }

    public static function forDate(string|Carbon $date): ?FiscalYear
    {
        if (! self::available()) {
            return null;
        }

        $day = Carbon::parse($date)->toDateString();

        return FiscalYear::whereDate('start_date', '<=', $day)
            ->whereDate('end_date', '>=', $day)
            ->orderByDesc('is_active')
            ->orderByDesc('year')
            ->first();
    }

    public static function assertDateIsOpen(string|Carbon $date, ?string $message = null): void
    {
        if (! self::available()) {
            return;
        }

        $day = Carbon::parse($date)->toDateString();
        $year = self::forDate($day);

        if (! $year) {
            throw new \RuntimeException($message ?: 'Nao existe exercicio fiscal para a data ' . Carbon::parse($day)->format('d/m/Y') . '.');
        }

        if ($year->status !== FiscalYear::STATUS_OPEN) {
            throw new \RuntimeException($message ?: 'O exercicio fiscal de ' . $year->year . ' esta fechado. Nao e permitido lancar nesta data.');
        }
    }

    public static function open(FiscalYear $fiscalYear, ?int $operatorId = null): FiscalYear
    {
        if ($fiscalYear->status === FiscalYear::STATUS_CLOSED) {
            throw new \RuntimeException('Nao e possivel abrir como ativo um exercicio ja fechado.');
        }

        return DB::transaction(function () use ($fiscalYear, $operatorId) {
            FiscalYear::whereKeyNot($fiscalYear->id)->update(['is_active' => false]);

            $fiscalYear->forceFill([
                'status' => FiscalYear::STATUS_OPEN,
                'is_active' => true,
                'opened_at' => $fiscalYear->opened_at ?: now(),
                'opened_by' => $fiscalYear->opened_by ?: $operatorId,
            ])->save();

            return $fiscalYear->refresh();
        });
    }

    public static function close(FiscalYear $fiscalYear, ?int $operatorId = null): FiscalYear
    {
        return DB::transaction(function () use ($fiscalYear, $operatorId) {
            $fiscalYear->forceFill([
                'status' => FiscalYear::STATUS_CLOSED,
                'is_active' => false,
                'closed_at' => now(),
                'closed_by' => $operatorId,
            ])->save();

            return $fiscalYear->refresh();
        });
    }

    private static function available(): bool
    {
        return Schema::hasTable('fiscal_years');
    }
}