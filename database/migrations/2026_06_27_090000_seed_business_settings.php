<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('app_settings')) {
            return;
        }

        DB::table('app_settings')->updateOrInsert(
            ['key' => 'company_profile'],
            [
                'value' => json_encode([
                    'name' => '',
                    'location' => '',
                    'nif' => '',
                    'iban' => '',
                    'account_number' => '',
                    'swift' => '',
                    'logo_path' => '',
                ]),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        DB::table('app_settings')->updateOrInsert(
            ['key' => 'tax_settings'],
            [
                'value' => json_encode([
                    'active' => false,
                    'value' => 14,
                ]),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        if (!Schema::hasTable('app_settings')) {
            return;
        }

        DB::table('app_settings')
            ->whereIn('key', ['company_profile', 'tax_settings'])
            ->delete();
    }
};
