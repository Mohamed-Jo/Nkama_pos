<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('operators', function (Blueprint $table) {
            if (Schema::hasColumn('operators', 'pin')) {
                $table->string('pin', 255)->change();
            }

            if (!Schema::hasColumn('operators', 'email')) {
                $table->string('email')->nullable()->unique()->after('name');
            }

            if (!Schema::hasColumn('operators', 'password')) {
                $table->string('password')->nullable()->after('pin');
            }
        });

        DB::table('operators')->orderBy('id')->chunkById(100, function ($operators) {
            foreach ($operators as $operator) {
                if ($operator->pin && !str_starts_with($operator->pin, '$2y$')) {
                    DB::table('operators')
                        ->where('id', $operator->id)
                        ->update(['pin' => Hash::make($operator->pin)]);
                }
            }
        });

        if (!Schema::hasTable('operator_password_resets')) {
            Schema::create('operator_password_resets', function (Blueprint $table) {
                $table->string('email')->primary();
                $table->string('token');
                $table->timestamp('created_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('operator_password_resets');

        Schema::table('operators', function (Blueprint $table) {
            if (Schema::hasColumn('operators', 'password')) {
                $table->dropColumn('password');
            }

            if (Schema::hasColumn('operators', 'email')) {
                $table->dropUnique(['email']);
                $table->dropColumn('email');
            }
        });
    }
};
