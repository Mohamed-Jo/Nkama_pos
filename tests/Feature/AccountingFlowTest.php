<?php

namespace Tests\Feature;

use App\Models\AccountingAccount;
use App\Models\AccountingJournalEntry;
use App\Models\Product;
use App\Models\Category;
use App\Models\Operator;
use App\Models\Payments;
use App\Models\Sale;
use App\Services\AccountingPostingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AccountingFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_accounting_dashboard_can_be_rendered(): void
    {
        $operator = $this->operator('accountant');

        $this->withSession(['operator_id' => $operator->id, 'operator_role' => 'accountant'])
            ->get('/admin/accounting')
            ->assertOk()
            ->assertSee('Contabilidade');
    }

    public function test_balanced_journal_entry_can_be_posted(): void
    {
        $operator = $this->operator('accountant');
        $cash = AccountingAccount::where('code', '11')->firstOrFail();
        $income = AccountingAccount::where('code', '41')->firstOrFail();

        $this->withSession(['operator_id' => $operator->id, 'operator_role' => 'accountant'])
            ->post('/admin/accounting/entries', [
                'entry_date' => now()->toDateString(),
                'document_number' => 'LC-001',
                'description' => 'Lancamento de teste',
                'lines' => [
                    ['accounting_account_id' => $cash->id, 'debit' => 1000, 'credit' => 0],
                    ['accounting_account_id' => $income->id, 'debit' => 0, 'credit' => 1000],
                ],
            ])
            ->assertRedirect();

        $entry = AccountingJournalEntry::with('lines')->firstOrFail();
        $this->assertSame('LC-001', $entry->document_number);
        $this->assertEquals(1000.00, (float) $entry->lines->sum('debit'));
        $this->assertEquals(1000.00, (float) $entry->lines->sum('credit'));
    }

    public function test_unbalanced_journal_entry_is_rejected(): void
    {
        $operator = $this->operator('accountant');
        $cash = AccountingAccount::where('code', '11')->firstOrFail();
        $income = AccountingAccount::where('code', '41')->firstOrFail();

        $this->withSession(['operator_id' => $operator->id, 'operator_role' => 'accountant'])
            ->from('/admin/accounting')
            ->post('/admin/accounting/entries', [
                'entry_date' => now()->toDateString(),
                'description' => 'Lancamento incorreto',
                'lines' => [
                    ['accounting_account_id' => $cash->id, 'debit' => 1000, 'credit' => 0],
                    ['accounting_account_id' => $income->id, 'debit' => 0, 'credit' => 900],
                ],
            ])
            ->assertRedirect('/admin/accounting')
            ->assertSessionHasErrors('lines');

        $this->assertSame(0, AccountingJournalEntry::count());
    }

    public function test_auditor_can_view_but_cannot_post_accounting_entries(): void
    {
        $operator = $this->operator('auditor');
        $cash = AccountingAccount::where('code', '11')->firstOrFail();
        $income = AccountingAccount::where('code', '41')->firstOrFail();

        $this->withSession(['operator_id' => $operator->id, 'operator_role' => 'auditor'])
            ->get('/admin/accounting')
            ->assertOk()
            ->assertDontSee('Registar Lancamento');

        $this->withSession(['operator_id' => $operator->id, 'operator_role' => 'auditor'])
            ->post('/admin/accounting/entries', [
                'entry_date' => now()->toDateString(),
                'description' => 'Tentativa auditor',
                'lines' => [
                    ['accounting_account_id' => $cash->id, 'debit' => 100, 'credit' => 0],
                    ['accounting_account_id' => $income->id, 'debit' => 0, 'credit' => 100],
                ],
            ])
            ->assertRedirect('/admin/dashboard');
    }
    public function test_sale_posting_creates_balanced_accounting_entry_once(): void
    {
        $operator = $this->operator('accountant');
        $sale = Sale::create(collect([
            'operator_id' => $operator->id,
            'invoice_number' => 'FR-TEST-1',
            'document_type_code' => 'FR',
            'subtotal' => 900,
            'tax' => 100,
            'total' => 1000,
            'paid' => 1000,
            'change' => 0,
            'payment_method' => 'cash',
            'payment_status' => 'paid',
            'status' => 'paid',
        ])->filter(fn ($value, $column) => Schema::hasColumn('sales', $column))->all());

        Payments::create([
            'sale_id' => $sale->id,
            'operator_id' => $operator->id,
            'method' => 'cash',
            'amount' => 1000,
        ]);

        app(AccountingPostingService::class)->postSale($sale);
        app(AccountingPostingService::class)->postSale($sale->refresh());

        $entry = AccountingJournalEntry::with('lines.account')->where('source_type', 'sale')->where('source_id', $sale->id)->firstOrFail();

        $this->assertSame(1, AccountingJournalEntry::where('source_type', 'sale')->where('source_id', $sale->id)->count());
        $this->assertEquals(1000.00, (float) $entry->lines->sum('debit'));
        $this->assertEquals(1000.00, (float) $entry->lines->sum('credit'));
        $this->assertSame(1, $entry->lines->filter(fn ($line) => $line->account?->code === '11' && (float) $line->debit === 1000.0)->count());
        $this->assertSame(1, $entry->lines->filter(fn ($line) => $line->account?->code === '41' && (float) $line->credit === 900.0)->count());
        $this->assertSame(1, $entry->lines->filter(fn ($line) => $line->account?->code === '34' && (float) $line->credit === 100.0)->count());
    }

    public function test_manual_entry_rejects_inactive_account(): void
    {
        $operator = $this->operator('accountant');
        $inactive = AccountingAccount::create([
            'code' => '999',
            'name' => 'Conta inativa',
            'type' => 'asset',
            'active' => false,
        ]);
        $income = AccountingAccount::where('code', '41')->firstOrFail();

        $this->withSession(['operator_id' => $operator->id, 'operator_role' => 'accountant'])
            ->from('/admin/accounting')
            ->post('/admin/accounting/entries', [
                'entry_date' => now()->toDateString(),
                'description' => 'Tentativa conta inativa',
                'lines' => [
                    ['accounting_account_id' => $inactive->id, 'debit' => 100, 'credit' => 0],
                    ['accounting_account_id' => $income->id, 'debit' => 0, 'credit' => 100],
                ],
            ])
            ->assertRedirect('/admin/accounting')
            ->assertSessionHasErrors('lines.0.accounting_account_id');
    }
    public function test_accounting_dashboard_seeds_standard_accounts_and_flags_unposted_documents(): void
    {
        $operator = $this->operator('accountant');
        $sale = Sale::create(collect([
            'invoice_number' => 'FR-MISSING-1',
            'document_type_code' => 'FR',
            'subtotal' => 500,
            'tax' => 0,
            'total' => 500,
            'payment_method' => 'cash',
            'payment_status' => 'paid',
            'status' => 'paid',
        ])->filter(fn ($value, $column) => Schema::hasColumn('sales', $column))->all());

        $this->withSession(['operator_id' => $operator->id, 'operator_role' => 'accountant'])
            ->get('/admin/accounting')
            ->assertOk()
            ->assertSee('Documentos sem lancamento')
            ->assertSee('FR-MISSING-1');

        $this->assertTrue(AccountingAccount::where('code', '13')->where('name', 'TPA/Cartoes a receber')->exists());
        $this->assertTrue(AccountingAccount::where('code', '35')->where('name', 'IVA dedutivel')->exists());
        $this->assertSame(0, AccountingJournalEntry::where('source_type', 'sale')->where('source_id', $sale->id)->count());
    }

    public function test_card_sale_posts_to_card_clearing_account(): void
    {
        $operator = $this->operator('accountant');
        $sale = Sale::create(collect([
            'operator_id' => $operator->id,
            'invoice_number' => 'FR-CARD-1',
            'document_type_code' => 'FR',
            'subtotal' => 1000,
            'tax' => 0,
            'total' => 1000,
            'payment_method' => 'card',
            'payment_status' => 'paid',
            'status' => 'paid',
        ])->filter(fn ($value, $column) => Schema::hasColumn('sales', $column))->all());

        Payments::create([
            'sale_id' => $sale->id,
            'operator_id' => $operator->id,
            'method' => 'card',
            'amount' => 1000,
        ]);

        app(AccountingPostingService::class)->postSale($sale);

        $entry = AccountingJournalEntry::with('lines.account')->where('source_type', 'sale')->where('source_id', $sale->id)->firstOrFail();

        $this->assertSame(1, $entry->lines->filter(fn ($line) => $line->account?->code === '13' && (float) $line->debit === 1000.0)->count());
    }
    public function test_sale_posting_records_inventory_cost_when_product_has_purchase_price(): void
    {
        $operator = $this->operator('accountant');
        $category = Category::create(['name' => 'Geral', 'status' => true]);
        $product = Product::create(collect([
            'category_id' => $category->id,
            'name' => 'Produto custo',
            'purchase_price' => 300,
            'selling_price' => 500,
            'stock_quantity' => 10,
            'minimum_stock' => 1,
            'unit' => 'un',
            'status' => true,
            'track_stock' => true,
        ])->filter(fn ($value, $column) => Schema::hasColumn('products', $column))->all());
        $sale = Sale::create(collect([
            'operator_id' => $operator->id,
            'invoice_number' => 'FR-COST-1',
            'document_type_code' => 'FR',
            'subtotal' => 1000,
            'tax' => 0,
            'total' => 1000,
            'payment_method' => 'cash',
            'payment_status' => 'paid',
            'status' => 'paid',
        ])->filter(fn ($value, $column) => Schema::hasColumn('sales', $column))->all());

        $sale->items()->create(collect([
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 500,
            'subtotal' => 1000,
            'net_subtotal' => 1000,
            'tax_rate' => 0,
            'tax_amount' => 0,
        ])->filter(fn ($value, $column) => Schema::hasColumn('sale_items', $column))->all());
        Payments::create([
            'sale_id' => $sale->id,
            'operator_id' => $operator->id,
            'method' => 'cash',
            'amount' => 1000,
        ]);

        app(AccountingPostingService::class)->postSale($sale);

        $entry = AccountingJournalEntry::with('lines.account')->where('source_type', 'sale')->where('source_id', $sale->id)->firstOrFail();

        $this->assertSame(1, $entry->lines->filter(fn ($line) => $line->account?->code === '61' && (float) $line->debit === 600.0)->count());
        $this->assertSame(1, $entry->lines->filter(fn ($line) => $line->account?->code === '48' && (float) $line->credit === 600.0)->count());
        $this->assertEquals(1600.00, (float) $entry->lines->sum('debit'));
        $this->assertEquals(1600.00, (float) $entry->lines->sum('credit'));
    }
    private function operator(string $role): Operator
    {
        $pin = str_pad((string) random_int(1, 99999999), 8, '0', STR_PAD_LEFT);

        return Operator::create([
            'name' => 'Operador Contabilidade',
            'pin' => $pin,
            'pin_fingerprint' => Operator::pinFingerprint($pin),
            'role' => $role,
            'active' => true,
        ]);
    }
}