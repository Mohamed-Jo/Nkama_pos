<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('operators', function (Blueprint $table) {
            if (!Schema::hasColumn('operators', 'recovery_code')) {
                $table->string('recovery_code')->nullable()->after('password');
            }
        });
    }

    public function down(): void
    {
        Schema::table('operators', function (Blueprint $table) {
            if (Schema::hasColumn('operators', 'recovery_code')) {
                $table->dropColumn('recovery_code');
            }
        });
    }
};
