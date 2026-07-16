<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('agt_series')) {
            return;
        }

        if (Schema::hasColumn('document_series', 'agt_status')) {
            DB::table('document_series')
                ->join('document_types', 'document_types.id', '=', 'document_series.document_type_id')
                ->select([
                    'document_series.id',
                    'document_series.year',
                    'document_series.code',
                    'document_series.start_number',
                    'document_series.current_number',
                    'document_series.agt_status',
                    'document_series.agt_response',
                    'document_series.agt_requested_at',
                    'document_types.code as document_type_code',
                ])
                ->whereNotNull('document_series.agt_status')
                ->orderBy('document_series.id')
                ->each(function ($series) {
                    DB::table('agt_series')->updateOrInsert(
                        [
                            'environment' => (string) config('agt.environment', 'hml'),
                            'document_type_code' => (string) $series->document_type_code,
                            'series_year' => (int) $series->year,
                            'series_code' => (string) $series->code,
                        ],
                        [
                            'document_series_id' => $series->id,
                            'start_number' => $series->start_number,
                            'current_number' => $series->current_number,
                            'status' => $series->agt_status,
                            'response_payload' => $series->agt_response,
                            'requested_at' => $series->agt_requested_at,
                            'accepted_at' => $series->agt_status === 'accepted' ? $series->agt_requested_at : null,
                            'rejected_at' => $series->agt_status === 'rejected' ? $series->agt_requested_at : null,
                            'last_error' => $series->agt_status === 'rejected' ? $this->message($series->agt_response) : null,
                            'updated_at' => now(),
                            'created_at' => now(),
                        ]
                    );
                });
        }

        Schema::table('document_series', function (Blueprint $table) {
            foreach (['agt_requested_at', 'agt_response', 'agt_status'] as $column) {
                if (Schema::hasColumn('document_series', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('document_series', function (Blueprint $table) {
            if (! Schema::hasColumn('document_series', 'agt_status')) {
                $table->string('agt_status', 20)->nullable()->after('active');
            }

            if (! Schema::hasColumn('document_series', 'agt_response')) {
                $table->json('agt_response')->nullable()->after('agt_status');
            }

            if (! Schema::hasColumn('document_series', 'agt_requested_at')) {
                $table->timestamp('agt_requested_at')->nullable()->after('agt_response');
            }
        });
    }

    private function message(?string $json): ?string
    {
        if (! $json) {
            return null;
        }

        $response = json_decode($json, true);

        if (! is_array($response)) {
            return mb_substr($json, 0, 1000);
        }

        $errors = $response['errorList'] ?? $response['errors'] ?? null;

        if (is_array($errors)) {
            return collect($errors)
                ->map(fn ($error) => is_array($error) ? json_encode($error, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : (string) $error)
                ->filter()
                ->take(3)
                ->implode(' | ') ?: null;
        }

        return (string) ($errors ?: ($response['message'] ?? null));
    }
};