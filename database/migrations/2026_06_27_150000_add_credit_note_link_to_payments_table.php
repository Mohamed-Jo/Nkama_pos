<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'credit_note_id')) {
                $table->foreignId('credit_note_id')
                    ->nullable()
                    ->after('sale_id')
                    ->constrained('credit_notes')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('payments', 'notes')) {
                $table->string('notes')->nullable()->after('amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'credit_note_id')) {
                $table->dropConstrainedForeignId('credit_note_id');
            }

            if (Schema::hasColumn('payments', 'notes')) {
                $table->dropColumn('notes');
            }
        });
    }
};
