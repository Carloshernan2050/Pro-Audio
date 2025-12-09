<?php

namespace App\Http\Controllers;

use App\Repositories\Interfaces\InventarioRepositoryInterface;
use Illuminate\Http\Request;

class InventarioController extends Controller
{
    private InventarioRepositoryInterface $inventarioRepository;

    public function __construct(InventarioRepositoryInterface $inventarioRepository)
    {
        $this->inventarioRepository = $inventarioRepository;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Usar repositorio en lugar de modelo directo (DIP)
        $inventario = $this->inventarioRepository->all();

        return response()->json($inventario);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'descripcion' => 'required|string|max:255',
            'stock' => 'required|integer|min:0',
        ], [
            'descripcion.required' => 'La descripción es obligatoria.',
            'descripcion.string' => 'La descripción debe ser texto.',
            'descripcion.max' => 'La descripción no puede exceder 255 caracteres.',
            'stock.required' => 'El stock es obligatorio.',
            'stock.integer' => 'El stock debe ser un número entero.',
            'stock.min' => 'El stock no puede ser menor a 0.',
        ]);

        // Usar repositorio en lugar de modelo directo (DIP)
        $this->inventarioRepository->create($request->only('descripcion', 'stock'));

        return response()->json(['success' => 'Artículo del inventario creado correctamente.']);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'descripcion' => 'required|string|max:255',
            'stock' => 'required|integer|min:0',
        ], [
            'descripcion.required' => 'La descripción es obligatoria.',
            'descripcion.string' => 'La descripción debe ser texto.',
            'descripcion.max' => 'La descripción no puede exceder 255 caracteres.',
            'stock.required' => 'El stock es obligatorio.',
            'stock.integer' => 'El stock debe ser un número entero.',
            'stock.min' => 'El stock no puede ser menor a 0.',
        ]);

        // Usar repositorio en lugar de modelo directo (DIP)
        $this->inventarioRepository->update((int) $id, $request->only('descripcion', 'stock'));

        return response()->json(['success' => 'Artículo del inventario actualizado correctamente.']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        // Usar repositorio en lugar de modelo directo (DIP)
        $this->inventarioRepository->delete((int) $id);

        return response()->json(['success' => 'Artículo del inventario eliminado correctamente.']);
    }
}
