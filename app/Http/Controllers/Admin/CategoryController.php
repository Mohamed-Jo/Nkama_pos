<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::latest()->paginate(20);

        return view('admin.categories.index', compact('categories'));
    }

    public function create()
    {
        return view('admin.categories.create');
    }

    public function store(Request $request)
    {
        Category::create([
            'name' => $request->name,
            'description' => $request->description,
            'status' => true
        ]);

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Categoria criada com sucesso');
    }

    /**
     * Retorna as subcategorias de uma especialidade em formato JSON
     */
    public function subcategories($id)
    {
        $category = Category::findOrFail($id);

        if ($category->parent_id !== null) {
            // Se for uma subcategoria, retornar as subcategorias do pai
            $subcategories = Category::where('parent_id', $category->parent_id)->get();
        } else {
            // Se for uma especialidade, retornar suas subcategorias
            $subcategories = $category->subcategories;
        }

        return response()->json($subcategories);
    }
}
