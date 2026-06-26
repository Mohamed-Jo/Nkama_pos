<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('operators', function (Blueprint $table) {
            if (Schema::hasColumn('operators', 'pin')) {
                $table->string('pin', 255)->change();
            }

            if (!Schema::hasColumn('operators', 'email')) {
                $table->string('email')->nullable()->unique()->after('name');
            }

            if (!Schema::hasColumn('operators', 'password')) {
                $table->string('password')->nullable()->after('pin');
            }

            if (!Schema::hasColumn('operators', 'pin_fingerprint')) {
                $table->string('pin_fingerprint', 64)->nullable()->unique()->after('pin');
            }

            if (!Schema::hasColumn('operators', 'recovery_code')) {
                $table->string('recovery_code')->nullable()->after('password');
            }

            if (!Schema::hasColumn('operators', 'recovery_code_used_at')) {
                $table->timestamp('recovery_code_used_at')->nullable()->after('recovery_code');
            }

            if (!Schema::hasColumn('operators', 'role')) {
                $table->string('role')->default('cashier')->after('recovery_code');
            }

            if (!Schema::hasColumn('operators', 'active')) {
                $table->boolean('active')->default(true)->after('role');
            }
        });
    }

    public function down(): void
    {
        Schema::table('operators', function (Blueprint $table) {
            if (Schema::hasColumn('operators', 'active')) {
                $table->dropColumn('active');
            }

            if (Schema::hasColumn('operators', 'role')) {
                $table->dropColumn('role');
            }

            if (Schema::hasColumn('operators', 'recovery_code')) {
                $table->dropColumn('recovery_code');
            }

            if (Schema::hasColumn('operators', 'recovery_code_used_at')) {
                $table->dropColumn('recovery_code_used_at');
            }

            if (Schema::hasColumn('operators', 'pin_fingerprint')) {
                $table->dropUnique(['pin_fingerprint']);
                $table->dropColumn('pin_fingerprint');
            }

            if (Schema::hasColumn('operators', 'password')) {
                $table->dropColumn('password');
            }

            if (Schema::hasColumn('operators', 'email')) {
                $table->dropUnique(['email']);
                $table->dropColumn('email');
            }
        });
    }
};
