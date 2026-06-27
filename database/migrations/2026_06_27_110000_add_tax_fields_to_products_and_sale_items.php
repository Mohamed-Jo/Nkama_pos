<?php

use App\Services\BusinessSettings;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'tax_rate')) {
                $table->decimal('tax_rate', 5, 2)->default(0)->after('selling_price');
            }
        });

        Schema::table('sale_items', function (Blueprint $table) {
            if (!Schema::hasColumn('sale_items', 'net_subtotal')) {
                $table->decimal('net_subtotal', 10, 2)->default(0)->after('subtotal');
            }

            if (!Schema::hasColumn('sale_items', 'tax_rate')) {
                $table->decimal('tax_rate', 5, 2)->default(0)->after('net_subtotal');
            }

            if (!Schema::hasColumn('sale_items', 'tax_amount')) {
                $table->decimal('tax_amount', 10, 2)->default(0)->after('tax_rate');
            }
        });

        if (Schema::hasTable('products') && Schema::hasTable('app_settings')) {
            $tax = BusinessSettings::tax();
            $defaultRate = (bool) ($tax['active'] ?? false) ? (float) ($tax['value'] ?? 0) : 0;

            DB::table('products')
                ->where('tax_rate', 0)
                ->update(['tax_rate' => $defaultRate]);
        }
    }

    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            foreach (['tax_amount', 'tax_rate', 'net_subtotal'] as $column) {
                if (Schema::hasColumn('sale_items', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'tax_rate')) {
                $table->dropColumn('tax_rate');
            }
        });
    }
};
