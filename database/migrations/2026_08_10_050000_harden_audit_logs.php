<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('audit_logs', 'severity')) {
                $table->string('severity', 20)->default('info')->after('action');
            }
            if (!Schema::hasColumn('audit_logs', 'event_hash')) {
                $table->string('event_hash', 64)->nullable()->after('data')->index('audit_event_hash_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            if (Schema::hasColumn('audit_logs', 'event_hash')) {
                $table->dropColumn('event_hash');
            }
            if (Schema::hasColumn('audit_logs', 'severity')) {
                $table->dropColumn('severity');
            }
        });
    }
};