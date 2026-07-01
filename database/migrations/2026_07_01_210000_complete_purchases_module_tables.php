<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            if (!Schema::hasColumn('purchases', 'supplier_id')) {
                $table->unsignedBigInteger('supplier_id')->nullable();
            }

            if (!Schema::hasColumn('purchases', 'operator_id')) {
                $table->unsignedBigInteger('operator_id')->nullable();
            }

            if (!Schema::hasColumn('purchases', 'document_number')) {
                $table->string('document_number')->nullable();
            }

            if (!Schema::hasColumn('purchases', 'purchase_date')) {
                $table->date('purchase_date')->nullable();
            }

            if (!Schema::hasColumn('purchases', 'status')) {
                $table->string('status', 24)->default('draft');
            }

            if (!Schema::hasColumn('purchases', 'subtotal')) {
                $table->decimal('subtotal', 12, 2)->default(0);
            }

            if (!Schema::hasColumn('purchases', 'tax')) {
                $table->decimal('tax', 12, 2)->default(0);
            }

            if (!Schema::hasColumn('purchases', 'total')) {
                $table->decimal('total', 12, 2)->default(0);
            }

            if (!Schema::hasColumn('purchases', 'notes')) {
                $table->text('notes')->nullable();
            }

            if (!Schema::hasColumn('purchases', 'received_at')) {
                $table->timestamp('received_at')->nullable();
            }
        });

        if (!Schema::hasTable('purchase_items')) {
            Schema::create('purchase_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('purchase_id');
                $table->unsignedBigInteger('product_id')->nullable();
                $table->unsignedInteger('quantity');
                $table->decimal('unit_cost', 12, 2)->default(0);
                $table->decimal('tax_rate', 5, 2)->default(0);
                $table->decimal('subtotal', 12, 2)->default(0);
                $table->decimal('tax', 12, 2)->default(0);
                $table->decimal('total', 12, 2)->default(0);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_items');

        Schema::table('purchases', function (Blueprint $table) {
            foreach (['supplier_id', 'operator_id', 'document_number', 'purchase_date', 'status', 'subtotal', 'tax', 'total', 'notes', 'received_at'] as $column) {
                if (Schema::hasColumn('purchases', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
