<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('name', 120);
            $table->string('type', 30)->default('cash');
            $table->foreignId('bank_account_id')->nullable()->constrained('bank_accounts')->nullOnDelete();
            $table->boolean('active')->default(true);
            $table->boolean('show_in_pos')->default(true);
            $table->boolean('show_in_sales')->default(true);
            $table->boolean('show_in_expenses')->default(true);
            $table->boolean('show_in_current_account')->default(true);
            $table->boolean('requires_bank_account')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['active', 'sort_order']);
        });

        $now = now();
        DB::table('payment_methods')->insert([
            ['code' => 'cash', 'name' => 'Dinheiro', 'type' => 'cash', 'active' => true, 'show_in_pos' => true, 'show_in_sales' => true, 'show_in_expenses' => true, 'show_in_current_account' => true, 'requires_bank_account' => false, 'sort_order' => 10, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'card', 'name' => 'Cartao/TPA', 'type' => 'card', 'active' => true, 'show_in_pos' => true, 'show_in_sales' => true, 'show_in_expenses' => true, 'show_in_current_account' => true, 'requires_bank_account' => false, 'sort_order' => 20, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'transf', 'name' => 'Transferencia', 'type' => 'bank_transfer', 'active' => true, 'show_in_pos' => true, 'show_in_sales' => true, 'show_in_expenses' => true, 'show_in_current_account' => true, 'requires_bank_account' => false, 'sort_order' => 30, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'multi', 'name' => 'Pagamento Misto', 'type' => 'mixed', 'active' => true, 'show_in_pos' => true, 'show_in_sales' => true, 'show_in_expenses' => false, 'show_in_current_account' => false, 'requires_bank_account' => false, 'sort_order' => 40, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'bank', 'name' => 'Banco', 'type' => 'bank', 'active' => true, 'show_in_pos' => false, 'show_in_sales' => false, 'show_in_expenses' => true, 'show_in_current_account' => false, 'requires_bank_account' => true, 'sort_order' => 50, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'customer_card', 'name' => 'Cartao Cliente', 'type' => 'customer_card', 'active' => true, 'show_in_pos' => true, 'show_in_sales' => false, 'show_in_expenses' => false, 'show_in_current_account' => false, 'requires_bank_account' => false, 'sort_order' => 60, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'pending', 'name' => 'Pendente', 'type' => 'credit', 'active' => true, 'show_in_pos' => false, 'show_in_sales' => false, 'show_in_expenses' => true, 'show_in_current_account' => false, 'requires_bank_account' => false, 'sort_order' => 70, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};