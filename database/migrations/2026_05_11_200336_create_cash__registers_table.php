<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_registers', function (Blueprint $table) {

            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->decimal('opening_amount', 18, 2)->default(0);

            $table->decimal('closing_amount', 18, 2)
                ->nullable();

            $table->decimal('total_sales', 18, 2)
                ->default(0);

            $table->decimal('total_cash', 18, 2)
                ->default(0);

            $table->decimal('total_card', 18, 2)
                ->default(0);

            $table->decimal('total_multi', 18, 2)
                ->default(0);

            $table->string('status')
                ->default('open'); // open / closed

            $table->timestamp('opened_at');

            $table->timestamp('closed_at')
                ->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_registers');
    }
};