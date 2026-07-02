<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            if (!Schema::hasColumn('purchases', 'approval_status')) {
                $table->string('approval_status', 24)->default('pending')->after('status');
            }

            if (!Schema::hasColumn('purchases', 'approved_by')) {
                $table->unsignedBigInteger('approved_by')->nullable()->after('operator_id');
            }

            if (!Schema::hasColumn('purchases', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approval_status');
            }

            if (!Schema::hasColumn('purchases', 'rejected_by')) {
                $table->unsignedBigInteger('rejected_by')->nullable()->after('approved_by');
            }

            if (!Schema::hasColumn('purchases', 'rejected_at')) {
                $table->timestamp('rejected_at')->nullable()->after('approved_at');
            }

            if (!Schema::hasColumn('purchases', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('rejected_at');
            }
        });

        DB::table('purchases')
            ->whereNull('approval_status')
            ->orWhere('approval_status', '')
            ->update(['approval_status' => 'approved', 'approved_at' => DB::raw('COALESCE(approved_at, created_at)')]);

        DB::table('purchases')
            ->where('approval_status', 'pending')
            ->update(['approval_status' => 'approved', 'approved_at' => DB::raw('COALESCE(approved_at, created_at)')]);
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            foreach (['approval_status', 'approved_at', 'rejected_at', 'rejection_reason', 'approved_by', 'rejected_by'] as $column) {
                if (Schema::hasColumn('purchases', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
