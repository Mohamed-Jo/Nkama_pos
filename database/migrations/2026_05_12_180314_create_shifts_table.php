<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
   public function up(): void
{
    Schema::create('shifts', function (Blueprint $table) {
        $table->id();

        $table->foreignId('user_id')
            ->constrained()
            ->cascadeOnDelete();

        // 💰 abertura e fecho
        $table->decimal('opening_cash', 12, 2)->default(0);
        $table->decimal('closing_cash', 12, 2)->nullable();

        // 📊 cálculo automático
        $table->decimal('expected_cash', 12, 2)->default(0);
        $table->decimal('difference', 12, 2)->default(0);

        // 💳 separação por métodos (ESSENCIAL PARA ERP)
        $table->decimal('cash_sales_total', 12, 2)->default(0);
        $table->decimal('card_sales_total', 12, 2)->default(0);
        $table->decimal('multi_sales_total', 12, 2)->default(0);

        // 🧾 controlo de operações
        $table->integer('sales_count')->default(0);
        $table->decimal('total_sales', 12, 2)->default(0);

        // ⏱ controlo de tempo
        $table->timestamp('opened_at')->nullable();
        $table->timestamp('closed_at')->nullable();

        // 🔐 estado do caixa
        $table->string('status')->default('open');

        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shifts');
    }
};
