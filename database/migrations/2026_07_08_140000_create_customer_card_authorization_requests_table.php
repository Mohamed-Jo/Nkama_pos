<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('customer_card_authorization_requests')) {
            Schema::dropIfExists('customer_card_authorization_requests');
        }

        Schema::create('customer_card_authorization_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_card_id');
            $table->unsignedBigInteger('sale_id')->nullable();
            $table->unsignedBigInteger('requested_by_operator_id')->nullable();
            $table->unsignedBigInteger('reviewed_by_operator_id')->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('reason', 180)->nullable();
            $table->string('status', 20)->default('pending');
            $table->string('token_hash')->nullable();
            $table->json('context')->nullable();
            $table->string('decision_note', 180)->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('used_at')->nullable();
            $table->timestamps();

            $table->foreign('customer_card_id', 'cc_auth_card_fk')->references('id')->on('customer_cards')->cascadeOnDelete();
            $table->foreign('sale_id', 'cc_auth_sale_fk')->references('id')->on('sales')->nullOnDelete();
            $table->foreign('requested_by_operator_id', 'cc_auth_requester_fk')->references('id')->on('operators')->nullOnDelete();
            $table->foreign('reviewed_by_operator_id', 'cc_auth_reviewer_fk')->references('id')->on('operators')->nullOnDelete();
            $table->index(['customer_card_id', 'status', 'expires_at'], 'card_auth_card_status_expires_idx');
            $table->index(['requested_by_operator_id', 'status', 'expires_at'], 'card_auth_operator_status_expires_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_card_authorization_requests');
    }
};