<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('product_stock_batches')) {
            Schema::create('product_stock_batches', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
                $table->string('lot_number', 80)->nullable();
                $table->date('expires_at')->nullable();
                $table->string('serial_number', 120)->nullable();
                $table->unsignedInteger('quantity')->default(0);
                $table->unsignedInteger('reserved_quantity')->default(0);
                $table->foreignId('operator_id')->nullable()->constrained('operators')->nullOnDelete();
                $table->timestamps();
                $table->index(['product_id', 'warehouse_id', 'lot_number'], 'psb_product_wh_lot_idx');
                $table->unique(['product_id', 'warehouse_id', 'serial_number'], 'psb_product_wh_serial_unique');
            });
        }

        Schema::table('stock_movements', function (Blueprint $table) {
            if (!Schema::hasColumn('stock_movements', 'lot_number')) {
                $table->string('lot_number', 80)->nullable()->after('warehouse_id');
            }
            if (!Schema::hasColumn('stock_movements', 'expires_at')) {
                $table->date('expires_at')->nullable()->after('lot_number');
            }
            if (!Schema::hasColumn('stock_movements', 'serial_number')) {
                $table->string('serial_number', 120)->nullable()->after('expires_at');
            }
        });

        Schema::table('stock_transfer_items', function (Blueprint $table) {
            if (!Schema::hasColumn('stock_transfer_items', 'lot_number')) {
                $table->string('lot_number', 80)->nullable()->after('product_id');
            }
            if (!Schema::hasColumn('stock_transfer_items', 'expires_at')) {
                $table->date('expires_at')->nullable()->after('lot_number');
            }
            if (!Schema::hasColumn('stock_transfer_items', 'serial_number')) {
                $table->string('serial_number', 120)->nullable()->after('expires_at');
            }
        });

        Schema::table('stock_transfers', function (Blueprint $table) {
            if (!Schema::hasColumn('stock_transfers', 'approved_by')) {
                $table->foreignId('approved_by')->nullable()->after('operator_id')->constrained('operators')->nullOnDelete();
            }
            if (!Schema::hasColumn('stock_transfers', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            }
            if (!Schema::hasColumn('stock_transfers', 'rejected_by')) {
                $table->foreignId('rejected_by')->nullable()->after('approved_at')->constrained('operators')->nullOnDelete();
            }
            if (!Schema::hasColumn('stock_transfers', 'rejected_at')) {
                $table->timestamp('rejected_at')->nullable()->after('rejected_by');
            }
            if (!Schema::hasColumn('stock_transfers', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('rejected_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('stock_transfers', function (Blueprint $table) {
            foreach (['approved_by', 'approved_at', 'rejected_by', 'rejected_at', 'rejection_reason'] as $column) {
                if (Schema::hasColumn('stock_transfers', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('stock_transfer_items', function (Blueprint $table) {
            foreach (['lot_number', 'expires_at', 'serial_number'] as $column) {
                if (Schema::hasColumn('stock_transfer_items', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            foreach (['lot_number', 'expires_at', 'serial_number'] as $column) {
                if (Schema::hasColumn('stock_movements', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::dropIfExists('product_stock_batches');
    }
};
