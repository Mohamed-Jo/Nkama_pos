<?php

use App\Services\BusinessSettings;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('companies')) {
            Schema::create('companies', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('location')->nullable();
                $table->string('nif', 80)->nullable();
                $table->string('iban', 80)->nullable();
                $table->string('account_number', 80)->nullable();
                $table->string('bank_name', 120)->nullable();
                $table->string('swift', 80)->nullable();
                $table->string('logo_path')->nullable();
                $table->string('login_background_path')->nullable();
                $table->boolean('active')->default(true);
                $table->timestamps();
            });
        }

        if (DB::table('companies')->count() === 0) {
            $profile = BusinessSettings::company();

            DB::table('companies')->insert([
                'name' => $profile['name'] ?: config('app.name', 'NKAMA POS'),
                'location' => $profile['location'] ?: null,
                'nif' => $profile['nif'] ?: null,
                'iban' => $profile['iban'] ?: null,
                'account_number' => $profile['account_number'] ?: null,
                'bank_name' => $profile['bank_name'] ?: null,
                'swift' => $profile['swift'] ?: null,
                'logo_path' => $profile['logo_path'] ?: null,
                'login_background_path' => $profile['login_background_path'] ?: null,
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (! Schema::hasColumn('operators', 'company_id')) {
            Schema::table('operators', function (Blueprint $table) {
                $table->foreignId('company_id')->nullable()->after('id')->constrained('companies')->nullOnDelete();
            });
        }

        $defaultCompanyId = DB::table('companies')->orderBy('id')->value('id');

        if ($defaultCompanyId) {
            DB::table('operators')->whereNull('company_id')->update(['company_id' => $defaultCompanyId]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('operators', 'company_id')) {
            Schema::table('operators', function (Blueprint $table) {
                $table->dropConstrainedForeignId('company_id');
            });
        }

        Schema::dropIfExists('companies');
    }
};
