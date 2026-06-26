<?php

use App\Models\Operator;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Operator::where('role', 'super_user')->exists()) {
            $firstOperator = Operator::orderBy('id')->first();

            if ($firstOperator) {
                $firstOperator->update([
                    'role' => 'super_user',
                    'active' => true,
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('operators')
            ->where('role', 'super_user')
            ->update(['role' => 'admin']);
    }
};
