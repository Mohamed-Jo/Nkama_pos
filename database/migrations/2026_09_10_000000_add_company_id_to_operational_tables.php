<?php

use App\Services\CurrentCompany;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $tables = [
        'accounting_accounts',
        'accounting_journal_entries',
        'agt_documents',
        'agt_series',
        'bank_accounts',
        'bank_transactions',
        'cash_movements',
        'cash_registers',
        'categories',
        'commercial_price_rules',
        'commercial_promotions',
        'credit_notes',
        'current_account_entries',
        'customer_card_authorization_requests',
        'customer_card_balance_transactions',
        'customer_card_otps',
        'customer_cards',
        'customer_coupons',
        'customers',
        'document_series',
        'stock_transfer_items',
        'sale_items',
        'restaurant_order_items',
        'purchase_return_items',
        'purchase_items',
        'point_transactions',
        'credit_note_items',
        'accounting_journal_lines',
        'expenses',
        'fiscal_years',
        'payment_methods',
        'payments',
        'pos_sessions',
        'product_stock_batches',
        'product_warehouse_stocks',
        'products',
        'purchase_attachments',
        'purchase_expenses',
        'purchase_returns',
        'purchases',
        'restaurant_orders',
        'restaurant_tables',
        'sales',
        'shifts',
        'stock_movements',
        'stock_transfers',
        'suppliers',
        'table_sessions',
        'warehouses',
    ];

    public function up(): void
    {
        $companyId = CurrentCompany::defaultId() ?: DB::table('companies')->orderBy('id')->value('id');

        foreach ($this->tables as $tableName) {
            if (! Schema::hasTable($tableName) || Schema::hasColumn($tableName, 'company_id')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) {
                $table->foreignId('company_id')->nullable()->after('id')->constrained('companies')->nullOnDelete();
            });

            if ($companyId) {
                DB::table($tableName)->whereNull('company_id')->update(['company_id' => $companyId]);
            }
        }
    }

    public function down(): void
    {
        foreach (array_reverse($this->tables) as $tableName) {
            if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, 'company_id')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) {
                $table->dropConstrainedForeignId('company_id');
            });
        }
    }
};
