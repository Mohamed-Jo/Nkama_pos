<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            if (!Schema::hasColumn('shifts', 'cash_sales_total')) {
                $table->decimal('cash_sales_total', 12, 2)->default(0);
            }

            if (!Schema::hasColumn('shifts', 'card_sales_total')) {
                $table->decimal('card_sales_total', 12, 2)
                    ->default(0)
                    ->after('cash_sales_total');
            }

            if (!Schema::hasColumn('shifts', 'transf_sales_total')) {
                $table->decimal('transf_sales_total', 12, 2)
                    ->default(0)
                    ->after('cash_sales_total');
            }

            if (!Schema::hasColumn('shifts', 'multi_sales_total')) {
                $table->decimal('multi_sales_total', 12, 2)
                    ->default(0)
                    ->after('card_sales_total');
            }
        });
    }

    public function down(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            if (Schema::hasColumn('shifts', 'transf_sales_total')) {
                $table->dropColumn('transf_sales_total');
            }
        });
    }
};
