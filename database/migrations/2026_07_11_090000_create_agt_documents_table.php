<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('agt_documents')) {
            return;
        }

        Schema::create('agt_documents', function (Blueprint $table) {
            $table->id();
            $table->string('document_model', 120);
            $table->unsignedBigInteger('document_id');
            $table->string('document_type_code', 10)->nullable();
            $table->string('invoice_number', 80);
            $table->string('status', 20)->default('draft');
            $table->json('payload')->nullable();
            $table->string('payload_hash', 128)->nullable();
            $table->string('external_id', 120)->nullable();
            $table->json('last_response')->nullable();
            $table->text('last_error')->nullable();
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamps();

            $table->unique(['document_model', 'document_id'], 'agt_doc_unique_document');
            $table->index(['status', 'created_at'], 'agt_doc_status_created_idx');
            $table->index('invoice_number', 'agt_doc_invoice_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agt_documents');
    }
};
