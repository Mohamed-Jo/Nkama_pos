<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('customer_card_balance_transactions')) {
            Schema::create('customer_card_balance_transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('customer_card_id')->constrained('customer_cards')->cascadeOnDelete();
                $table->foreignId('sale_id')->nullable()->constrained('sales')->nullOnDelete();
                $table->foreignId('shift_id')->nullable()->constrained('shifts')->nullOnDelete();
                $table->foreignId('operator_id')->nullable()->constrained('operators')->nullOnDelete();
                $table->string('type', 24);
                $table->string('method', 24)->nullable();
                $table->decimal('amount', 12, 2);
                $table->decimal('balance_after', 12, 2)->default(0);
                $table->string('description')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_card_balance_transactions');
    }
};