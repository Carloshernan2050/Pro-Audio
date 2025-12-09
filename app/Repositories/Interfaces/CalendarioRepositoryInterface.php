<?php

namespace App\Repositories\Interfaces;

use App\Models\Calendario;
use Illuminate\Database\Eloquent\Collection;

interface CalendarioRepositoryInterface
{
    /**
     * Obtiene todos los calendarios con sus relaciones.
     *
     * @return Collection
     */
    public function allWithRelations(): Collection;

    /**
     * Busca un calendario por ID.
     *
     * @param  int  $id
     * @return Calendario
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function find(int $id): Calendario;

    /**
     * Busca un calendario por ID con sus relaciones.
     *
     * @param  int  $id
     * @return Calendario
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findWithRelations(int $id): Calendario;

    /**
     * Crea un nuevo calendario.
     *
     * @param  array  $data
     * @return Calendario
     */
    public function create(array $data): Calendario;

    /**
     * Actualiza un calendario existente.
     *
     * @param  int  $id
     * @param  array  $data
     * @return bool
     */
    public function update(int $id, array $data): bool;

    /**
     * Elimina un calendario.
     *
     * @param  int  $id
     * @return bool
     */
    public function delete(int $id): bool;
}

