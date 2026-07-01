<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            if (!Schema::hasColumn('purchases', 'payment_type')) {
                $table->string('payment_type', 24)->default('direct');
            }

            if (!Schema::hasColumn('purchases', 'payment_status')) {
                $table->string('payment_status', 24)->default('paid');
            }

            if (!Schema::hasColumn('purchases', 'current_account_entry_id')) {
                $table->unsignedBigInteger('current_account_entry_id')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            foreach (['payment_type', 'payment_status', 'current_account_entry_id'] as $column) {
                if (Schema::hasColumn('purchases', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
