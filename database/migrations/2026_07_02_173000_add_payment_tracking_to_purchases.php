<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            if (!Schema::hasColumn('purchases', 'due_date')) {
                $table->date('due_date')->nullable()->after('purchase_date');
            }

            if (!Schema::hasColumn('purchases', 'paid_amount')) {
                $table->decimal('paid_amount', 12, 2)->default(0)->after('total');
            }
        });

        DB::table('purchases')
            ->where('payment_type', 'direct')
            ->update([
                'paid_amount' => DB::raw('total'),
                'payment_status' => 'paid',
            ]);

        DB::table('purchases')
            ->where('payment_type', 'credit')
            ->where('paid_amount', 0)
            ->update(['payment_status' => 'unpaid']);
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            foreach (['due_date', 'paid_amount'] as $column) {
                if (Schema::hasColumn('purchases', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
