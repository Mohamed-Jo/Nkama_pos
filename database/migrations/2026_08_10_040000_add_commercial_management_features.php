<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (!Schema::hasColumn('customers', 'discount_percent')) {
                $table->decimal('discount_percent', 5, 2)->default(0)->after('address');
            }
            if (!Schema::hasColumn('customers', 'price_table')) {
                $table->string('price_table', 40)->nullable()->after('discount_percent');
            }
        });

        if (!Schema::hasTable('commercial_price_rules')) {
            Schema::create('commercial_price_rules', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('price_table', 40)->nullable();
                $table->foreignId('customer_id')->nullable()->constrained('customers')->cascadeOnDelete();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->decimal('unit_price', 14, 2);
                $table->date('starts_at')->nullable();
                $table->date('ends_at')->nullable();
                $table->boolean('active')->default(true);
                $table->timestamps();
                $table->index(['customer_id', 'product_id'], 'cpr_customer_product_idx');
                $table->index(['price_table', 'product_id'], 'cpr_table_product_idx');
            });
        }

        if (!Schema::hasTable('commercial_promotions')) {
            Schema::create('commercial_promotions', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->foreignId('product_id')->nullable()->constrained('products')->cascadeOnDelete();
                $table->foreignId('category_id')->nullable()->constrained('categories')->cascadeOnDelete();
                $table->decimal('discount_percent', 5, 2)->default(0);
                $table->decimal('fixed_price', 14, 2)->nullable();
                $table->date('starts_at')->nullable();
                $table->date('ends_at')->nullable();
                $table->boolean('active')->default(true);
                $table->timestamps();
                $table->index(['product_id', 'active'], 'cp_product_active_idx');
                $table->index(['category_id', 'active'], 'cp_category_active_idx');
            });
        }

        Schema::table('sales', function (Blueprint $table) {
            if (!Schema::hasColumn('sales', 'is_proforma')) {
                $table->boolean('is_proforma')->default(false)->after('status');
            }
            if (!Schema::hasColumn('sales', 'converted_sale_id')) {
                $table->foreignId('converted_sale_id')->nullable()->after('is_proforma')->constrained('sales')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            foreach (['converted_sale_id', 'is_proforma'] as $column) {
                if (Schema::hasColumn('sales', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::dropIfExists('commercial_promotions');
        Schema::dropIfExists('commercial_price_rules');

        Schema::table('customers', function (Blueprint $table) {
            foreach (['price_table', 'discount_percent'] as $column) {
                if (Schema::hasColumn('customers', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};