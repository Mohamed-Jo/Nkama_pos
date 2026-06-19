<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
         /*    $table->decimal('expected_cash', 12, 2)
                ->default(0)
                ->after('opening_cash'); */
/* 
            $table->decimal('expected_cash', 12, 2)
                ->default(0)
                ->after('closing_cash'); */

           /*  $table->decimal('difference', 12, 2)
                ->default(0)
                ->after('expected_cash'); */

            $table->decimal('cash_sales_total', 12, 2)
                ->default(0);
/*                 ->after('difference');
 */
            $table->decimal('card_sales_total', 12, 2)
                ->default(0)
                ->after('cash_sales_total');

            $table->decimal('transf_sales_total', 12, 2)
                ->default(0)
                ->after('cash_sales_total');

            $table->decimal('multi_sales_total', 12, 2)
                ->default(0)
                ->after('card_sales_total') ;
        });
    }

    public function down(): void
    {
        Schema::table('shifts', function (Blueprint $table) {

            $table->dropColumn([
                // 'expected_cash',
                'difference',
                'cash_sales_total',
                'card_sales_total',
                'multi_sales_total',
                'transf_sales_total'
            ]);

        });
    }
};