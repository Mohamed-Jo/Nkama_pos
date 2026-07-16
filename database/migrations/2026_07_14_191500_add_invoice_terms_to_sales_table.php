<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            if (! Schema::hasColumn('sales', 'currency')) {
                $table->string('currency', 12)->nullable()->after('status');
            }

            if (! Schema::hasColumn('sales', 'exchange_rate')) {
                $table->decimal('exchange_rate', 18, 6)->default(1)->after('currency');
            }

            if (! Schema::hasColumn('sales', 'exemption_reason')) {
                $table->string('exemption_reason')->nullable()->after('exchange_rate');
            }

            if (! Schema::hasColumn('sales', 'commercial_discount')) {
                $table->decimal('commercial_discount', 10, 2)->default(0)->after('exemption_reason');
            }

            if (! Schema::hasColumn('sales', 'payment_condition')) {
                $table->string('payment_condition', 120)->nullable()->after('commercial_discount');
            }

            if (! Schema::hasColumn('sales', 'due_date')) {
                $table->date('due_date')->nullable()->after('payment_condition');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            foreach (['due_date', 'payment_condition', 'commercial_discount', 'exemption_reason', 'exchange_rate', 'currency'] as $column) {
                if (Schema::hasColumn('sales', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};