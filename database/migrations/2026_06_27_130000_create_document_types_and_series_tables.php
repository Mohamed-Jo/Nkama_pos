<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique();
            $table->string('name');
            $table->string('description')->nullable();
            $table->boolean('affects_current_account')->default(false);
            $table->boolean('is_credit_note')->default(false);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('document_series', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_type_id')->constrained('document_types')->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->string('code', 20);
            $table->unsignedBigInteger('start_number')->default(1);
            $table->unsignedBigInteger('current_number')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(['document_type_id', 'year', 'code']);
        });

        Schema::table('sales', function (Blueprint $table) {
            if (!Schema::hasColumn('sales', 'document_type_code')) {
                $table->string('document_type_code', 10)->nullable()->after('invoice_number');
            }

            if (!Schema::hasColumn('sales', 'document_series_id')) {
                $table->foreignId('document_series_id')->nullable()->after('document_type_code')->constrained('document_series')->nullOnDelete();
            }

            if (!Schema::hasColumn('sales', 'document_number')) {
                $table->unsignedBigInteger('document_number')->nullable()->after('document_series_id');
            }
        });

        $types = [
            ['code' => 'FR', 'name' => 'Fatura Recibo', 'description' => 'Venda normal liquidada no momento.', 'affects_current_account' => false, 'is_credit_note' => false],
            ['code' => 'FT', 'name' => 'Fatura', 'description' => 'Venda faturada para conta corrente.', 'affects_current_account' => true, 'is_credit_note' => false],
            ['code' => 'NC', 'name' => 'Nota de Credito', 'description' => 'Anulacao ou correcao de documento original.', 'affects_current_account' => true, 'is_credit_note' => true],
            ['code' => 'FA', 'name' => 'Fatura Adiantamento', 'description' => 'Documento de adiantamento.', 'affects_current_account' => false, 'is_credit_note' => false],
        ];

        foreach ($types as $type) {
            DB::table('document_types')->updateOrInsert(
                ['code' => $type['code']],
                array_merge($type, [
                    'active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }

        $year = (int) now()->year;

        foreach (DB::table('document_types')->get() as $type) {
            DB::table('document_series')->updateOrInsert(
                [
                    'document_type_id' => $type->id,
                    'year' => $year,
                    'code' => (string) $year,
                ],
                [
                    'start_number' => 1,
                    'current_number' => 0,
                    'active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            if (Schema::hasColumn('sales', 'document_series_id')) {
                $table->dropConstrainedForeignId('document_series_id');
            }

            if (Schema::hasColumn('sales', 'document_number')) {
                $table->dropColumn('document_number');
            }

            if (Schema::hasColumn('sales', 'document_type_code')) {
                $table->dropColumn('document_type_code');
            }
        });

        Schema::dropIfExists('document_series');
        Schema::dropIfExists('document_types');
    }
};
