<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! $this->usesMysql()) {
            return;
        }

        if (Schema::hasColumn('sales', 'user_id')) {
            DB::statement('ALTER TABLE sales MODIFY user_id BIGINT UNSIGNED NULL');
        }

        if (Schema::hasColumn('shifts', 'user_id')) {
            DB::statement('ALTER TABLE shifts MODIFY user_id BIGINT UNSIGNED NULL');
        }
    }

    public function down(): void
    {
        if (! $this->usesMysql()) {
            return;
        }

        if (Schema::hasColumn('sales', 'user_id') && DB::table('sales')->whereNull('user_id')->doesntExist()) {
            DB::statement('ALTER TABLE sales MODIFY user_id BIGINT UNSIGNED NOT NULL');
        }

        if (Schema::hasColumn('shifts', 'user_id') && DB::table('shifts')->whereNull('user_id')->doesntExist()) {
            DB::statement('ALTER TABLE shifts MODIFY user_id BIGINT UNSIGNED NOT NULL');
        }
    }

    private function usesMysql(): bool
    {
        return in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true);
    }
};