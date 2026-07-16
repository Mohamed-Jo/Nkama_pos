<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('customer_card_otps')) {
            return;
        }

        Schema::create('customer_card_otps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_card_id')->constrained('customer_cards')->cascadeOnDelete();
            $table->foreignId('sale_id')->nullable()->constrained('sales')->nullOnDelete();
            $table->string('purpose', 40)->default('sale_payment');
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('code_hash');
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->string('sent_to', 80)->nullable();
            $table->foreignId('requested_by_operator_id')->nullable()->constrained('operators')->nullOnDelete();
            $table->foreignId('verified_by_operator_id')->nullable()->constrained('operators')->nullOnDelete();
            $table->timestamps();

            $table->index(['customer_card_id', 'purpose', 'used_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_card_otps');
    }
};