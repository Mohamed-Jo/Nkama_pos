<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Http\Request;

class StockController extends Controller
{
    // 📦 ENTRADA DE STOCK
    public function in(Request $request)
    {
        $product = Product::findOrFail($request->product_id);

        $stockBefore = $product->stock_quantity;

        $product->stock_quantity += $request->quantity;
        $product->save();

        StockMovement::create([
            'product_id' => $product->id,
            'type' => 'IN',
            'quantity' => $request->quantity,
            'stock_before' => $stockBefore,
            'stock_after' => $product->stock_quantity,
            'notes' => $request->notes,
            'user_id' => auth()->id(),
            'operator_id' => session('operator_id'),
        ]);

        return back()->with('success', 'Stock atualizado (ENTRADA)');
    }

    // 📉 SAÍDA DE STOCK
    public function out(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|numeric|min:1',
            'notes' => 'nullable|string'
        ]);

        $product = Product::findOrFail($request->product_id);

        // bloquear stock negativo
        if ($product->stock_quantity < $request->quantity) {
            return back()->with('error', 'Stock insuficiente');
        }

        $stockBefore = $product->stock_quantity;

        $product->decrement(
            'stock_quantity',
            $request->quantity
        );

        StockMovement::create([
            'product_id' => $product->id,
            'type' => 'OUT',
            'quantity' => $request->quantity,
            'stock_before' => $stockBefore,
            'stock_after' => $product->fresh()->stock_quantity,
            'notes' => $request->notes ?? 'Ajuste manual',
            'user_id' => auth()->id(),
            'operator_id' => session('operator_id'),
        ]);

        return back()->with(
            'success',
            'Saída de stock registada'
        );
    }
}
