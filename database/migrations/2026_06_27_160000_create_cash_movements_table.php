<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shift_id')->nullable()->constrained('shifts')->nullOnDelete();
            $table->foreignId('operator_id')->nullable()->constrained('operators')->nullOnDelete();
            $table->foreignId('current_account_entry_id')->nullable()->constrained('current_account_entries')->nullOnDelete();
            $table->string('type', 30);
            $table->string('method', 20);
            $table->decimal('amount', 14, 2);
            $table->string('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_movements');
    }
};
