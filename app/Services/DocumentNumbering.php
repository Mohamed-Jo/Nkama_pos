<?php

namespace App\Services;

use App\Models\DocumentSeries;
use App\Models\DocumentType;
use Illuminate\Support\Facades\DB;

class DocumentNumbering
{
    public static function next(string $documentTypeCode, ?int $year = null): array
    {
        $code = strtoupper(trim($documentTypeCode));
        $year ??= (int) now()->year;

        return DB::transaction(function () use ($code, $year) {
            $type = DocumentType::where('code', $code)->where('active', true)->lockForUpdate()->first();

            if (! $type) {
                throw new \RuntimeException("Tipo de documento {$code} nao esta configurado.");
            }

            $series = DocumentSeries::where('document_type_id', $type->id)
                ->where('year', $year)
                ->where('active', true)
                ->lockForUpdate()
                ->orderByDesc('id')
                ->first();

            if (! $series) {
                $series = DocumentSeries::create([
                    'document_type_id' => $type->id,
                    'year' => $year,
                    'code' => (string) $year,
                    'start_number' => 1,
                    'current_number' => 0,
                    'active' => true,
                ]);

                $series = DocumentSeries::whereKey($series->id)->lockForUpdate()->first();
            }

            $nextNumber = max((int) $series->current_number + 1, (int) $series->start_number);
            $series->update(['current_number' => $nextNumber]);

            return [
                'document_type_code' => $type->code,
                'document_series_id' => $series->id,
                'document_number' => $nextNumber,
                'invoice_number' => sprintf('%s %s/%d', $type->code, $series->code, $nextNumber),
            ];
        });
    }
}
