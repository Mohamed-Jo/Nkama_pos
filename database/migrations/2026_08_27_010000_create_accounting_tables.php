<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name');
            $table->string('type', 30);
            $table->foreignId('parent_id')->nullable()->constrained('accounting_accounts')->nullOnDelete();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index(['type', 'active']);
        });

        Schema::create('accounting_journal_entries', function (Blueprint $table) {
            $table->id();
            $table->date('entry_date');
            $table->string('document_number')->nullable();
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('description');
            $table->string('status', 20)->default('posted');
            $table->foreignId('posted_by')->nullable()->constrained('operators')->nullOnDelete();
            $table->timestamps();

            $table->index('entry_date');
            $table->index(['source_type', 'source_id']);
        });

        Schema::create('accounting_journal_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_entry_id')->constrained('accounting_journal_entries')->cascadeOnDelete();
            $table->foreignId('accounting_account_id')->constrained('accounting_accounts')->restrictOnDelete();
            $table->decimal('debit', 14, 2)->default(0);
            $table->decimal('credit', 14, 2)->default(0);
            $table->string('memo')->nullable();
            $table->timestamps();

            $table->index('accounting_account_id');
        });

        $now = now();
        DB::table('accounting_accounts')->insert([
            ['code' => '11', 'name' => 'Caixa', 'type' => 'asset', 'created_at' => $now, 'updated_at' => $now],
            ['code' => '12', 'name' => 'Bancos', 'type' => 'asset', 'created_at' => $now, 'updated_at' => $now],
            ['code' => '21', 'name' => 'Clientes', 'type' => 'asset', 'created_at' => $now, 'updated_at' => $now],
            ['code' => '31', 'name' => 'Fornecedores', 'type' => 'liability', 'created_at' => $now, 'updated_at' => $now],
            ['code' => '41', 'name' => 'Vendas', 'type' => 'income', 'created_at' => $now, 'updated_at' => $now],
            ['code' => '51', 'name' => 'Compras/Mercadorias', 'type' => 'expense', 'created_at' => $now, 'updated_at' => $now],
            ['code' => '62', 'name' => 'Gastos gerais', 'type' => 'expense', 'created_at' => $now, 'updated_at' => $now],
            ['code' => '81', 'name' => 'Capital/Resultados', 'type' => 'equity', 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_journal_lines');
        Schema::dropIfExists('accounting_journal_entries');
        Schema::dropIfExists('accounting_accounts');
    }
};