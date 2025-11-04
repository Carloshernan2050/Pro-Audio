<?php

namespace App\Http\Controllers;

use App\Models\MovimientosInventario;
use App\Models\Inventario;
use Illuminate\Http\Request;

class MovimientosInventarioController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $movimientos = MovimientosInventario::with('inventario')->get();
        return response()->json($movimientos);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'inventario_id' => 'required|exists:inventario,id',
            'tipo_movimiento' => 'required|in:entrada,salida',
            'cantidad' => 'required|integer|min:1'
        ], [
            'inventario_id.required' => 'Debe seleccionar un artículo del inventario.',
            'inventario_id.exists' => 'El artículo seleccionado no existe.',
            'tipo_movimiento.required' => 'Debe seleccionar un tipo de movimiento.',
            'tipo_movimiento.in' => 'El tipo de movimiento debe ser entrada o salida.',
            'cantidad.required' => 'La cantidad es obligatoria.',
            'cantidad.integer' => 'La cantidad debe ser un número entero.',
            'cantidad.min' => 'La cantidad debe ser mayor a 0.'
        ]);

        $inventario = Inventario::findOrFail($request->inventario_id);

        // Actualizar stock según el tipo de movimiento
        if ($request->tipo_movimiento === 'entrada') {
            $inventario->stock += $request->cantidad;
        } else {
            if ($inventario->stock < $request->cantidad) {
                return response()->json(['error' => 'No hay suficiente stock disponible para realizar esta operación.'], 400);
            }
            $inventario->stock -= $request->cantidad;
        }

        $inventario->save();

        MovimientosInventario::create([
            'inventario_id' => $request->inventario_id,
            'tipo_movimiento' => $request->tipo_movimiento,
            'cantidad' => $request->cantidad,
            'fecha_movimiento' => now(),
            'descripcion' => ''
        ]);

        return response()->json(['success' => 'Movimiento de inventario registrado correctamente.']);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'inventario_id' => 'required|exists:inventario,id',
            'tipo_movimiento' => 'required|in:entrada,salida',
            'cantidad' => 'required|integer|min:1'
        ], [
            'inventario_id.required' => 'Debe seleccionar un artículo del inventario.',
            'inventario_id.exists' => 'El artículo seleccionado no existe.',
            'tipo_movimiento.required' => 'Debe seleccionar un tipo de movimiento.',
            'tipo_movimiento.in' => 'El tipo de movimiento debe ser entrada o salida.',
            'cantidad.required' => 'La cantidad es obligatoria.',
            'cantidad.integer' => 'La cantidad debe ser un número entero.',
            'cantidad.min' => 'La cantidad debe ser mayor a 0.'
        ]);

        $movimiento = MovimientosInventario::findOrFail($id);
        $inventarioOriginal = $movimiento->inventario;
        $nuevoInventario = Inventario::findOrFail($request->inventario_id);

        // Revertir el movimiento original
        if ($movimiento->tipo_movimiento === 'entrada') {
            $inventarioOriginal->stock -= $movimiento->cantidad;
        } else {
            $inventarioOriginal->stock += $movimiento->cantidad;
        }
        $inventarioOriginal->save();

        // Aplicar el nuevo movimiento
        if ($request->tipo_movimiento === 'entrada') {
            $nuevoInventario->stock += $request->cantidad;
        } else {
            if ($nuevoInventario->stock < $request->cantidad) {
                return response()->json(['error' => 'No hay suficiente stock disponible para realizar esta operación.'], 400);
            }
            $nuevoInventario->stock -= $request->cantidad;
        }
        $nuevoInventario->save();

        // Actualizar el movimiento
        $movimiento->update([
            'inventario_id' => $request->inventario_id,
            'tipo_movimiento' => $request->tipo_movimiento,
            'cantidad' => $request->cantidad
        ]);

        return response()->json(['success' => 'Movimiento de inventario actualizado correctamente.']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $movimiento = MovimientosInventario::findOrFail($id);
        $inventario = $movimiento->inventario;

        // Revertir el movimiento en el stock
        if ($movimiento->tipo_movimiento === 'entrada') {
            $inventario->stock -= $movimiento->cantidad;
        } else {
            $inventario->stock += $movimiento->cantidad;
        }

        $inventario->save();
        $movimiento->delete();

        return response()->json(['success' => 'Movimiento de inventario eliminado correctamente.']);
    }
}
