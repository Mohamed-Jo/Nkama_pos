<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('restaurant_orders') || DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE restaurant_orders MODIFY status ENUM('open','kitchen','served','closed','cancelled','canceled','transferred') NOT NULL DEFAULT 'open'");
    }

    public function down(): void
    {
        if (!Schema::hasTable('restaurant_orders')) {
            return;
        }

        DB::table('restaurant_orders')
            ->whereIn('status', ['canceled', 'transferred'])
            ->update(['status' => 'cancelled']);

        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE restaurant_orders MODIFY status ENUM('open','kitchen','served','closed','cancelled') NOT NULL DEFAULT 'open'");
    }
};
