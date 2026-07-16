<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('customer_cards')) {
            return;
        }

        DB::table('customer_cards')
            ->whereNull('expires_at')
            ->orderBy('id')
            ->chunkById(200, function ($cards) {
                foreach ($cards as $card) {
                    $baseDate = $card->issued_at ?: $card->created_at ?: now();

                    DB::table('customer_cards')
                        ->where('id', $card->id)
                        ->update([
                            'expires_at' => Carbon::parse($baseDate)->addYear(),
                            'updated_at' => now(),
                        ]);
                }
            });
    }

    public function down(): void
    {
        // Keep existing expiration dates; removing them would weaken card controls.
    }
};