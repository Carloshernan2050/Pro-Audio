<?php

namespace App\Services;

use App\Models\Calendario;
use App\Models\Reserva;
use Illuminate\Http\Request;

class ReservaService
{
    /**
     * Actualiza la reserva vinculada con items.
     */
    public function actualizarReservaVinculada(Request $request, int $calendarioId, int $cantidadTotal, array $nuevosItemsReserva): void
    {
        $reserva = Reserva::with('items')->where('calendario_id', $calendarioId)->first();
        if (! $reserva) {
            return;
        }

        $reserva->update([
            'servicio' => $request->input('servicio'),
            'fecha_inicio' => $request->fecha_inicio,
            'fecha_fin' => $request->fecha_fin,
            'descripcion_evento' => $request->descripcion_evento,
            'cantidad_total' => $cantidadTotal,
            'meta' => array_merge($reserva->meta ?? [], [
                'actualizada_en' => now()->toDateTimeString(),
            ]),
        ]);

        $reserva->items()->delete();
        foreach ($nuevosItemsReserva as $item) {
            $reserva->items()->create($item);
        }
    }

    /**
     * Actualiza la reserva vinculada en formato antiguo.
     */
    public function actualizarReservaFormatoAntiguo(Request $request, int $calendarioId, Calendario $calendario): void
    {
        $reserva = Reserva::where('calendario_id', $calendarioId)->first();
        if (! $reserva) {
            return;
        }

        $reserva->update([
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
