<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restaurant_tables', function (Blueprint $table) {

            $table->id();

            $table->string('name');
            $table->integer('capacity')->default(4);

            $table->enum('status', [
                'free',
                'occupied',
                'reserved'
            ])->default('free');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restaurant_tables');
    }
};