<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\CommercialPriceRule;
use App\Models\Customer;
use App\Models\Operator;
use App\Models\Payments;
use App\Models\Product;
use App\Models\ProductStockBatch;
use App\Models\ProductWarehouseStock;
use App\Models\Purchase;
use App\Models\PurchaseAttachment;
use App\Models\PurchaseExpense;
use App\Models\PurchaseReturn;
use App\Models\Sale;
use App\Models\Shift;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Models\Supplier;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use App\Services\ModuleSettings;
use Tests\TestCase;

class OperationalFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_operator_can_open_shift_without_laravel_user_session(): void
    {
        $operator = $this->operator('Caixa Teste', 'cashier');

        $this->withSession(['operator_id' => $operator->id])
            ->postJson('/admin/shift/open', ['opening_cash' => 1500])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('already_open', false);

        $this->assertDatabaseHas('shifts', [
            'operator_id' => $operator->id,
            'user_id' => null,
            'status' => 'open',
        ]);
    }

    public function test_pos_checkout_records_sale_payment_and_stock_movement(): void
    {
        $operator = $this->operator('Operador POS', 'cashier');
        $product = $this->product(['stock_quantity' => 8, 'selling_price' => 250]);

        Shift::create([
            'operator_id' => $operator->id,
            'user_id' => null,
            'opening_cash' => 1000,
            'status' => 'open',
            'opened_at' => now(),
        ]);

        $this->withSession(['operator_id' => $operator->id])
            ->postJson('/admin/pos/checkout', [
                'items' => [
                    ['id' => $product->id, 'qty' => 2],
                ],
                'total' => 500,
                'payment_method' => 'cash',
                'amount_paid' => 500,
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('payment_status', 'paid');

        $product->refresh();

        $this->assertSame(6, (int) $product->stock_quantity);
        $this->assertSame(1, Sale::count());
        $this->assertSame(1, Payments::where('method', 'cash')->where('amount', 500)->count());
        $this->assertSame(1, StockMovement::where('product_id', $product->id)->where('type', 'OUT')->where('operator_id', $operator->id)->count());
    }

    public function test_purchase_can_be_approved_by_another_operator_and_received_into_stock(): void
    {
        $creator = $this->operator('Comprador', 'admin');
        $approver = $this->operator('Aprovador', 'manager');
        $supplier = Supplier::create(['company_name' => 'Fornecedor QA', 'status' => true]);
        $product = $this->product(['stock_quantity' => 3, 'purchase_price' => 100, 'selling_price' => 180]);

        $this->withSession(['operator_id' => $creator->id])
            ->post('/admin/purchases', [
                'supplier_id' => $supplier->id,
                'document_number' => 'QA-001',
                'purchase_date' => now()->toDateString(),
                'payment_type' => 'direct',
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 4, 'unit_cost' => 120, 'tax_rate' => 0],
                ],
            ])
            ->assertRedirect();

        $purchase = Purchase::firstOrFail();

        $this->withSession(['operator_id' => $approver->id])
            ->patch("/admin/purchases/{$purchase->id}/approve")
            ->assertRedirect();

        $purchase->refresh();
        $this->assertTrue($purchase->isApproved());
        $this->assertSame('paid', $purchase->payment_status);

        $this->withSession(['operator_id' => $approver->id])
            ->post("/admin/purchases/{$purchase->id}/receive", [
                'received' => [
                    $purchase->items()->firstOrFail()->id => 4,
                ],
            ])
            ->assertRedirect();

        $product->refresh();
        $purchase->refresh();

        $this->assertSame(Purchase::STATUS_RECEIVED, $purchase->status);
        $this->assertSame(7, (int) $product->stock_quantity);
        $this->assertSame(1, StockMovement::where('product_id', $product->id)->where('type', 'IN')->where('reference_type', 'purchase')->count());
    }


    public function test_professional_purchase_records_expenses_and_attachment(): void
    {
        Storage::fake('public');

        $creator = $this->operator('Comprador Profissional', 'admin');
        $supplier = Supplier::create(['company_name' => 'Fornecedor Pro', 'status' => true]);
        $product = $this->product(['purchase_price' => 100]);

        $this->withSession(['operator_id' => $creator->id])
            ->post('/admin/purchases', [
                'supplier_id' => $supplier->id,
                'document_type' => Purchase::TYPE_ORDER,
                'document_number' => 'DOC-PRO-001',
                'quotation_reference' => 'COT-44',
                'order_number' => 'OC-44',
                'supplier_invoice_number' => 'FT-FORN-44',
                'purchase_date' => now()->toDateString(),
                'payment_type' => 'direct',
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 2, 'unit_cost' => 100, 'tax_rate' => 0],
                ],
                'expenses' => [
                    ['description' => 'Transporte', 'category' => 'Logistica', 'amount' => 50],
                ],
                'attachments' => [UploadedFile::fake()->create('fatura.pdf', 32, 'application/pdf')],
            ])
            ->assertRedirect();

        $purchase = Purchase::firstOrFail();

        $this->assertSame(Purchase::TYPE_ORDER, $purchase->document_type);
        $this->assertSame('OC-44', $purchase->order_number);
        $this->assertSame(50.0, (float) $purchase->expenses_total);
        $this->assertSame(250.0, (float) $purchase->total);
        $this->assertSame(1, PurchaseExpense::where('purchase_id', $purchase->id)->count());
        $this->assertSame(1, PurchaseAttachment::where('purchase_id', $purchase->id)->count());
        Storage::disk('public')->assertExists(PurchaseAttachment::firstOrFail()->path);
    }

    public function test_purchase_return_to_supplier_decreases_stock(): void
    {
        $creator = $this->operator('Comprador Retorno', 'admin');
        $approver = $this->operator('Aprovador Retorno', 'manager');
        $supplier = Supplier::create(['company_name' => 'Fornecedor Retorno', 'status' => true]);
        $product = $this->product(['stock_quantity' => 5, 'purchase_price' => 100]);

        $this->withSession(['operator_id' => $creator->id])->post('/admin/purchases', [
            'supplier_id' => $supplier->id,
            'document_type' => Purchase::TYPE_PURCHASE,
            'document_number' => 'RET-001',
            'purchase_date' => now()->toDateString(),
            'payment_type' => 'direct',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 4, 'unit_cost' => 100, 'tax_rate' => 0],
            ],
        ])->assertRedirect();

        $purchase = Purchase::firstOrFail();
        $item = $purchase->items()->firstOrFail();

        $this->withSession(['operator_id' => $approver->id])->patch("/admin/purchases/{$purchase->id}/approve")->assertRedirect();
        $this->withSession(['operator_id' => $approver->id])->post("/admin/purchases/{$purchase->id}/receive", [
            'received' => [$item->id => 4],
        ])->assertRedirect();

        $this->withSession(['operator_id' => $approver->id])->post("/admin/purchases/{$purchase->id}/returns", [
            'return_date' => now()->toDateString(),
            'document_number' => 'DEV-001',
            'reason' => 'Produto danificado',
            'returned' => [$item->id => 2],
        ])->assertRedirect()->assertSessionHas('success');

        $product->refresh();
        $item->refresh();

        $this->assertSame(7, (int) $product->stock_quantity);
        $this->assertSame(2, (int) $item->returned_quantity);
        $this->assertSame(1, PurchaseReturn::where('purchase_id', $purchase->id)->count());
        $this->assertSame(1, StockMovement::where('product_id', $product->id)->where('type', 'OUT')->where('reference_type', 'purchase_return')->count());
    }

    public function test_stock_adjustment_records_batch_and_traceability(): void
    {
        $operator = $this->operator('Gestor Stock', 'admin');
        $product = $this->product(['stock_quantity' => 5]);

        $this->withSession(['operator_id' => $operator->id])
            ->post('/admin/stock/adjust', [
                'product_id' => $product->id,
                'mode' => 'in',
                'quantity' => 3,
                'reason' => 'Entrada lote QA',
                'lot_number' => 'LT-2026-A',
                'expires_at' => '2026-12-31',
                'serial_number' => 'SER-001',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $product->refresh();

        $this->assertSame(8, (int) $product->stock_quantity);
        $this->assertDatabaseHas('product_stock_batches', [
            'product_id' => $product->id,
            'lot_number' => 'LT-2026-A',
            'serial_number' => 'SER-001',
            'quantity' => 3,
        ]);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => 'IN',
            'lot_number' => 'LT-2026-A',
            'serial_number' => 'SER-001',
        ]);
    }

    public function test_warehouse_transfer_requires_approval_before_moving_stock(): void
    {
        ModuleSettings::update(array_merge(ModuleSettings::all(), ['stock_warehouses' => true]));

        $creator = $this->operator('Solicitante Armazem', 'admin');
        $approver = $this->operator('Aprovador Armazem', 'manager');
        $product = $this->product(['stock_quantity' => 10]);
        $source = Warehouse::create(['name' => 'Origem QA', 'code' => 'ORGQA', 'active' => true, 'is_default' => true]);
        $target = Warehouse::create(['name' => 'Destino QA', 'code' => 'DSTQA', 'active' => true, 'is_default' => false]);

        ProductWarehouseStock::create(['product_id' => $product->id, 'warehouse_id' => $source->id, 'quantity' => 10, 'minimum_stock' => 0]);
        ProductWarehouseStock::create(['product_id' => $product->id, 'warehouse_id' => $target->id, 'quantity' => 0, 'minimum_stock' => 0]);
        ProductStockBatch::create([
            'product_id' => $product->id,
            'warehouse_id' => $source->id,
            'lot_number' => 'LT-TRF',
            'expires_at' => '2026-11-30',
            'quantity' => 4,
            'operator_id' => $creator->id,
        ]);

        $this->withSession(['operator_id' => $creator->id])
            ->post('/admin/warehouses/transfer', [
                'from_warehouse_id' => $source->id,
                'to_warehouse_id' => $target->id,
                'product_id' => $product->id,
                'quantity' => 2,
                'lot_number' => 'LT-TRF',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $transfer = StockTransfer::firstOrFail();
        $this->assertSame('pending', $transfer->status);
        $this->assertSame(10, (int) ProductWarehouseStock::where('warehouse_id', $source->id)->where('product_id', $product->id)->value('quantity'));
        $this->assertSame(0, (int) ProductWarehouseStock::where('warehouse_id', $target->id)->where('product_id', $product->id)->value('quantity'));

        $this->withSession(['operator_id' => $approver->id])
            ->patch("/admin/warehouses/transfers/{$transfer->id}/approve")
            ->assertRedirect()
            ->assertSessionHas('success');

        $transfer->refresh();

        $this->assertSame('completed', $transfer->status);
        $this->assertSame(8, (int) ProductWarehouseStock::where('warehouse_id', $source->id)->where('product_id', $product->id)->value('quantity'));
        $this->assertSame(2, (int) ProductWarehouseStock::where('warehouse_id', $target->id)->where('product_id', $product->id)->value('quantity'));
        $this->assertSame(2, StockMovement::where('reference_type', 'stock_transfer')->where('reference_id', $transfer->id)->count());
    }
    public function test_commercial_proforma_uses_customer_price_rule_and_does_not_move_stock(): void
    {
        $operator = $this->operator('Comercial QA', 'admin');
        $customer = Customer::create([
            'name' => 'Cliente VIP QA',
            'discount_percent' => 10,
            'price_table' => 'VIP',
            'status' => true,
        ]);
        $product = $this->product(['stock_quantity' => 5, 'selling_price' => 100, 'tax_rate' => 0]);

        CommercialPriceRule::create([
            'name' => 'VIP Produto QA',
            'price_table' => 'VIP',
            'product_id' => $product->id,
            'unit_price' => 80,
            'active' => true,
        ]);

        $this->withSession(['operator_id' => $operator->id, 'operator_role' => 'admin'])
            ->postJson('/admin/sales', [
                'customer_id' => $customer->id,
                'items' => [['id' => $product->id, 'qty' => 1]],
                'payments' => ['cash' => 0, 'card' => 0, 'multi' => 0, 'transf' => 0],
                'total' => 68,
                'commercial_discount' => 5,
                'is_proforma' => true,
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $sale = Sale::latest()->firstOrFail();
        $product->refresh();

        $this->assertTrue((bool) $sale->is_proforma);
        $this->assertSame('PROFORMA', $sale->document_type_code);
        $this->assertSame(68.0, (float) $sale->total);
        $this->assertSame(5, (int) $product->stock_quantity);
        $this->assertSame(0, StockMovement::where('reference_type', 'sale')->where('reference_id', $sale->id)->count());
    }
    private function operator(string $name, string $role): Operator
    {
        $pin = str_pad((string) random_int(1, 99999999), 8, '0', STR_PAD_LEFT);

        return Operator::create([
            'name' => $name,
            'pin' => $pin,
            'pin_fingerprint' => Operator::pinFingerprint($pin),
            'role' => $role,
            'active' => true,
        ]);
    }

    private function product(array $attributes = []): Product
    {
        $category = Category::create([
            'name' => 'Categoria QA',
            'status' => true,
        ]);

        return Product::create(array_merge([
            'category_id' => $category->id,
            'name' => 'Produto QA ' . uniqid(),
            'barcode' => uniqid('qa'),
            'purchase_price' => 100,
            'selling_price' => 200,
            'tax_rate' => 0,
            'stock_quantity' => 10,
            'minimum_stock' => 1,
            'unit' => 'un',
            'status' => true,
            'track_stock' => true,
            'available_restaurant' => true,
            'available_supermarket' => true,
        ], $attributes));
    }
}