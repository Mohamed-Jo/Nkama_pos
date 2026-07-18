<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{Category, Customer, Operator, Product, RestaurantOrder, RestaurantOrderItem, RestaurantTable, Shift};
use App\Services\BusinessSettings;
use App\Services\ModuleSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RestaurantController extends Controller
{
    public function index()
    {
        $modules = ModuleSettings::all();

        return view('admin.pos.index', [
            'modules' => $modules,
            'products' => Product::with('category')
                ->where('status', true)
                ->where('available_supermarket', true)
                ->orderBy('category_id')
                ->orderBy('name')
                ->get(),
            'restaurantCategories' => Category::where('status', true)
                ->whereHas('products', function ($query) {
                    $query->where('status', true)->where('available_restaurant', true);
                })
                ->with(['products' => function ($query) {
                    $query->where('status', true)
                        ->where('available_restaurant', true)
                        ->orderBy('name');
                }])
                ->orderBy('name')
                ->get(),
            'customers' => Customer::where('status', true)->get(),
            'operatorName' => Operator::find(session('operator_id'))?->name ?? 'Operador',
            'shift' => Shift::where('operator_id', session('operator_id'))->where('status', 'open')->first(),
            'tables' => RestaurantTable::with('currentOrder')
                ->where(function ($query) {
                    $operatorId = session('operator_id');
                    $query->whereNull('current_order_id')
                        ->orWhereDoesntHave('currentOrder')
                        ->orWhereHas('currentOrder', function ($orderQuery) use ($operatorId) {
                            $orderQuery->whereIn('status', ['closed', 'cancelled', 'canceled', 'transferred'])
                                ->when($operatorId, fn ($query) => $query->orWhere('operator_id', $operatorId));
                        });
                })
                ->orderByRaw('LENGTH(name), name')
                ->get(),
        ]);
    }

    public function openTable($tableId)
    {
        try {
            $table = RestaurantTable::findOrFail($tableId);
            $operatorId = session('operator_id');
            $order = $table->current_order_id
                ? RestaurantOrder::with('items.product')->find($table->current_order_id)
                : null;

            if ($this->isForeignActiveOrder($order)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Esta mesa esta ocupada por outro operador.',
                ], 403);
            }

            if (!$order || in_array($order->status, ['closed', 'cancelled', 'canceled'], true)) {
                $order = RestaurantOrder::create([
                    'table_id' => $table->id,
                    'operator_id' => $operatorId,
                    'status' => 'open',
                    'subtotal' => 0,
                    'total' => 0
                ]);

                $table->update([
                    'status' => 'free',
                    'current_order_id' => $order->id
                ]);

                $table->current_order_id = $order->id;
                $table->status = 'free';
            }

            $order = $order->load('items.product');
            $hasItems = $order->items->isNotEmpty();

            if ($hasItems && $table->status !== 'occupied') {
                $table->update(['status' => 'occupied']);
                $table->status = 'occupied';
            }

            $tableStatus = $hasItems ? 'occupied' : ($table->status === 'reserved' ? 'reserved' : 'free');

            return response()->json([
                'success' => true,
                'table_status' => $tableStatus,
                'order' => $order,
                'items' => $order ? $order->items : []
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao carregar mesa: ' . $e->getMessage()
            ], 500);
        }
    }

    public function reserveTable($tableId): JsonResponse
    {
        try {
            $operatorId = session('operator_id');
            $table = RestaurantTable::with('currentOrder.items')->lockForUpdate()->findOrFail($tableId);
            $order = $table->currentOrder;

            if ($this->isForeignActiveOrder($order)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Esta mesa esta ocupada por outro operador.',
                ], 403);
            }

            if ($order && $order->items->isNotEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mesa com consumo nao pode ser marcada como reserva.',
                ], 422);
            }

            if (!$order || in_array($order->status, ['closed', 'cancelled', 'canceled', 'transferred'], true)) {
                $order = RestaurantOrder::create([
                    'table_id' => $table->id,
                    'operator_id' => $operatorId,
                    'status' => 'open',
                    'subtotal' => 0,
                    'total' => 0,
                    'notes' => 'Reserva de mesa',
                ]);
            }

            $table->update([
                'status' => 'reserved',
                'current_order_id' => $order->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Mesa reservada com sucesso.',
                'table_status' => 'reserved',
                'order' => $order->fresh('items.product'),
                'items' => [],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao reservar mesa: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function releaseReservation($tableId): JsonResponse
    {
        try {
            $table = RestaurantTable::with('currentOrder.items')->lockForUpdate()->findOrFail($tableId);
            $order = $table->currentOrder;

            if ($this->isForeignActiveOrder($order)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Esta mesa esta ocupada por outro operador.',
                ], 403);
            }

            if ($order && $order->items->isNotEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mesa com consumo nao pode libertar reserva.',
                ], 422);
            }

            if ($order) {
                $order->update([
                    'status' => 'canceled',
                    'subtotal' => 0,
                    'total' => 0,
                ]);
            }

            $table->update([
                'status' => 'free',
                'current_order_id' => null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Reserva libertada com sucesso.',
                'table_status' => 'free',
                'order_id' => null,
                'items' => [],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao libertar reserva: ' . $e->getMessage(),
            ], 500);
        }
    }
    public function closeOrder($tableId)
    {
        try {
            $table = RestaurantTable::findOrFail($tableId);

            if ($table->current_order_id) {
                $order = RestaurantOrder::find($table->current_order_id);
                if ($this->isForeignActiveOrder($order)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Esta mesa esta ocupada por outro operador.',
                    ], 403);
                }
                if ($order) {
                    $order->update([
                        'status' => 'closed',
                        'closed_at' => now()
                    ]);
                }
            }

            $table->update([
                'status' => 'free',
                'current_order_id' => null
            ]);

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao fechar mesa: ' . $e->getMessage()
            ], 500);
        }
    }

    public function tableSummary($tableId): JsonResponse
    {
        try {
            $table = RestaurantTable::with(['currentOrder.items.product'])->findOrFail($tableId);
            $order = $table->currentOrder;

            if ($this->isForeignActiveOrder($order)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Esta mesa esta ocupada por outro operador.',
                ], 403);
            }

            if (!$order || in_array($order->status, ['closed', 'cancelled', 'canceled'], true)) {
                return response()->json([
                    'success' => true,
                    'table' => [
                        'id' => $table->id,
                        'name' => $table->name,
                        'status' => 'free',
                    ],
                    'order_id' => null,
                    'items' => [],
                    'subtotal' => 0,
                    'total' => 0,
                ]);
            }

            $items = $order->items->map(function ($item) {
                return [
                    'product_id' => $item->product_id,
                    'name' => $item->product?->name ?? 'Produto Desconhecido',
                    'qty' => (int) $item->qty,
                    'price' => (float) $item->price,
                    'taxRate' => (float) ($item->product?->tax_rate ?? 0),
                    'subtotal' => (float) $item->subtotal,
                ];
            });

            return response()->json([
                'success' => true,
                'table' => [
                    'id' => $table->id,
                    'name' => $table->name,
                    'status' => $table->status,
                ],
                'order_id' => $order->id,
                'items' => $items,
                'subtotal' => (float) $order->subtotal,
                'total' => (float) $order->total,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao consultar mesa: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function tableTicket($tableId)
    {
        $table = RestaurantTable::with(['currentOrder.items.product'])->findOrFail($tableId);
        $order = $table->currentOrder;

        if ($this->isForeignActiveOrder($order)) {
            abort(403, 'Esta mesa esta ocupada por outro operador.');
        }

        if (!$order || in_array($order->status, ['closed', 'cancelled', 'canceled'], true)) {
            abort(404, 'Mesa sem conta aberta.');
        }

        $company = BusinessSettings::company();
        $logoUrl = BusinessSettings::logoUrl($company);
        $printSettings = BusinessSettings::print();

        $totals = $this->restaurantOrderTotals($order);

        return view('admin.restaurant.table-ticket', compact('table', 'order', 'company', 'logoUrl', 'totals', 'printSettings'));
    }

    public function transferOrder(Request $request): JsonResponse
    {
        $request->validate([
            'from_table_id' => 'required|exists:restaurant_tables,id',
            'to_table_id' => 'required|exists:restaurant_tables,id|different:from_table_id',
            'items' => 'nullable|array',
            'items.*.product_id' => 'required_with:items|integer|exists:products,id',
            'items.*.quantity' => 'required_with:items|integer|min:1',
        ]);

        try {
            $result = DB::transaction(function () use ($request) {
                $fromTable = RestaurantTable::lockForUpdate()->findOrFail($request->from_table_id);
                $toTable = RestaurantTable::lockForUpdate()->findOrFail($request->to_table_id);

                if (!$fromTable->current_order_id) {
                    throw new \Exception('A mesa de origem nao tem conta aberta.');
                }

                if ($toTable->current_order_id || in_array($toTable->status, ['occupied', 'waiting_payment'], true)) {
                    throw new \Exception('A mesa de destino precisa estar livre.');
                }

                $order = RestaurantOrder::with('items.product')->lockForUpdate()->findOrFail($fromTable->current_order_id);
                $this->assertOrderBelongsToCurrentOperator($order);

                if ($order->items->isEmpty()) {
                    throw new \Exception('A conta de origem nao tem itens para transferir.');
                }

                $selectedItems = collect($request->input('items', []))
                    ->filter(fn ($item) => (int) ($item['quantity'] ?? 0) > 0)
                    ->keyBy(fn ($item) => (int) $item['product_id']);

                if ($selectedItems->isEmpty()) {
                    $order->update(['table_id' => $toTable->id]);

                    $fromTable->update([
                        'status' => 'free',
                        'current_order_id' => null,
                    ]);

                    $toTable->update([
                        'status' => 'occupied',
                        'current_order_id' => $order->id,
                    ]);

                    return [
                        'order_id' => $order->id,
                        'from_order_id' => null,
                        'from_table' => $fromTable->name,
                        'to_table' => $toTable->name,
                        'partial' => false,
                    ];
                }

                $destinationOrder = RestaurantOrder::create([
                    'table_id' => $toTable->id,
                    'operator_id' => $order->operator_id,
                    'status' => 'open',
                    'subtotal' => 0,
                    'total' => 0,
                    'notes' => 'Transferencia parcial da mesa ' . $fromTable->name,
                ]);

                foreach ($selectedItems as $selected) {
                    $sourceItem = $order->items
                        ->firstWhere('product_id', (int) $selected['product_id']);

                    if (!$sourceItem) {
                        throw new \Exception('Um dos itens selecionados ja nao existe na mesa de origem.');
                    }

                    $qtyToMove = (int) $selected['quantity'];
                    $sourceQty = (int) $sourceItem->qty;

                    if ($qtyToMove > $sourceQty) {
                        throw new \Exception('Quantidade maior do que a disponivel para ' . ($sourceItem->product->name ?? 'produto') . '.');
                    }

                    $lineSubtotal = round($qtyToMove * (float) $sourceItem->price, 2);

                    RestaurantOrderItem::create([
                        'order_id' => $destinationOrder->id,
                        'product_id' => $sourceItem->product_id,
                        'qty' => $qtyToMove,
                        'price' => $sourceItem->price,
                        'subtotal' => $lineSubtotal,
                        'notes' => $sourceItem->notes,
                    ]);

                    if ($qtyToMove === $sourceQty) {
                        $sourceItem->delete();
                    } else {
                        $remainingQty = $sourceQty - $qtyToMove;
                        $sourceItem->update([
                            'qty' => $remainingQty,
                            'subtotal' => round($remainingQty * (float) $sourceItem->price, 2),
                        ]);
                    }
                }

                $order->refresh();
                $originTotal = round((float) $order->items()->sum('subtotal'), 2);
                $destinationTotal = round((float) $destinationOrder->items()->sum('subtotal'), 2);

                $destinationOrder->update([
                    'subtotal' => $destinationTotal,
                    'total' => $destinationTotal,
                ]);

                if ($originTotal <= 0 || !$order->items()->exists()) {
                    $order->update([
                        'subtotal' => 0,
                        'total' => 0,
                        'status' => 'transferred',
                    ]);

                    $fromTable->update([
                        'status' => 'free',
                        'current_order_id' => null,
                    ]);
                } else {
                    $order->update([
                        'subtotal' => $originTotal,
                        'total' => $originTotal,
                    ]);

                    $fromTable->update([
                        'status' => 'occupied',
                        'current_order_id' => $order->id,
                    ]);
                }


                $toTable->update([
                    'status' => 'occupied',
                    'current_order_id' => $destinationOrder->id,
                ]);

                return [
                    'order_id' => $destinationOrder->id,
                    'from_order_id' => $originTotal > 0 ? $order->id : null,
                    'from_table' => $fromTable->name,
                    'to_table' => $toTable->name,
                    'partial' => true,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => "Conta transferida de {$result['from_table']} para {$result['to_table']}.",
                'order_id' => $result['order_id'],
                'from_order_id' => $result['from_order_id'],
                'partial' => $result['partial'],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao transferir conta: ' . $e->getMessage(),
            ], 422);
        }
    }

    public function addItem(Request $request)
    {
        try {
            $request->validate([
                'order_id' => 'required|exists:restaurant_orders,id',
                'product_id' => 'required|exists:products,id',
                'quantity' => 'required|integer|min:1',
                'price' => 'required|numeric'
            ]);

            
            $order = RestaurantOrder::findOrFail($request->order_id);
            if ($this->isForeignActiveOrder($order)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Esta mesa esta ocupada por outro operador.',
                ], 403);
            }

// Procura se o item já existe na sessão/pedido atual
            $item = RestaurantOrderItem::where('order_id', $request->order_id)
                ->where('product_id', $request->product_id)
                ->first();

            if ($item) {
                $item->increment('qty', $request->quantity);
                $item->update([
                    'subtotal' => $item->qty * $item->price
                ]);
            } else {
                RestaurantOrderItem::create([
                    'order_id' => $request->order_id,
                    'product_id' => $request->product_id,
                    'qty' => $request->quantity,
                    'price' => $request->price,
                    'subtotal' => $request->quantity * $request->price
                ]);
            }

            $order = RestaurantOrder::find($request->order_id);

            if ($order) {
                // Atualiza os totais do pedido pai
                $novoTotal = $order->items()->sum('subtotal');
                $order->update([
                    'subtotal' => $novoTotal,
                    'total' => $novoTotal
                ]);

                // Atualiza o estado da mesa para ocupada
                $table = RestaurantTable::find($order->table_id);
                if ($table && $table->status !== 'occupied') {
                    $table->update(['status' => 'occupied']);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Item adicionado com sucesso!',
                'order_id' => $order?->id,
                'item' => [
                    'product_id' => $request->product_id,
                    'quantity' => $item ? $item->qty : $request->quantity,
                    'price' => $request->price,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao adicionar item: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getTablesState(): JsonResponse
    {
        try {
            $tables = RestaurantTable::with(['currentOrder.items.product'])->get();
            $formattedStates = [];

            foreach ($tables as $table) {
                $activeOrder = $table->currentOrder;

                if ($this->isForeignActiveOrder($activeOrder)) {
                    $formattedStates[$table->id] = [
                        'status' => 'hidden',
                        'order_id' => null,
                        'itens' => [],
                    ];
                    continue;
                }

                $hasItems = $activeOrder && $activeOrder->items->isNotEmpty();

                if ($table->status === 'reserved' && !$hasItems) {
                    $formattedStates[$table->id] = [
                        'status' => 'reserved',
                        'order_id' => $activeOrder?->id,
                        'itens' => [],
                    ];
                    continue;
                }

                if ($hasItems || in_array($table->status, ['occupied', 'waiting_payment'], true)) {
                    $formattedStates[$table->id] = [
                        'status' => 'occupied',
                        'order_id' => $activeOrder?->id,
                        'itens' => $activeOrder ? $activeOrder->items->map(function ($item) {
                            return [
                                'id' => $item->product_id,
                                'product_id' => $item->product_id,
                                'name' => $item->product ? $item->product->name : 'Produto Desconhecido',
                                'price' => (float) $item->price,
                                'qty' => (int) $item->qty,
                                'quantity' => (int) $item->qty,
                                'taxRate' => (float) ($item->product?->tax_rate ?? 0),
                                'total' => (float) $item->subtotal
                            ];
                        })->toArray() : []
                    ];
                } else {
                    $formattedStates[$table->id] = [
                        'status' => 'free',
                        'order_id' => null,
                        'itens' => []
                    ];
                }
            }

            return response()->json($formattedStates, 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao carregar o estado das mesas: ' . $e->getMessage()
            ], 500);
        }
    }

    public function removeItem(Request $request)
    {
        try {
            $request->validate([
                'order_id' => 'required|exists:restaurant_orders,id',
                'product_id' => 'required|exists:products,id'
            ]);

            $order = RestaurantOrder::findOrFail($request->order_id);
            if ($this->isForeignActiveOrder($order)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Esta mesa esta ocupada por outro operador.',
                ], 403);
            }
            $table = RestaurantTable::find($order->table_id);

            $item = $order->items()->where('product_id', $request->product_id)->first();

            if ($item) {
                if ($item->qty > 1) {
                    $item->decrement('qty');
                    $item->update(['subtotal' => $item->qty * $item->price]);
                } else {
                    $item->delete();
                }

                // Recalcula o total do pedido
                $novoTotal = $order->items()->sum('subtotal');
                $order->update([
                    'subtotal' => $novoTotal,
                    'total' => $novoTotal
                ]);
            }

            // --- LÓGICA DE LIBERTAÇÃO DA MESA ---
            // Se o pedido ficar vazio após a remoção, limpa a mesa e o pedido na BD
            if ($order->items()->count() === 0) {
                if ($table) {
                    $table->update([
                        'status' => 'free',
                        'current_order_id' => null
                    ]);
                }
                $order->update(['status' => 'canceled']);
            }

            return response()->json([
                'success' => true,
                'message' => 'Item removido e mesa verificada com sucesso!'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao remover item: ' . $e->getMessage()
            ], 500);
        }
    }

    public function clearCart(Request $request)
    {
        try {
            $request->validate([
                'order_id' => 'required|exists:restaurant_orders,id'
            ]);

            $order = RestaurantOrder::findOrFail($request->order_id);
            if ($this->isForeignActiveOrder($order)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Esta mesa esta ocupada por outro operador.',
                ], 403);
            }

            $order->items()->delete();

            $order->update([
                'subtotal' => 0,
                'total' => 0,
                'status' => 'canceled'
            ]);

            $table = RestaurantTable::find($order->table_id);
            if ($table) {
                $table->update([
                    'status' => 'free',
                    'current_order_id' => null
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Carrinho limpo e mesa libertada!'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao limpar carrinho: ' . $e->getMessage()
            ], 500);
        }
    }

    private function isForeignActiveOrder(?RestaurantOrder $order): bool
    {
        if (!$order || in_array($order->status, ['closed', 'cancelled', 'canceled', 'transferred'], true)) {
            return false;
        }

        $operatorId = session('operator_id');

        return $operatorId && (int) $order->operator_id !== (int) $operatorId;
    }

    private function assertOrderBelongsToCurrentOperator(RestaurantOrder $order): void
    {
        if ($this->isForeignActiveOrder($order)) {
            throw new \RuntimeException('Esta mesa esta ocupada por outro operador.');
        }
    }
    private function restaurantOrderTotals(RestaurantOrder $order): array
    {
        $grossTotal = 0.0;
        $netTotal = 0.0;
        $taxTotal = 0.0;
        $taxBreakdown = [];

        foreach ($order->items as $item) {
            $gross = round((float) $item->subtotal, 2);
            $taxRate = round((float) ($item->product?->tax_rate ?? 0), 2);
            $split = BusinessSettings::splitGrossTotal($gross, $taxRate);

            $grossTotal += $split['total'];
            $netTotal += $split['subtotal'];
            $taxTotal += $split['tax'];

            $key = number_format($taxRate, 2, '.', '');
            $taxBreakdown[$key] ??= [
                'rate' => $taxRate,
                'incidence' => 0.0,
                'tax' => 0.0,
            ];
            $taxBreakdown[$key]['incidence'] += $split['subtotal'];
            $taxBreakdown[$key]['tax'] += $split['tax'];
        }

        ksort($taxBreakdown, SORT_NUMERIC);

        return [
            'subtotal' => round($netTotal, 2),
            'tax' => round($taxTotal, 2),
            'total' => round($grossTotal, 2),
            'tax_breakdown' => array_map(fn ($row) => [
                'rate' => round($row['rate'], 2),
                'incidence' => round($row['incidence'], 2),
                'tax' => round($row['tax'], 2),
            ], array_values($taxBreakdown)),
        ];
    }
}
