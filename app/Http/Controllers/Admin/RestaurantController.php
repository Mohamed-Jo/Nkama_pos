<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{Category, Customer, Operator, Product, RestaurantOrder, RestaurantOrderItem, RestaurantTable, Shift};
use App\Services\ModuleSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
            'tables' => RestaurantTable::orderByRaw('LENGTH(name), name')->get(),
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

            return response()->json([
                'success' => true,
                'table_status' => $hasItems ? 'occupied' : 'free',
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

    public function closeOrder($tableId)
    {
        try {
            $table = RestaurantTable::findOrFail($tableId);

            if ($table->current_order_id) {
                $order = RestaurantOrder::find($table->current_order_id);
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

    public function addItem(Request $request)
    {
        try {
            $request->validate([
                'order_id' => 'required|exists:restaurant_orders,id',
                'product_id' => 'required|exists:products,id',
                'quantity' => 'required|integer|min:1',
                'price' => 'required|numeric'
            ]);

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

                $hasItems = $activeOrder && $activeOrder->items->isNotEmpty();

                if ($hasItems || in_array($table->status, ['occupied', 'waiting_payment'], true)) {
                    $formattedStates[$table->id] = [
                        'status' => 'occupied',
                        'order_id' => $activeOrder?->id,
                        'itens' => $activeOrder ? $activeOrder->items->map(function ($item) {
                            return [
                                'id' => $item->id,
                                'product_id' => $item->product_id,
                                'name' => $item->product ? $item->product->name : 'Produto Desconhecido',
                                'price' => (float) $item->price,
                                'quantity' => (int) $item->qty,
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
}
