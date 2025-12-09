<?php

namespace App\Repositories\Interfaces;

use App\Models\MovimientosInventario;
use Illuminate\Database\Eloquent\Collection;

interface MovimientoInventarioRepositoryInterface
{
    /**
     * Crea un nuevo movimiento de inventario.
     *
     * @param  array  $data
     * @return MovimientosInventario
     */
    public function create(array $data): MovimientosInventario;

    /**
     * Busca un movimiento por ID.
     *
     * @param  int  $id
     * @return MovimientosInventario
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function find(int $id): MovimientosInventario;

    /**
     * Actualiza un movimiento existente.
     *
     * @param  int  $id
     * @param  array  $data
     * @return bool
     */
    public function update(int $id, array $data): bool;

    /**
     * Elimina un movimiento.
     *
     * @param  int  $id
     * @return bool
     */
    public function delete(int $id): bool;
}

