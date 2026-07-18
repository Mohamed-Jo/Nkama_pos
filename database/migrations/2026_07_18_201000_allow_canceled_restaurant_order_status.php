<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement("ALTER TABLE restaurant_orders MODIFY status ENUM('open','kitchen','served','closed','cancelled','canceled','transferred') NOT NULL DEFAULT 'open'");
    }

    public function down(): void
    {
        DB::table('restaurant_orders')
            ->whereIn('status', ['canceled', 'transferred'])
            ->update(['status' => 'cancelled']);

        DB::statement("ALTER TABLE restaurant_orders MODIFY status ENUM('open','kitchen','served','closed','cancelled') NOT NULL DEFAULT 'open'");
    }
};
