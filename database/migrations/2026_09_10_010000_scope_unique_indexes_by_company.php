<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $indexes = [
        'accounting_accounts' => [
            'legacy' => ['accounting_accounts_code_unique' => ['code']],
            'company' => ['acct_company_code_unique' => ['company_id', 'code']],
        ],
        'agt_series' => [
            'legacy' => ['agt_series_unique_local_series' => ['environment', 'document_type_code', 'series_year', 'series_code']],
            'company' => ['agt_series_company_local_unique' => ['company_id', 'environment', 'document_type_code', 'series_year', 'series_code']],
        ],
        'credit_notes' => [
            'legacy' => ['credit_notes_invoice_number_unique' => ['invoice_number']],
            'company' => ['credit_notes_company_invoice_unique' => ['company_id', 'invoice_number']],
        ],
        'customer_cards' => [
            'legacy' => ['customer_cards_card_number_unique' => ['card_number']],
            'company' => ['customer_cards_company_number_unique' => ['company_id', 'card_number']],
        ],
        'customer_coupons' => [
            'legacy' => ['customer_coupons_coupon_code_unique' => ['coupon_code']],
            'company' => ['coupons_company_code_unique' => ['company_id', 'coupon_code']],
        ],
        'document_series' => [
            'protect' => ['document_series_type_fk_idx' => ['document_type_id']],
            'legacy' => ['document_series_document_type_id_year_code_unique' => ['document_type_id', 'year', 'code']],
            'company' => ['doc_series_company_type_year_code_unique' => ['company_id', 'document_type_id', 'year', 'code']],
        ],
        'fiscal_years' => [
            'legacy' => ['fiscal_years_year_unique' => ['year']],
            'company' => ['fiscal_years_company_year_unique' => ['company_id', 'year']],
        ],
        'payment_methods' => [
            'legacy' => ['payment_methods_code_unique' => ['code']],
            'company' => ['payment_methods_company_code_unique' => ['company_id', 'code']],
        ],
        'products' => [
            'legacy' => ['products_barcode_unique' => ['barcode']],
            'company' => ['products_company_barcode_unique' => ['company_id', 'barcode']],
        ],

        'stock_transfers' => [
            'legacy' => ['stock_transfers_reference_unique' => ['reference']],
            'company' => ['stock_transfers_company_ref_unique' => ['company_id', 'reference']],
        ],
        'warehouses' => [
            'legacy' => ['warehouses_code_unique' => ['code']],
            'company' => ['warehouses_company_code_unique' => ['company_id', 'code']],
        ],
    ];

    public function up(): void
    {
        foreach ($this->indexes as $table => $config) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'company_id')) {
                continue;
            }

            foreach ($config['protect'] ?? [] as $index => $columns) {
                $this->addIndex($table, $index, $columns);
            }

            foreach ($config['legacy'] as $index => $columns) {
                $this->dropIndex($table, $index);
            }

            foreach ($config['company'] as $index => $columns) {
                $this->addUnique($table, $index, $columns);
            }
        }
    }

    public function down(): void
    {
        foreach ($this->indexes as $table => $config) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach ($config['company'] as $index => $columns) {
                $this->dropIndex($table, $index);
            }

            foreach ($config['legacy'] as $index => $columns) {
                $this->addUnique($table, $index, $columns);
            }
        }
    }

    private function addUnique(string $table, string $index, array $columns): void
    {
        if ($this->indexExists($table, $index)) {
            return;
        }

        DB::statement(sprintf(
            'ALTER TABLE `%s` ADD UNIQUE `%s` (%s)',
            $table,
            $index,
            $this->columnList($columns)
        ));
    }

    private function addIndex(string $table, string $index, array $columns): void
    {
        if ($this->indexExists($table, $index)) {
            return;
        }

        DB::statement(sprintf(
            'ALTER TABLE `%s` ADD INDEX `%s` (%s)',
            $table,
            $index,
            $this->columnList($columns)
        ));
    }

    private function dropIndex(string $table, string $index): void
    {
        if (! $this->indexExists($table, $index)) {
            return;
        }

        DB::statement(sprintf('ALTER TABLE `%s` DROP INDEX `%s`', $table, $index));
    }

    private function indexExists(string $table, string $index): bool
    {
        return ! empty(DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$index]));
    }

    private function columnList(array $columns): string
    {
        return collect($columns)->map(fn ($column) => "`{$column}`")->implode(', ');
    }
};