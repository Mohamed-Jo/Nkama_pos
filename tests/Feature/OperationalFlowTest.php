<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Operator;
use App\Models\Payments;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Shift;
use App\Models\StockMovement;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
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