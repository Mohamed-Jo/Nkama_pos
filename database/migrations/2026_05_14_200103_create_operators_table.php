<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('operators', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable()->unique();
            $table->string('pin', 255)->unique();
            $table->string('pin_fingerprint', 64)->nullable()->unique();
            $table->string('password')->nullable();
            $table->string('recovery_code')->nullable();
            $table->timestamp('recovery_code_used_at')->nullable();
            $table->string('role')->default('cashier');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operators');
    }
};
