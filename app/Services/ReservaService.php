<?php

namespace App\Services;

use App\Repositories\Interfaces\CalendarioRepositoryInterface;
use App\Repositories\Interfaces\ReservaItemRepositoryInterface;
use App\Repositories\Interfaces\ReservaRepositoryInterface;
use Illuminate\Http\Request;

class ReservaService
{
    private ReservaRepositoryInterface $reservaRepository;

    private ReservaItemRepositoryInterface $reservaItemRepository;

    private CalendarioRepositoryInterface $calendarioRepository;

    public function __construct(
        ReservaRepositoryInterface $reservaRepository,
        ReservaItemRepositoryInterface $reservaItemRepository,
        CalendarioRepositoryInterface $calendarioRepository
    ) {
        $this->reservaRepository = $reservaRepository;
        $this->reservaItemRepository = $reservaItemRepository;
        $this->calendarioRepository = $calendarioRepository;
    }

    /**
     * Actualiza la reserva vinculada con items.
     */
    public function actualizarReservaVinculada(Request $request, int $calendarioId, int $cantidadTotal, array $nuevosItemsReserva): void
    {
        // Usar repositorio en lugar de modelo directo (DIP)
        $reserva = $this->reservaRepository->findByCalendarioId($calendarioId);
        if (! $reserva) {
            return;
        }

        // Usar repositorio para actualizar (DIP)
        $this->reservaRepository->update($reserva->id, [
            'servicio' => $request->input('servicio'),
            'fecha_inicio' => $request->fecha_inicio,
            'fecha_fin' => $request->fecha_fin,
            'descripcion_evento' => $request->descripcion_evento,
            'cantidad_total' => $cantidadTotal,
            'meta' => array_merge($reserva->meta ?? [], [
                'actualizada_en' => now()->toDateTimeString(),
            ]),
        ]);

        // Usar repositorio para eliminar items (DIP)
        $this->reservaItemRepository->deleteByReservaId($reserva->id);
        
        // Usar repositorio para crear items (DIP)
        foreach ($nuevosItemsReserva as $item) {
            $this->reservaItemRepository->create([
                'reserva_id' => $reserva->id,
                'inventario_id' => $item['inventario_id'],
                'cantidad' => $item['cantidad'],
            ]);
        }
    }

    /**
     * Actualiza la reserva vinculada en formato antiguo.
     */
    public function actualizarReservaFormatoAntiguo(Request $request, int $calendarioId, $calendario): void
    {
        // Usar repositorio en lugar de modelo directo (DIP)
        $reserva = $this->reservaRepository->findByCalendarioId($calendarioId);
        if (! $reserva) {
            return;
        }

        // Usar repositorio para obtener calendario si es necesario
        if (! is_object($calendario) || ! isset($calendario->cantidad)) {
            $calendario = $this->calendarioRepository->find($calendarioId);
        }

        // Usar repositorio para actualizar (DIP)
        $this->reservaRepository->update($reserva->id, [
            'servicio' => $request->input('servicio'),
            'fecha_inicio' => $request->fecha_inicio,
            'fecha_fin' => $request->fecha_fin,
            'descripcion_evento' => $request->descripcion_evento,
            'cantidad_total' => $request->cantidad ?? $calendario->cantidad,
            'meta' => array_merge($reserva->meta ?? [], [
                'actualizada_en' => now()->toDateTimeString(),
            ]),
        ]);
    }
}
