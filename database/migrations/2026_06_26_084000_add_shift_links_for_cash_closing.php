<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            if (!Schema::hasColumn('shifts', 'operator_id')) {
                $table->foreignId('operator_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('operators')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('shifts', 'notes')) {
                $table->text('notes')->nullable()->after('difference');
            }
        });

        Schema::table('sales', function (Blueprint $table) {
            if (!Schema::hasColumn('sales', 'shift_id')) {
                $table->foreignId('shift_id')
                    ->nullable()
                    ->after('operator_id')
                    ->constrained('shifts')
                    ->nullOnDelete();
            }
        });

        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'shift_id')) {
                $table->foreignId('shift_id')
                    ->nullable()
                    ->after('operator_id')
                    ->constrained('shifts')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'shift_id')) {
                $table->dropForeign(['shift_id']);
                $table->dropColumn('shift_id');
            }
        });

        Schema::table('sales', function (Blueprint $table) {
            if (Schema::hasColumn('sales', 'shift_id')) {
                $table->dropForeign(['shift_id']);
                $table->dropColumn('shift_id');
            }
        });

        Schema::table('shifts', function (Blueprint $table) {
            if (Schema::hasColumn('shifts', 'notes')) {
                $table->dropColumn('notes');
            }

            if (Schema::hasColumn('shifts', 'operator_id')) {
                $table->dropForeign(['operator_id']);
                $table->dropColumn('operator_id');
            }
        });
    }
};
