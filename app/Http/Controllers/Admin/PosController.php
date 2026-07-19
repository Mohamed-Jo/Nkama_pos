<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\EnviarDocumentoAGTJob;
use App\Jobs\SolicitarSerieAGTJob;
use App\Models\Category;
use App\Models\CurrentAccountEntry;
use App\Models\Customer;
use App\Models\Operator;
use App\Models\Payments;
use App\Models\Product;
use App\Models\RestaurantTable;
use App\Models\Sale;
use App\Models\Shift;
use App\Models\StockMovement;
use App\Services\AuditLogger;
use App\Services\AGTElectronicInvoiceService;
use App\Services\BusinessSettings;
use App\Services\CustomerCardAuthorizationService;
use App\Services\CustomerCardOtpService;
use App\Services\CustomerCardService;
use App\Services\OperatorPermissions;
use App\Services\DocumentNumbering;
use App\Services\ModuleSettings;
use App\Services\StockWarehouseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class PosController extends Controller
{
    public function index()
    {
        $modules = ModuleSettings::all();
        $operatorId = session('operator_id');
        $todaySalesQuery = Sale::where('status', 'paid')
            ->whereDate('created_at', today());

        if ($operatorId) {
            $todaySalesQuery->where('operator_id', $operatorId);
        } elseif (auth()->id()) {
            $todaySalesQuery->where('user_id', auth()->id());
        }

        $todaySales = (float) $todaySalesQuery->sum('total');

        $todayPaymentsQuery = Payments::whereDate('created_at', today());
        if ($operatorId) {
            $todayPaymentsQuery->where('operator_id', $operatorId);
        } elseif (auth()->id()) {
            $todayPaymentsQuery->where('user_id', auth()->id());
        }

        $todayPaymentsByMethod = (clone $todayPaymentsQuery)
            ->selectRaw('method, SUM(amount) as total')
            ->groupBy('method')
            ->pluck('total', 'method');
        $todayCashSales = (float) ($todayPaymentsByMethod['cash'] ?? 0);
        $todayCardSales = (float) collect(['card', 'multi'])
            ->sum(fn ($method) => (float) ($todayPaymentsByMethod[$method] ?? 0));

        $stockWarehouseService = app(StockWarehouseService::class);
        $products = Product::with('category')
            ->where('status', true)
            ->where('available_supermarket', true)
            ->orderBy('category_id')
            ->orderBy('name')
            ->get();
        $stockWarehouseService->attachQuantities($products, 'supermarket');

        $restaurantCategories = Category::where('status', true)
            ->whereHas('products', function ($query) {
                $query->where('status', true)->where('available_restaurant', true);
            })
            ->with(['products' => function ($query) {
                $query->where('status', true)
                    ->where('available_restaurant', true)
                    ->orderBy('name');
            }])
            ->orderBy('name')
            ->get();
        $restaurantCategories->each(fn ($category) => $stockWarehouseService->attachQuantities($category->products, 'restaurant'));

        return view('admin.pos.index', [
            'modules' => $modules,
            'products' => $products,
            'restaurantCategories' => $restaurantCategories,
            'customers' => Customer::with('card')->where('status', true)->orderBy('name')->get(),
            'operatorName' => Operator::find(session('operator_id'))?->name ?? 'Operador',
            'todaySales' => $todaySales,
            'todayCashSales' => $todayCashSales,
            'todayCardSales' => $todayCardSales,
            'shift' => Shift::where('operator_id', session('operator_id'))->where('status', 'open')->first(),
            'tables' => RestaurantTable::with('currentOrder')
                ->where(function ($query) use ($operatorId) {
                    $query->whereNull('current_order_id')
                        ->orWhereDoesntHave('currentOrder')
                        ->orWhereHas('currentOrder', function ($orderQuery) use ($operatorId) {
                            $orderQuery->whereIn('status', ['closed', 'cancelled', 'canceled', 'transferred'])
                                ->orWhere('operator_id', $operatorId);
                        });
                })
                ->orderByRaw('LENGTH(name), name')
                ->get(),
        ]);
    }

    public function checkout(Request $request)
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'total' => 'required|numeric|min:0',
            'payment_method' => 'nullable|string',
            'amount_paid' => 'nullable|numeric|min:0',
            'payment_breakdown' => 'nullable|array',
            'table_id' => 'nullable|integer',
            'split_bill' => 'nullable|boolean',
            'customer_id' => 'nullable|exists:customers,id',
            'customer_card_number' => 'nullable|string|max:80',
            'customer_card_otp' => 'nullable|string|max:12',
            'customer_card_authorization_token' => 'nullable|string|max:120',
            'customer_card_authorization_id' => 'nullable|integer',
            'customer_card_offline_emergency' => 'nullable|boolean',
            'supervisor_pin' => 'nullable|string|digits:8',
            'supervisor_reason' => 'nullable|string|max:180',
        ]);

        $isRestaurantSale = $request->filled('table_id');

        if ($isRestaurantSale && !ModuleSettings::enabled('restaurant')) {
            return response()->json([
                'success' => false,
                'message' => 'Modulo Restaurante desativado pelo super-user.',
            ], 403);
        }

        if (!$isRestaurantSale && !ModuleSettings::enabled('supermarket')) {
            return response()->json([
                'success' => false,
                'message' => 'Modulo Supermercado desativado pelo super-user.',
            ], 403);
        }

        try {
            $sale = DB::transaction(function () use ($request) {
                $operatorId = session('operator_id');
                $operator = $operatorId ? Operator::find($operatorId) : null;

                if (!$operator) {
                    throw new \Exception('Operador invalido ou sessao expirada');
                }

                if ($request->filled('table_id')) {
                    $table = RestaurantTable::with('currentOrder')->lockForUpdate()->findOrFail((int) $request->input('table_id'));
                    if ($this->tableHasForeignActiveOrder($table, (int) $operator->id)) {
                        throw new \Exception('Esta mesa esta ocupada por outro operador.');
                    }
                }
                $shift = $operatorId
                    ? Shift::where('operator_id', $operatorId)->where('status', 'open')->lockForUpdate()->first()
                    : null;

                if (!$shift) {
                    throw new \Exception('Abra o caixa antes de vender');
                }

                $paymentMethod = $request->input('payment_method', 'cash');
                $stockOperation = $request->filled('table_id') ? 'restaurant' : 'supermarket';
                $calculated = $this->calculateSaleItems($request->items, $stockOperation);
                $total = $calculated['total'];
                $breakdown = $this->normalizePaymentBreakdown($paymentMethod, $request, $total);
                $paid = round(array_sum($breakdown), 2);
                $cashPaid = round((float) ($breakdown['cash'] ?? 0), 2);
                $change = round(min(max($paid - $total, 0), max($cashPaid, 0)), 2);
                $outstanding = round(max($total - $paid, 0), 2);
                $customerId = $request->integer('customer_id') ?: null;
                $customerCard = null;
                $customerCardOtp = null;
                $customerCardAuthorization = null;
                $customerCardEmergencySupervisor = null;

                $customerCardPayment = round((float) ($breakdown['customer_card'] ?? 0), 2);

                if ((ModuleSettings::enabled('customer_card') && $request->filled('customer_card_number')) || $paymentMethod === 'customer_card' || $customerCardPayment > 0) {
                    if (!ModuleSettings::enabled('customer_card')) {
                        throw new \Exception('Modulo Cartao Cliente desativado pelo super-user.');
                    }

                    if (!$request->filled('customer_card_number')) {
                        throw new \Exception('Leia ou informe o cartao cliente para pagar por fidelidade.');
                    }

                    $customerCard = app(CustomerCardService::class)->lookup((string) $request->input('customer_card_number'));

                    if (!$customerCard || !app(CustomerCardService::class)->isUsable($customerCard)) {
                        throw new \Exception('Cartao cliente nao encontrado, inativo ou expirado.');
                    }

                    $customerId = $customerCard->customer_id;

                    if ($paymentMethod === 'customer_card' || $customerCardPayment > 0) {
                        $capacity = app(CustomerCardService::class)->paymentCapacity($customerCard);
                        $requiredCardAmount = $paymentMethod === 'customer_card' ? $total : $customerCardPayment;

                        if ($capacity['total_available'] + 0.0001 < $requiredCardAmount) {
                            throw new \Exception('Bonus e saldo do cartao cliente insuficientes para esta compra.');
                        }

                        if ($requiredCardAmount > 0) {
                            if ($request->boolean('customer_card_offline_emergency')) {
                                if ($requiredCardAmount > self::CUSTOMER_CARD_OFFLINE_LIMIT) {
                                    throw new \Exception('Uso emergencial offline limitado a ' . number_format(self::CUSTOMER_CARD_OFFLINE_LIMIT, 2, ',', '.') . ' Kz por venda.');
                                }

                                $customerCardEmergencySupervisor = $this->authorizeCustomerCardEmergency($request);
                            } elseif ($request->filled('customer_card_authorization_id')) {
                                $customerCardAuthorization = app(CustomerCardAuthorizationService::class)->verifyApproved(
                                    $customerCard,
                                    (int) $request->input('customer_card_authorization_id'),
                                    $requiredCardAmount,
                                    $operator?->id
                                );
                            } elseif (ModuleSettings::enabled('customer_card_otp')) {
                                if (!$request->filled('customer_card_otp')) {
                                    throw new \Exception('Informe o OTP enviado ao cliente ou solicite autorizacao do gestor para usar Fidelidade.');
                                }

                                $customerCardOtp = app(CustomerCardOtpService::class)->verify(
                                    $customerCard,
                                    (string) $request->input('customer_card_otp'),
                                    $requiredCardAmount,
                                    $operator?->id
                                );
                            }
                        }
                    }
                }

                if ($paymentMethod === 'customer_card' && $paid < $total) {
                    throw new \Exception('Pagamento por Cartao Cliente/Fidelidade deve cobrir o total da venda.');
                }

                if ($outstanding > 0 && !ModuleSettings::enabled('current_account')) {
                    throw new \Exception('Modulo Conta Corrente desativado pelo super-user.');
                }

                if ($outstanding > 0 && !$customerId) {
                    throw new \Exception('Pagamento insuficiente');
                }

                if ($paymentMethod === 'credit' && !$customerId) {
                    throw new \Exception('Selecione o cliente para vender em conta corrente');
                }

                $paymentStatus = $outstanding > 0
                    ? ($paid > 0 ? 'partial' : 'unpaid')
                    : 'paid';

                $documentType = $outstanding > 0 ? 'FT' : 'FR';
                $document = DocumentNumbering::next($documentType);

                $sale = Sale::create($this->salePayload([
                    'invoice_number' => $document['invoice_number'],
                    'document_type_code' => $document['document_type_code'],
                    'document_series_id' => $document['document_series_id'],
                    'document_number' => $document['document_number'],
                    'customer_id' => $customerId,
                    'customer_card_id' => $customerCard?->id,
                    'operator_id' => $operator?->id,
                    'shift_id' => $shift?->id,
                    'user_id' => auth()->id(),
                    'subtotal' => $calculated['subtotal'],
                    'discount' => 0,
                    'tax' => $calculated['tax'],
                    'tax_rate' => $calculated['tax_rate'],
                    'total' => $calculated['total'],
                    'paid' => $paid,
                    'change' => $change,
                    'payment_method' => $outstanding > 0 && $paid > 0 ? 'mixed_credit' : ($paymentMethod === 'multi' ? 'mixed' : $paymentMethod),
                    'payment_status' => $paymentStatus,
                    'status' => $paymentStatus,
                ]));

                foreach ($calculated['items'] as $item) {
                    $product = $item['product'];
                    $stockBefore = Schema::hasColumn('products', 'stock_quantity')
                        ? (float) $product->stock_quantity
                        : null;

                    $sale->items()->create([
                        'product_id' => $product->id,
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'subtotal' => $item['subtotal'],
                        'net_subtotal' => $item['net_subtotal'],
                        'tax_rate' => $item['tax_rate'],
                        'tax_amount' => $item['tax_amount'],
                    ]);                    if (Schema::hasColumn('products', 'stock_quantity') && ($product->track_stock ?? true)) {
                        [$stockBefore, $stockAfter] = app(StockWarehouseService::class)->decrease($product, (int) $item['quantity'], $stockOperation);
                        $movementWarehouseId = app(StockWarehouseService::class)->warehouseIdFor($stockOperation);

                        StockMovement::create([
                            'product_id' => $product->id,
                            'warehouse_id' => $movementWarehouseId,
                            'type' => 'OUT',
                            'reason' => 'Venda POS',
                            'quantity' => $item['quantity'],
                            'stock_before' => $stockBefore,
                            'stock_after' => $stockAfter,
                            'notes' => 'Venda ' . $sale->invoice_number,
                            'reference_type' => 'sale',
                            'reference_id' => $sale->id,
                            'operator_id' => $operator?->id,
                        ]);
                    }
                }

                $paymentAmounts = $this->netPaymentAmounts($breakdown, $change);

                foreach ($paymentAmounts as $method => $amount) {
                    if ($amount <= 0) {
                        continue;
                    }

                    Payments::create($this->paymentPayload([
                        'sale_id' => $sale->id,
                        'shift_id' => $shift?->id,
                        'operator_id' => $operator?->id,
                        'method' => $method,
                        'payment_method' => $method,
                        'amount' => $amount,
                    ]));
                }

                if ($outstanding > 0) {
                    CurrentAccountEntry::create([
                        'entity_type' => 'customer',
                        'entity_id' => $customerId,
                        'entry_date' => now()->toDateString(),
                        'movement_type' => 'debit',
                        'debit' => $outstanding,
                        'credit' => 0,
                        'document_type' => 'sale',
                        'document_id' => $sale->id,
                        'description' => 'Venda a credito ' . $sale->invoice_number,
                        'operator_id' => $operator?->id,
                    ]);
                }

                if ((float) ($paymentAmounts['customer_card'] ?? 0) > 0 && $customerCard) {
                    app(CustomerCardService::class)->paySale($customerCard, $sale, (float) $paymentAmounts['customer_card']);

                    if ($customerCardOtp) {
                        app(CustomerCardOtpService::class)->markUsed($customerCardOtp, $sale);
                    }

                    if ($customerCardAuthorization) {
                        app(CustomerCardAuthorizationService::class)->markUsed($customerCardAuthorization, $sale);
                    }

                    if ($customerCardEmergencySupervisor) {
                        AuditLogger::log('customer_card_emergency_payment', 'CustomerCard', $customerCard->id, [
                            'sale_id' => $sale->id,
                            'invoice_number' => $sale->invoice_number,
                            'card_number' => $customerCard->card_number,
                            'customer_id' => $customerCard->customer_id,
                            'amount' => (float) ($paymentAmounts['customer_card'] ?? 0),
                            'operator_id' => $operator?->id,
                            'supervisor_id' => $customerCardEmergencySupervisor->id,
                            'supervisor_name' => $customerCardEmergencySupervisor->name,
                            'reason' => $request->input('supervisor_reason'),
                        ]);
                    }
                }

                app(CustomerCardService::class)->earnFromSale($sale);

                $this->applyRestaurantSplitPayment($request, $operator);


                return $sale;
            });

            if ($sale->document_series_id) {
                SolicitarSerieAGTJob::dispatch((int) $sale->document_series_id)->afterResponse();
            }

            $agtDocument = $this->registerAgtSale($sale);

            return response()->json([
                'success' => true,
                'sale_id' => $sale->id,
                'invoice' => $sale->invoice_number,
                'payment_method' => $sale->payment_method,
                'payment_status' => $sale->payment_status,
                'outstanding' => round(max((float) $sale->total - (float) $sale->paid, 0), 2),
                'message' => 'Fatura emitida com sucesso',
                'agt_status' => $agtDocument?->status,
                'agt_status_label' => $agtDocument?->status_label,
                'agt_message' => $agtDocument?->validation_message,
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    private function registerAgtSale(Sale $sale): ?\App\Models\AgtDocument
    {
        try {
            $service = app(AGTElectronicInvoiceService::class);
            $document = $service->prepareSale($sale);

            EnviarDocumentoAGTJob::dispatch((int) $document->id)->afterResponse();

            return $document;
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }
    public function findProductByBarcode(Request $request)
    {
        if (!ModuleSettings::enabled('supermarket')) {
            return response()->json(['success' => false, 'message' => 'Modulo supermercado desativado.'], 403);
        }

        $product = Product::where('barcode', $request->barcode)->where('status', true)->first();

        if (!$product) {
            return response()->json(['success' => false, 'message' => 'Produto nao encontrado no supermercado.'], 404);
        }

        if (($product->track_stock ?? true) && ! app(StockWarehouseService::class)->available($product, 1, 'supermarket')) {
            return response()->json(['success' => false, 'message' => 'Produto sem stock disponivel no armazem do supermercado.'], 422);
        }

        $product->setAttribute('operation_stock_quantity', app(StockWarehouseService::class)->quantityFor($product, 'supermarket'));

        return response()->json(['success' => true, 'data' => $product]);
    }

    private function authorizeCustomerCardEmergency(Request $request): Operator
    {
        if (!$request->filled('supervisor_pin')) {
            throw new \Exception('PIN de supervisor obrigatorio para uso emergencial offline de Fidelidade.');
        }

        $pinFingerprint = Operator::pinFingerprint((string) $request->input('supervisor_pin'));
        $supervisor = Operator::where('active', true)->where('pin_fingerprint', $pinFingerprint)->first();

        if (!$supervisor) {
            $supervisor = Operator::where('active', true)
                ->whereNull('pin_fingerprint')
                ->get()
                ->first(fn (Operator $operator) => Hash::check((string) $request->input('supervisor_pin'), $operator->pin));

            if ($supervisor && !Operator::where('pin_fingerprint', $pinFingerprint)->whereKeyNot($supervisor->id)->exists()) {
                $supervisor->forceFill(['pin_fingerprint' => $pinFingerprint])->save();
            }
        }

        if (!$supervisor || !OperatorPermissions::allowsAny($supervisor->role, ['security.manage', 'cash.audit', 'management.view'])) {
            AuditLogger::log('customer_card_emergency_authorization_failed', 'CustomerCard', null, [
                'operator_id' => session('operator_id'),
                'operator_name' => session('operator_name'),
                'matched_operator_id' => $supervisor?->id,
                'matched_operator_role' => $supervisor?->role,
                'reason' => $supervisor ? 'sem permissao de supervisor' : 'pin invalido',
            ]);

            throw new \Exception('PIN de supervisor invalido ou sem permissao para uso emergencial offline.');
        }

        return $supervisor;
    }

    private function applyRestaurantSplitPayment(Request $request, Operator $operator): void
    {
        if (!$request->boolean('split_bill') || !$request->filled('table_id')) {
            return;
        }

        $table = RestaurantTable::with('currentOrder.items')->lockForUpdate()->findOrFail((int) $request->input('table_id'));

        if ($this->tableHasForeignActiveOrder($table, (int) $operator->id)) {
            throw new \Exception('Esta mesa esta ocupada por outro operador.');
        }

        $order = $table->currentOrder;

        if (!$order || in_array($order->status, ['closed', 'cancelled', 'canceled', 'transferred'], true)) {
            throw new \Exception('A mesa ja nao tem conta aberta.');
        }

        $requestedItems = collect($request->input('items', []))
            ->mapWithKeys(function ($item) {
                $productId = (int) ($item['id'] ?? $item['product_id'] ?? 0);
                $qty = (int) max(1, (float) ($item['qty'] ?? $item['quantity'] ?? 1));

                return $productId > 0 ? [$productId => $qty] : [];
            });

        if ($requestedItems->isEmpty()) {
            throw new \Exception('Selecione pelo menos um item para dividir a conta.');
        }

        foreach ($requestedItems as $productId => $qtyPaid) {
            $orderItem = $order->items()->where('product_id', $productId)->lockForUpdate()->first();

            if (!$orderItem) {
                throw new \Exception('Um dos itens pagos ja nao existe nesta mesa.');
            }

            $currentQty = (int) $orderItem->qty;

            if ($qtyPaid > $currentQty) {
                throw new \Exception('Quantidade paga maior do que a quantidade aberta na mesa.');
            }

            if ($qtyPaid === $currentQty) {
                $orderItem->delete();
                continue;
            }

            $remainingQty = $currentQty - $qtyPaid;
            $orderItem->update([
                'qty' => $remainingQty,
                'subtotal' => round($remainingQty * (float) $orderItem->price, 2),
            ]);
        }

        $remainingTotal = round((float) $order->items()->sum('subtotal'), 2);

        if ($remainingTotal <= 0 || !$order->items()->exists()) {
            $order->update([
                'subtotal' => 0,
                'total' => 0,
                'status' => 'closed',
            ]);

            $table->update([
                'status' => 'free',
                'current_order_id' => null,
            ]);

            return;
        }

        $order->update([
            'subtotal' => $remainingTotal,
            'total' => $remainingTotal,
            'status' => 'open',
        ]);

        $table->update([
            'status' => 'occupied',
            'current_order_id' => $order->id,
        ]);
    }

    private function tableHasForeignActiveOrder(RestaurantTable $table, int $operatorId): bool
    {
        $order = $table->currentOrder;

        if (!$order || in_array($order->status, ['closed', 'cancelled', 'canceled', 'transferred'], true)) {
            return false;
        }

        return (int) $order->operator_id !== $operatorId;
    }
    private function normalizePaymentBreakdown(string $paymentMethod, Request $request, float $total): array
    {
        if ($paymentMethod === 'customer_card') {
            return [
                'customer_card' => round((float) $request->input('amount_paid', $total), 2),
            ];
        }

        if ($paymentMethod === 'credit') {
            return [
                'credit' => 0,
            ];
        }

        if ($paymentMethod === 'multi') {
            $breakdown = $request->input('payment_breakdown', []);

            return [
                'cash' => round((float) ($breakdown['cash'] ?? 0), 2),
                'card' => round((float) ($breakdown['card'] ?? 0), 2),
                'transf' => round((float) ($breakdown['transfer'] ?? $breakdown['transf'] ?? 0), 2),
                'customer_card' => round((float) ($breakdown['customer_card'] ?? 0), 2),
            ];
        }

        return [
            $paymentMethod => round((float) $request->input('amount_paid', $total), 2),
        ];
    }

    private function calculateSaleItems(array $items, string $stockOperation): array
    {
        $calculatedItems = [];
        $grossTotal = 0.0;
        $netTotal = 0.0;
        $taxTotal = 0.0;
        $rates = [];

        foreach ($items as $item) {
            $product = Product::lockForUpdate()->findOrFail($item['id']);
            $qty = (int) max(1, (float) ($item['qty'] ?? 1));

            if (Schema::hasColumn('products', 'stock_quantity') && ! app(StockWarehouseService::class)->available($product, $qty, $stockOperation)) {
                throw new \Exception("Stock insuficiente {$product->name}");
            }

            $price = round((float) $product->selling_price, 2);
            $gross = round($qty * $price, 2);
            $taxRate = round((float) ($product->tax_rate ?? 0), 2);
            $split = BusinessSettings::splitGrossTotal($gross, $taxRate);

            $grossTotal += $split['total'];
            $netTotal += $split['subtotal'];
            $taxTotal += $split['tax'];
            $rates[(string) $taxRate] = true;

            $calculatedItems[] = [
                'product' => $product,
                'quantity' => $qty,
                'unit_price' => $price,
                'subtotal' => $split['total'],
                'net_subtotal' => $split['subtotal'],
                'tax_rate' => $taxRate,
                'tax_amount' => $split['tax'],
            ];
        }

        return [
            'items' => $calculatedItems,
            'subtotal' => round($netTotal, 2),
            'tax' => round($taxTotal, 2),
            'tax_rate' => count($rates) === 1 ? (float) array_key_first($rates) : 0.0,
            'total' => round($grossTotal, 2),
        ];
    }

    private function netPaymentAmounts(array $breakdown, float $change): array
    {
        $amounts = $breakdown;

        if ($change > 0 && isset($amounts['cash'])) {
            $amounts['cash'] = round(max(0, (float) $amounts['cash'] - $change), 2);
        }

        return $amounts;
    }

    private function salePayload(array $values): array
    {
        return collect($values)
            ->filter(fn ($value, $column) => $value !== null && Schema::hasColumn('sales', $column))
            ->all();
    }

    private function paymentPayload(array $values): array
    {
        return collect($values)
            ->filter(fn ($value, $column) => $value !== null && Schema::hasColumn('payments', $column))
            ->all();
    }
}
