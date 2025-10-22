<?php

namespace App\Http\Controllers;

use App\Models\Inventario;
use Illuminate\Http\Request;

class InventarioController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $inventario = Inventario::all();
        return response()->json($inventario);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'descripcion' => 'required|string|max:255',
            'stock' => 'required|integer|min:0'
        ]);

        Inventario::create($request->only('descripcion', 'stock'));

        return response()->json(['success' => 'Item creado correctamente.']);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'descripcion' => 'required|string|max:255',
            'stock' => 'required|integer|min:0'
        ]);

        $inventario = Inventario::findOrFail($id);
        $inventario->update($request->only('descripcion', 'stock'));

        return response()->json(['success' => 'Item actualizado correctamente.']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $inventario = Inventario::findOrFail($id);
        $inventario->delete();

        return response()->json(['success' => 'Item eliminado correctamente.']);
    }
}
