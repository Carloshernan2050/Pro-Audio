<?php

namespace App\Repositories\Interfaces;

use App\Models\Servicios;
use Illuminate\Database\Eloquent\Collection;

interface ServicioRepositoryInterface
{
    /**
     * Obtiene todos los servicios ordenados por ID descendente.
     *
     * @return Collection
     */
    public function all(): Collection;

    /**
     * Busca un servicio por ID.
     *
     * @param  int  $id
     * @return Servicios
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function find(int $id): Servicios;

    /**
     * Busca un servicio por ID con sus relaciones.
     *
     * @param  int  $id
     * @return Servicios
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findWithRelations(int $id): Servicios;

    /**
     * Crea un nuevo servicio.
     *
     * @param  array  $data
     * @return Servicios
     */
    public function create(array $data): Servicios;

    /**
     * Actualiza un servicio existente.
     *
     * @param  int  $id
     * @param  array  $data
     * @return bool
     */
    public function update(int $id, array $data): bool;

    /**
     * Elimina un servicio.
     *
     * @param  int  $id
     * @return bool
     */
    public function delete(int $id): bool;

    /**
     * Busca un servicio por nombre.
     *
     * @param  string  $nombre
     * @return Servicios|null
     */
    public function findByNombre(string $nombre): ?Servicios;

    /**
     * Obtiene solo los nombres de los servicios.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function obtenerNombres(): \Illuminate\Database\Eloquent\Collection;
}

