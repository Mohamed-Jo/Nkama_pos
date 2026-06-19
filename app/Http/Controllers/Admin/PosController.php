<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{Operator, Product, Customer, RestaurantOrder, RestaurantTable, Sale, SaleItem, Shift, StockMovement};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PosController extends Controller
{
    public function index()
    {
        return view('admin.pos.index', [
            'products' => Product::where('status', true)->get(),
            'customers' => Customer::where('status', true)->get(),
            'operatorName' => Operator::find(session('operator_id'))?->name ?? 'Operador',
            'shift' => Shift::where('operator_id', session('operator_id'))->where('status', 'open')->first(),
            'tables' => RestaurantTable::all(),
        ]);
    }

    public function checkout(Request $request)
    {
        // 1. Validar a requisição
        $request->validate([
            'table_id' => 'required',
            'items' => 'required|array',
        ]);

        try {
            DB::beginTransaction();

            // 2. Criar o Registo de Venda (Order)
            $order = Sale::create([
                'table_id' => $request->table_id,
                'total' => $request->total,
                'status' => 'paid'
            ]);

            // 3. Inserir itens e baixar stock
            foreach ($request->items as $item) {
                SaleItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['id'],
                    'quantity' => $item['qty'],
                    'price' => $item['price']
                ]);

                // Redução de stock
                Product::find($item['id'])->decrement('stock', $item['qty']);
            }

            DB::commit();
            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function openTable($id)
    {
        $table = RestaurantTable::findOrFail($id);

        if (!$table->current_order_id) {
            $order = RestaurantOrder::create(['table_id' => $table->id, 'status' => 'open', 'total' => 0]);
            $table->update(['status' => 'occupied', 'current_order_id' => $order->id]);
        }

        $order = $table->currentOrder()->with('items')->first();

        return response()->json([
            'order' => $order,
            'items' => $order->items
        ]);
    }

    public function closeOrder($orderId)
    {
        $order = RestaurantOrder::findOrFail($orderId);

        $order->update(['status' => 'closed', 'closed_at' => now()]);
        $order->table()->update(['status' => 'waiting_payment']);

        return response()->json(['success' => true]);
    }

}