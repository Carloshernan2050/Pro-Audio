<?php

namespace App\Repositories;

use App\Models\Cotizacion;
use App\Repositories\Interfaces\CotizacionRepositoryInterface;

class CotizacionRepository implements CotizacionRepositoryInterface
{
    /**
     * Crea una nueva cotización en la base de datos.
     *
     * @param  array  $data
     * @return Cotizacion
     */
    public function crear(array $data): Cotizacion
    {
        return Cotizacion::create($data);
    }

    /**
     * Obtiene todas las cotizaciones con sus relaciones.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function allWithRelations(): \Illuminate\Database\Eloquent\Collection
    {
        return Cotizacion::with(['persona', 'subServicio.servicio'])
            ->orderBy('fecha_cotizacion', 'desc')
            ->get();
    }

    /**
     * Elimina cotizaciones por IDs de subservicios.
     *
     * @param  array  $subServicioIds
     * @return bool
     */
    public function deleteBySubServicioIds(array $subServicioIds): bool
    {
        return Cotizacion::whereIn('sub_servicios_id', $subServicioIds)->delete() > 0;
    }

    /**
     * Obtiene todas las cotizaciones de un cliente específico con sus relaciones.
     *
     * @param  int  $personasId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByPersonasId(int $personasId): \Illuminate\Database\Eloquent\Collection
    {
        return Cotizacion::with(['persona', 'subServicio.servicio'])
            ->where('personas_id', $personasId)
            ->orderBy('fecha_cotizacion', 'desc')
            ->get();
    }
}

