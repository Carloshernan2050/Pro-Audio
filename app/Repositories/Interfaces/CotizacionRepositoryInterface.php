<?php

namespace App\Repositories\Interfaces;

use App\Models\Cotizacion;
use Illuminate\Database\Eloquent\Collection;

interface CotizacionRepositoryInterface
{
    /**
     * Crea una nueva cotización en la base de datos.
     *
     * @param  array  $data
     * @return Cotizacion
     */
    public function crear(array $data): Cotizacion;

    /**
     * Obtiene todas las cotizaciones con sus relaciones.
     *
     * @return Collection
     */
    public function allWithRelations(): Collection;

    /**
     * Elimina cotizaciones por IDs de subservicios.
     *
     * @param  array  $subServicioIds
     * @return bool
     */
    public function deleteBySubServicioIds(array $subServicioIds): bool;
}

