<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('operators', function (Blueprint $table) {
            if (!Schema::hasColumn('operators', 'role')) {
                $table->string('role')->default('cashier')->after('password');
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
        });
    }
};
