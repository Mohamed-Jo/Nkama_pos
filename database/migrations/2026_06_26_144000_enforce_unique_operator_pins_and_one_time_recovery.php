<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('operators', function (Blueprint $table) {
            if (!Schema::hasColumn('operators', 'pin_fingerprint')) {
                $table->string('pin_fingerprint', 64)->nullable()->unique()->after('pin');
            }

            if (!Schema::hasColumn('operators', 'recovery_code_used_at')) {
                $table->timestamp('recovery_code_used_at')->nullable()->after('recovery_code');
            }
        });
    }

    public function down(): void
    {
        Schema::table('operators', function (Blueprint $table) {
            if (Schema::hasColumn('operators', 'recovery_code_used_at')) {
                $table->dropColumn('recovery_code_used_at');
            }

            if (Schema::hasColumn('operators', 'pin_fingerprint')) {
                $table->dropUnique(['pin_fingerprint']);
                $table->dropColumn('pin_fingerprint');
            }
        });
    }
};
