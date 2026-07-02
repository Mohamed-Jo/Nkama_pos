<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_items', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_items', 'received_quantity')) {
                $table->unsignedInteger('received_quantity')->default(0)->after('quantity');
            }
        });

        if (Schema::hasTable('purchases')) {
            DB::table('purchase_items')
                ->whereIn('purchase_id', DB::table('purchases')->select('id')->where('status', 'received'))
                ->update(['received_quantity' => DB::raw('quantity')]);
        }
    }

    public function down(): void
    {
        Schema::table('purchase_items', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_items', 'received_quantity')) {
                $table->dropColumn('received_quantity');
            }
        });
    }
};
