<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            if (!Schema::hasColumn('sales', 'operator_id')) {
                $table->foreignId('operator_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('operators')
                    ->nullOnDelete();
            }
        });

        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'operator_id')) {
                $table->foreignId('operator_id')
                    ->nullable()
                    ->after('sale_id')
                    ->constrained('operators')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'operator_id')) {
                $table->dropForeign(['operator_id']);
                $table->dropColumn('operator_id');
            }
        });

        Schema::table('sales', function (Blueprint $table) {
            if (Schema::hasColumn('sales', 'operator_id')) {
                $table->dropForeign(['operator_id']);
                $table->dropColumn('operator_id');
            }
        });
    }
};
