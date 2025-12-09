<?php

namespace App\Services;

use App\Repositories\Interfaces\CalendarioItemRepositoryInterface;
use App\Repositories\Interfaces\InventarioRepositoryInterface;
use App\Repositories\Interfaces\MovimientoInventarioRepositoryInterface;
use Illuminate\Support\Facades\DB;

class CalendarioItemService
{
    private CalendarioItemRepositoryInterface $calendarioItemRepository;

    private MovimientoInventarioRepositoryInterface $movimientoInventarioRepository;

    private InventarioRepositoryInterface $inventarioRepository;

    public function __construct(
        CalendarioItemRepositoryInterface $calendarioItemRepository,
        MovimientoInventarioRepositoryInterface $movimientoInventarioRepository,
        InventarioRepositoryInterface $inventarioRepository
    ) {
        $this->calendarioItemRepository = $calendarioItemRepository;
        $this->movimientoInventarioRepository = $movimientoInventarioRepository;
        $this->inventarioRepository = $inventarioRepository;
    }
    /**
     * Crea los items de calendario asociados.
     */
    public function crearItemsCalendario(int $calendarioId, array $items): void
    {
        foreach ($items as $item) {
            $movimientoId = $this->obtenerOCrearMovimientoInventario($item['inventario_id']);

            // Usar repositorio en lugar de modelo directo (DIP)
            $this->calendarioItemRepository->create([
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

        // Usar repositorio en lugar de modelo directo (DIP)
        $inventario = $this->inventarioRepository->find($inventarioId);
        $stockActual = $inventario->stock ?? 0;

        // Usar repositorio en lugar de modelo directo (DIP)
        $movimiento = $this->movimientoInventarioRepository->create([
            'inventario_id' => $inventarioId,
            'tipo_movimiento' => 'entrada',
            'cantidad' => $stockActual > 0 ? $stockActual : 1,
            'fecha_movimiento' => now(),
            'descripcion' => 'Movimiento automático al crear alquiler',
        ]);

        return $movimiento->id;
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

            // Usar repositorio en lugar de modelo directo (DIP)
            $this->inventarioRepository->decrementStock($inventarioId, $cantidad);

            // Usar repositorio en lugar de modelo directo (DIP)
            $this->movimientoInventarioRepository->create([
                'inventario_id' => $inventarioId,
                'tipo_movimiento' => 'alquilado',
                'cantidad' => $cantidad,
                'fecha_movimiento' => now(),
                'descripcion' => 'Ajuste de reserva #'.$calendarioId.' (nueva cantidad)',
            ]);

            // Usar repositorio en lugar de modelo directo (DIP)
            $this->calendarioItemRepository->create([
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
