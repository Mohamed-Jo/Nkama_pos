<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RestaurantTable;
use App\Models\RestaurantOrder;
use App\Models\Sale;
use App\Models\SaleItem;
use DB;
use Illuminate\Http\Request;

class RestaurantController extends Controller
{
    public function index()
    {
        $tables = RestaurantTable::all();
        return view('admin.pos.index', compact('tables'));
    }

    public function table($id)
    {
        $table = RestaurantTable::with('orders.items.product')->findOrFail($id);

        return response()->json([
            'table' => $table,
            'items' => $table->orders()->where('status', 'open')->with('items')->first()?->items ?? []
        ]);
    }

    public function openTable($id)
    {
        $table = RestaurantTable::findOrFail($id);

        $order = $table->orders()->firstOrCreate([
            'status' => 'open'
        ]);

        return response()->json([
            'order_id' => $order->id,
            'items' => $order->items()->get()
        ]);
    }

    public function addItem(Request $request)
    {
        $order = RestaurantOrder::where('table_id', $request->table_id)
            ->where('status', 'open')
            ->first();

        if (!$order) {
            return response()->json(['error' => 'Mesa sem pedido aberto'], 400);
        }

        $item = $order->items()->where('product_id', $request->product_id)->first();

        if ($item) {
            $item->qty += $request->qty;
            $item->save();
        } else {
            $order->items()->create([
                'product_id' => $request->product_id,
                'qty' => $request->qty,
                'price' => $request->price
            ]);
        }

        return response()->json(['success' => true]);
    }
    public function closeTable($id)
    {
        DB::beginTransaction();

        try {

            $table = RestaurantTable::findOrFail($id);

            $orders = RestaurantOrder::where('table_id', $id)
                ->where('status', 'open')
                ->get();

            if ($orders->isEmpty()) {
                return response()->json([
                    'message' => 'Mesa sem pedidos'
                ], 400);
            }

            $total = 0;

            foreach ($orders as $order) {
                $total += $order->price * $order->qty;
            }

            // 🔥 CRIAR VENDA (INTEGRAÇÃO COM POS)
            $sale = Sale::create([
                'customer_id' => null,
                'user_id' => auth()->id(),
                'invoice_number' => 'REST-' . time(),
                'subtotal' => $total,
                'discount' => 0,
                'tax' => 0,
                'total' => $total,
                'payment_method' => 'cash',
                'payment_status' => 'paid'
            ]);

            foreach ($orders as $order) {

                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $order->product_id,
                    'quantity' => $order->qty,
                    'unit_price' => $order->price,
                    'subtotal' => $order->price * $order->qty
                ]);

                // fechar pedido
                $order->update(['status' => 'closed']);
            }

            // fechar mesa
            $table->update(['status' => 'free']);

            DB::commit();

            return response()->json([
                'success' => true,
                'sale_id' => $sale->id
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => $e->getMessage()
            ], 500);
        }
    }
    public function closeOrder($id)
    {
        $order = RestaurantOrder::findOrFail($id);

        $order->status = 'closed';
        $order->save();

        return response()->json(['success' => true]);
    }

    /**
 * Localiza um produto instantaneamente pelo código de barras para o Supermercado
 */
public function findProductByBarcode(Request $request)
{
    $request->validate([
        'barcode' => 'required|string'
    ]);

    // Procura na coluna de código de barras do teu banco (ex: barcode, ean, sku)
    $product = \App\Models\Product::where('barcode', $request->barcode)
        ->orWhere('name', 'like', "%{$request->barcode}%")
        ->first();

    if (!$product) {
        return response()->json(['success' => false, 'message' => 'Artigo não localizado.'], 404);
    }

    // Verifica se há stock antes de retornar para o leitor
    if ($product->stock_quantity <= 0) {
        return response()->json(['success' => false, 'message' => 'Artigo sem stock disponível.'], 400);
    }

    return response()->json([
        'success' => true,
        'product' => [
            'id' => $product->id,
            'name' => $product->name,
            'price' => $product->selling_price
        ]
    ]);
}


}