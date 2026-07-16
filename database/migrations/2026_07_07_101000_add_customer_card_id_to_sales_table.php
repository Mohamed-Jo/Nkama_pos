<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            if (!Schema::hasColumn('sales', 'customer_card_id')) {
                $table->foreignId('customer_card_id')->nullable()->after('customer_id')->constrained('customer_cards')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            if (Schema::hasColumn('sales', 'customer_card_id')) {
                $table->dropForeign(['customer_card_id']);
                $table->dropColumn('customer_card_id');
            }
        });
    }
};