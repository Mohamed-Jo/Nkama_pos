<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('restaurant_tables', function (Blueprint $table) {
            if (!Schema::hasColumn('restaurant_tables', 'current_order_id')) {
                $table->foreignId('current_order_id')
                    ->nullable()
                    ->after('status')
                    ->constrained('restaurant_orders')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('restaurant_tables', function (Blueprint $table) {
            if (Schema::hasColumn('restaurant_tables', 'current_order_id')) {
                $table->dropForeign(['current_order_id']);
                $table->dropColumn('current_order_id');
            }
        });
    }
};
