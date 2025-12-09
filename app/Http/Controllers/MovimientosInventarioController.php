<?php

namespace App\Http\Controllers;

use App\Repositories\Interfaces\InventarioRepositoryInterface;
use App\Repositories\Interfaces\MovimientoInventarioRepositoryInterface;
use Illuminate\Http\Request;

class MovimientosInventarioController extends Controller
{
    private MovimientoInventarioRepositoryInterface $movimientoInventarioRepository;

    private InventarioRepositoryInterface $inventarioRepository;

    public function __construct(
        MovimientoInventarioRepositoryInterface $movimientoInventarioRepository,
        InventarioRepositoryInterface $inventarioRepository
    ) {
        $this->movimientoInventarioRepository = $movimientoInventarioRepository;
        $this->inventarioRepository = $inventarioRepository;
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $movimientos = \App\Models\MovimientosInventario::with('inventario')->get();

        return response()->json($movimientos);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'inventario_id' => 'required|exists:inventario,id',
            'tipo_movimiento' => 'required|in:entrada,salida,alquilado,devuelto',
            'cantidad' => 'required|integer|min:1',
        ], [
            'inventario_id.required' => 'Debe seleccionar un artículo del inventario.',
            'inventario_id.exists' => 'El artículo seleccionado no existe.',
            'tipo_movimiento.required' => 'Debe seleccionar un tipo de movimiento.',
            'tipo_movimiento.in' => 'El tipo de movimiento debe ser entrada, salida, alquilado o devuelto.',
            'cantidad.required' => 'La cantidad es obligatoria.',
            'cantidad.integer' => 'La cantidad debe ser un número entero.',
            'cantidad.min' => 'La cantidad debe ser mayor a 0.',
        ]);

        // Usar repositorio en lugar de modelo directo (DIP)
        $inventario = $this->inventarioRepository->find($request->inventario_id);
        $incrementaStock = in_array($request->tipo_movimiento, ['entrada', 'devuelto'], true);

        // Actualizar stock según el tipo de movimiento usando repositorio (DIP)
        if ($incrementaStock) {
            $this->inventarioRepository->incrementStock($inventario->id, $request->cantidad);
        } else {
            if ($inventario->stock < $request->cantidad) {
                return response()->json(['error' => 'No hay suficiente stock disponible para realizar esta operación.'], 400);
            }
            $this->inventarioRepository->decrementStock($inventario->id, $request->cantidad);
        }

        // Usar repositorio en lugar de modelo directo (DIP)
        $movimiento = $this->movimientoInventarioRepository->create([
            'inventario_id' => $request->inventario_id,
            'tipo_movimiento' => $request->tipo_movimiento,
            'cantidad' => $request->cantidad,
            'fecha_movimiento' => now(),
            'descripcion' => $request->descripcion ?? '',
        ]);

        return response()->json([
            'success' => 'Movimiento de inventario registrado correctamente.',
            'movimiento_id' => $movimiento->id,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'inventario_id' => 'required|exists:inventario,id',
            'tipo_movimiento' => 'required|in:entrada,salida,alquilado,devuelto',
            'cantidad' => 'required|integer|min:1',
        ], [
            'inventario_id.required' => 'Debe seleccionar un artículo del inventario.',
            'inventario_id.exists' => 'El artículo seleccionado no existe.',
            'tipo_movimiento.required' => 'Debe seleccionar un tipo de movimiento.',
            'tipo_movimiento.in' => 'El tipo de movimiento debe ser entrada, salida, alquilado o devuelto.',
            'cantidad.required' => 'La cantidad es obligatoria.',
            'cantidad.integer' => 'La cantidad debe ser un número entero.',
            'cantidad.min' => 'La cantidad debe ser mayor a 0.',
        ]);

        // Usar repositorio en lugar de modelo directo (DIP)
        $movimiento = $this->movimientoInventarioRepository->find($id);
        $inventarioOriginal = $this->inventarioRepository->find($movimiento->inventario_id);
        $nuevoInventario = $this->inventarioRepository->find($request->inventario_id);
        $incrementaStockNuevo = in_array($request->tipo_movimiento, ['entrada', 'devuelto'], true);
        $incrementaStockOriginal = in_array($movimiento->tipo_movimiento, ['entrada', 'devuelto'], true);

        // Revertir el movimiento original usando repositorio (DIP)
        if ($incrementaStockOriginal) {
            $this->inventarioRepository->decrementStock($inventarioOriginal->id, $movimiento->cantidad);
        } else {
            $this->inventarioRepository->incrementStock($inventarioOriginal->id, $movimiento->cantidad);
        }

        // Aplicar el nuevo movimiento usando repositorio (DIP)
        if ($incrementaStockNuevo) {
            $this->inventarioRepository->incrementStock($nuevoInventario->id, $request->cantidad);
        } else {
            if ($nuevoInventario->stock < $request->cantidad) {
                return response()->json(['error' => 'No hay suficiente stock disponible para realizar esta operación.'], 400);
            }
            $this->inventarioRepository->decrementStock($nuevoInventario->id, $request->cantidad);
        }

        // Actualizar el movimiento usando repositorio (DIP)
        $this->movimientoInventarioRepository->update($id, [
            'inventario_id' => $request->inventario_id,
            'tipo_movimiento' => $request->tipo_movimiento,
            'cantidad' => $request->cantidad,
        ]);

        return response()->json(['success' => 'Movimiento de inventario actualizado correctamente.']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        // Usar repositorio en lugar de modelo directo (DIP)
        $movimiento = $this->movimientoInventarioRepository->find($id);
        $inventario = $this->inventarioRepository->find($movimiento->inventario_id);
        $incrementaStock = in_array($movimiento->tipo_movimiento, ['entrada', 'devuelto'], true);

        // Revertir el movimiento en el stock usando repositorio (DIP)
        if ($incrementaStock) {
            $this->inventarioRepository->decrementStock($inventario->id, $movimiento->cantidad);
        } else {
            $this->inventarioRepository->incrementStock($inventario->id, $movimiento->cantidad);
        }

        // Usar repositorio para eliminar (DIP)
        $this->movimientoInventarioRepository->delete($id);

        return response()->json(['success' => 'Movimiento de inventario eliminado correctamente.']);
    }
}
