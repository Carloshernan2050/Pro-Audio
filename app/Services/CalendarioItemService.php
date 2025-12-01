<?php

namespace App\Services;

use App\Models\CalendarioItem;
use App\Models\MovimientosInventario;
use Illuminate\Support\Facades\DB;

class CalendarioItemService
{
    /**
     * Crea los items de calendario asociados.
     */
    public function crearItemsCalendario(int $calendarioId, array $items): void
    {
        foreach ($items as $item) {
            $movimientoId = $this->obtenerOCrearMovimientoInventario($item['inventario_id']);

            CalendarioItem::create([
                'calendario_id' => $calendarioId,
                'movimientos_inventario_id' => $movimientoId,
                'cantidad' => $item['cantidad'] ?? 1,
            ]);
        }
    }

    /**
     * Obtiene o crea un movimiento de inventario.
     */
    public function obtenerOCrearMovimientoInventario(int $inventarioId): int
    {
        $movimiento = DB::table('movimientos_inventario')
            ->where('inventario_id', $inventarioId)
            ->first();

        if ($movimiento) {
            return $movimiento->id;
        }

        $inventario = DB::table('inventario')->where('id', $inventarioId)->first();
        $stockActual = $inventario->stock ?? 0;

        return MovimientosInventario::create([
            'inventario_id' => $inventarioId,
            'tipo_movimiento' => 'entrada',
            'cantidad' => $stockActual > 0 ? $stockActual : 1,
            'fecha_movimiento' => now(),
            'descripcion' => 'Movimiento automático al crear alquiler',
        ])->id;
    }

    /**
     * Crea items de calendario para una actualización.
     */
    public function crearItemsCalendarioParaActualizacion(int $calendarioId, array $items): array
    {
        $nuevosItemsReserva = [];

        foreach ($items as $item) {
            $inventarioId = $item['inventario_id'];
            $cantidad = $item['cantidad'] ?? 1;
            $movimientoId = $this->obtenerOCrearMovimientoInventario($inventarioId);

            \App\Models\Inventario::where('id', $inventarioId)->decrement('stock', $cantidad);

            MovimientosInventario::create([
                'inventario_id' => $inventarioId,
                'tipo_movimiento' => 'alquilado',
                'cantidad' => $cantidad,
                'fecha_movimiento' => now(),
                'descripcion' => 'Ajuste de reserva #'.$calendarioId.' (nueva cantidad)',
            ]);

            CalendarioItem::create([
                'calendario_id' => $calendarioId,
                'movimientos_inventario_id' => $movimientoId,
                'cantidad' => $cantidad,
            ]);

            $nuevosItemsReserva[] = [
                'inventario_id' => $inventarioId,
                'cantidad' => $cantidad,
            ];
        }

        return $nuevosItemsReserva;
    }
}
