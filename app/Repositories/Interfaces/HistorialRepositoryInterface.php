<?php

namespace App\Repositories\Interfaces;

use App\Models\Historial;
use Illuminate\Database\Eloquent\Collection;

interface HistorialRepositoryInterface
{
    /**
     * Obtiene todos los registros de historial con sus relaciones.
     *
     * @return Collection
     */
    public function allWithRelations(): Collection;

    /**
     * Obtiene reservas confirmadas con sus relaciones.
     *
     * @return Collection
     */
    public function getReservasConfirmadas(): Collection;

    /**
     * Crea un nuevo registro de historial.
     *
     * @param  array  $data
     * @return Historial
     */
    public function create(array $data): Historial;

    /**
     * Actualiza un registro de historial existente.
     *
     * @param  int  $id
     * @param  array  $data
     * @return bool
     */
    public function update(int $id, array $data): bool;
}

