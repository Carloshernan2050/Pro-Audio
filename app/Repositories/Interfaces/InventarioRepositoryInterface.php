<?php

namespace App\Repositories\Interfaces;

use App\Models\Inventario;
use Illuminate\Database\Eloquent\Collection;

interface InventarioRepositoryInterface
{
    /**
     * Obtiene todos los inventarios.
     *
     * @return Collection
     */
    public function all(): Collection;

    /**
     * Busca un inventario por ID.
     *
     * @param  int  $id
     * @return Inventario
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function find(int $id): Inventario;

    /**
     * Crea un nuevo inventario.
     *
     * @param  array  $data
     * @return Inventario
     */
    public function create(array $data): Inventario;

    /**
     * Actualiza un inventario existente.
     *
     * @param  int  $id
     * @param  array  $data
     * @return bool
     */
    public function update(int $id, array $data): bool;

    /**
     * Elimina un inventario.
     *
     * @param  int  $id
     * @return bool
     */
    public function delete(int $id): bool;

    /**
     * Incrementa el stock de un inventario.
     *
     * @param  int  $id
     * @param  int  $cantidad
     * @return bool
     */
    public function incrementStock(int $id, int $cantidad): bool;

    /**
     * Decrementa el stock de un inventario.
     *
     * @param  int  $id
     * @param  int  $cantidad
     * @return bool
     */
    public function decrementStock(int $id, int $cantidad): bool;
}

