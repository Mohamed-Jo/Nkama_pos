<?php

namespace App\Services;

use App\Models\Stock;
use App\Models\Product;
use App\Models\StockMovement;

class StockServices
{
    public function in($product, $qty, $note = null)
    {
        $before = $product->stock_quantity;

        $product->increment('stock_quantity', $qty);

        StockMovement::create([
            'product_id' => $product->id,
            'type' => 'IN',
            'quantity' => $qty,
            'stock_before' => $before,
            'stock_after' => $product->stock_quantity,
            'notes' => $note,
            'user_id' => auth()->id(),
        ]);
    }

    public function out($product, $qty, $note = null)
    {
        $before = $product->stock_quantity;

        if ($product->stock_quantity < $qty) {
            throw new \Exception("Stock insuficiente");
        }

        $product->decrement('stock_quantity', $qty);

        StockMovement::create([
            'product_id' => $product->id,
            'type' => 'OUT',
            'quantity' => $qty,
            'stock_before' => $before,
            'stock_after' => $product->stock_quantity,
            'notes' => $note,
            'user_id' => auth()->id(),
        ]);
    }
}