<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('restaurant_tables')) {
            return;
        }

        DB::statement("ALTER TABLE restaurant_tables MODIFY status ENUM('free','occupied','reserved','waiting_payment') NOT NULL DEFAULT 'free'");
    }

    public function down(): void
    {
        if (!Schema::hasTable('restaurant_tables')) {
            return;
        }

        DB::table('restaurant_tables')
            ->whereIn('status', ['reserved', 'waiting_payment'])
            ->update(['status' => 'free']);

        DB::statement("ALTER TABLE restaurant_tables MODIFY status ENUM('free','occupied') NOT NULL DEFAULT 'free'");
    }
};
