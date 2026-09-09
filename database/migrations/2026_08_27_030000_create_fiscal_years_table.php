<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fiscal_years', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80);
            $table->unsignedSmallInteger('year')->unique();
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status', 20)->default('open');
            $table->boolean('is_active')->default(false);
            $table->timestamp('opened_at')->nullable();
            $table->foreignId('opened_by')->nullable()->constrained('operators')->nullOnDelete();
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('operators')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['status', 'is_active']);
            $table->index(['start_date', 'end_date']);
        });

        $now = now();
        $year = (int) $now->year;

        DB::table('fiscal_years')->insert([
            'name' => 'Exercicio ' . $year,
            'year' => $year,
            'start_date' => $year . '-01-01',
            'end_date' => $year . '-12-31',
            'status' => 'open',
            'is_active' => true,
            'opened_at' => $now,
            'notes' => 'Exercicio criado automaticamente na migracao inicial.',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('fiscal_years');
    }
};