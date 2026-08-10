<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            if (!Schema::hasColumn('purchases', 'document_type')) {
                $table->string('document_type', 24)->default('purchase')->after('document_number');
            }
            if (!Schema::hasColumn('purchases', 'quotation_reference')) {
                $table->string('quotation_reference', 80)->nullable()->after('document_type');
            }
            if (!Schema::hasColumn('purchases', 'order_number')) {
                $table->string('order_number', 80)->nullable()->after('quotation_reference');
            }
            if (!Schema::hasColumn('purchases', 'supplier_invoice_number')) {
                $table->string('supplier_invoice_number', 80)->nullable()->after('order_number');
            }
            if (!Schema::hasColumn('purchases', 'expenses_total')) {
                $table->decimal('expenses_total', 12, 2)->default(0)->after('tax');
            }
        });

        Schema::table('purchase_items', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_items', 'returned_quantity')) {
                $table->unsignedInteger('returned_quantity')->default(0)->after('received_quantity');
            }
        });

        if (!Schema::hasTable('purchase_expenses')) {
            Schema::create('purchase_expenses', function (Blueprint $table) {
                $table->id();
                $table->foreignId('purchase_id')->constrained()->cascadeOnDelete();
                $table->string('description');
                $table->string('category', 80)->default('Geral');
                $table->decimal('amount', 12, 2)->default(0);
                $table->boolean('affects_cost')->default(true);
                $table->foreignId('operator_id')->nullable()->constrained('operators')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('purchase_attachments')) {
            Schema::create('purchase_attachments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('purchase_id')->constrained()->cascadeOnDelete();
                $table->string('label')->nullable();
                $table->string('original_name');
                $table->string('path');
                $table->string('mime_type', 120)->nullable();
                $table->unsignedBigInteger('size')->default(0);
                $table->foreignId('operator_id')->nullable()->constrained('operators')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('purchase_returns')) {
            Schema::create('purchase_returns', function (Blueprint $table) {
                $table->id();
                $table->foreignId('purchase_id')->constrained()->cascadeOnDelete();
                $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
                $table->foreignId('operator_id')->nullable()->constrained('operators')->nullOnDelete();
                $table->date('return_date');
                $table->string('document_number', 80)->nullable();
                $table->text('reason')->nullable();
                $table->decimal('total', 12, 2)->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('purchase_return_items')) {
            Schema::create('purchase_return_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('purchase_return_id')->constrained('purchase_returns')->cascadeOnDelete();
                $table->foreignId('purchase_item_id')->nullable()->constrained('purchase_items')->nullOnDelete();
                $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
                $table->unsignedInteger('quantity');
                $table->decimal('unit_cost', 12, 2)->default(0);
                $table->decimal('tax_rate', 5, 2)->default(0);
                $table->decimal('total', 12, 2)->default(0);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_return_items');
        Schema::dropIfExists('purchase_returns');
        Schema::dropIfExists('purchase_attachments');
        Schema::dropIfExists('purchase_expenses');

        Schema::table('purchase_items', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_items', 'returned_quantity')) {
                $table->dropColumn('returned_quantity');
            }
        });

        Schema::table('purchases', function (Blueprint $table) {
            foreach (['document_type', 'quotation_reference', 'order_number', 'supplier_invoice_number', 'expenses_total'] as $column) {
                if (Schema::hasColumn('purchases', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
