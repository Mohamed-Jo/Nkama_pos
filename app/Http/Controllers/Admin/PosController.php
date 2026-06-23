<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{Operator, Product, Customer, RestaurantTable, Sale, SaleItem, Shift, Category};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PosController extends Controller
{
   public function index()
    {
        return view('admin.pos.index', [
            'products' => Product::where('status', true)->orderBy('name')->get(),
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
            'tables' => RestaurantTable::all(), // <-- ADICIONA ESTA LINHA AQUI
        ]);
    }

    public function checkout(Request $request)
    {
        $request->validate([
            'items' => 'required|array',
            'total' => 'required|numeric',
        ]);

        try {
            DB::beginTransaction();

            $order = Sale::create([
                'total' => $request->total,
                'status' => 'paid',
                // Correção: $request->method() devolve "POST". Captura o campo 'payment_method' do JSON/Form
                'method' => $request->input('payment_method', 'cash')
            ]);

            foreach ($request->items as $item) {
                SaleItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['id'],
                    'quantity' => $item['qty'],
                    'price' => $item['price']
                ]);

                $product = Product::find($item['id']);
                if ($product) {
                    $product->decrement('stock', $item['qty']);
                }
            }

            DB::commit();
            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // Adicionado o método que faltava para ler o código de barras
    public function findProductByBarcode(Request $request)
    {
        $product = Product::where('barcode', $request->barcode)->where('status', true)->first();

        if (!$product) {
            return response()->json(['success' => false, 'message' => 'Produto não encontrado.'], 404);
        }

        return response()->json(['success' => true, 'data' => $product]);
    }
}
