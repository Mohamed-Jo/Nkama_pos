<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RestaurantTable;
use Illuminate\Http\Request;

class RestaurantMesaController extends Controller
{
    // Listar todas as mesas
    public function index()
    {
        $tables = RestaurantTable::orderByRaw('LENGTH(name), name')->get();
        return view('admin.restaurant.index', compact('tables'));
    }

    // Mostrar formulário para criar mesa
    public function create()
    {
        return view('admin.restaurant.create');
    }

    // Gravar nova mesa
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:10',
            'capacity' => 'required|integer|min:1',
            // 'name' => 'required|string|max:10',
        ]);

        RestaurantTable::create(array_merge($validated, [
            'status' => 'free',
        ]));

        return redirect()->route('admin.restaurantMesa.index')
                         ->with('success', 'Mesa criada com sucesso!');
    }

    // Alternar estado (Livre <-> Ocupada)
    public function toggleStatus(RestaurantTable $table)
    {
        $table->status = ($table->status === 'free') ? 'occupied' : 'free';
        if ($table->status === 'free') {
            $table->current_order_id = null;
        }
        $table->save();

        return back()->with('success', 'Estado da mesa ' . $table->name . ' atualizado!');
    }
}
