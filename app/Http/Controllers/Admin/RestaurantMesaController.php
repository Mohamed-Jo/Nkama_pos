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
        $tables = RestaurantTable::all();
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

        RestaurantTable::create($validated);

        return redirect()->route('admin.restaurant.index')
                         ->with('success', 'Mesa criada com sucesso!');
    }

    // Alternar estado (Livre <-> Ocupada)
    public function toggleStatus(RestaurantTable $table)
    {
        $table->status = ($table->status === 'livre') ? 'ocupada' : 'livre';
        $table->save();

        return back()->with('success', 'Estado da mesa ' . $table->number . ' atualizado!');
    }
}