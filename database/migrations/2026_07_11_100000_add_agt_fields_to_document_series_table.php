<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
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

    public function down(): void
    {
        Schema::table('document_series', function (Blueprint $table) {
            foreach (['agt_requested_at', 'agt_response', 'agt_status'] as $column) {
                if (Schema::hasColumn('document_series', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
