<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('agt_series')) {
            return;
        }

        Schema::create('agt_series', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_series_id')->nullable()->constrained('document_series')->nullOnDelete();
            $table->string('environment', 10)->default('hml');
            $table->string('document_type_code', 10);
            $table->unsignedSmallInteger('series_year');
            $table->string('series_code', 50)->nullable();
            $table->unsignedInteger('start_number')->nullable();
            $table->unsignedInteger('current_number')->nullable();
            $table->string('status', 30)->nullable();
            $table->string('request_id')->nullable();
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->unique(['environment', 'document_type_code', 'series_year', 'series_code'], 'agt_series_unique_local_series');
            $table->index(['status', 'requested_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agt_series');
    }
};