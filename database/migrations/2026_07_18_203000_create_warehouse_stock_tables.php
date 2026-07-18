<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique()->nullable();
            $table->string('location')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('product_warehouse_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->integer('quantity')->default(0);
            $table->integer('minimum_stock')->default(0);
            $table->timestamps();
            $table->unique(['product_id', 'warehouse_id']);
        });

        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('from_warehouse_id')->constrained('warehouses');
            $table->foreignId('to_warehouse_id')->constrained('warehouses');
            $table->foreignId('operator_id')->nullable()->constrained('operators')->nullOnDelete();
            $table->string('status')->default('completed');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('stock_transfer_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_transfer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->integer('quantity');
            $table->integer('from_stock_before')->default(0);
            $table->integer('from_stock_after')->default(0);
            $table->integer('to_stock_before')->default(0);
            $table->integer('to_stock_after')->default(0);
            $table->timestamps();
        });

        $defaultWarehouseId = DB::table('warehouses')->insertGetId([
            'name' => 'Armazem Geral',
            'code' => 'GERAL',
            'location' => 'Principal',
            'is_default' => true,
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('products')
            ->select('id', 'stock_quantity', 'minimum_stock')
            ->orderBy('id')
            ->chunk(100, function ($products) use ($defaultWarehouseId) {
                $rows = [];

                foreach ($products as $product) {
                    $rows[] = [
                        'product_id' => $product->id,
                        'warehouse_id' => $defaultWarehouseId,
                        'quantity' => (int) $product->stock_quantity,
                        'minimum_stock' => (int) $product->minimum_stock,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                if ($rows) {
                    DB::table('product_warehouse_stocks')->insert($rows);
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_transfer_items');
        Schema::dropIfExists('stock_transfers');
        Schema::dropIfExists('product_warehouse_stocks');
        Schema::dropIfExists('warehouses');
    }
};
