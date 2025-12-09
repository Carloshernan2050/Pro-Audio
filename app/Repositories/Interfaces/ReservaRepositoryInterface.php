<?php

namespace App\Repositories\Interfaces;

use App\Models\Reserva;
use Illuminate\Database\Eloquent\Collection;

interface ReservaRepositoryInterface
{
    /**
     * Obtiene todas las reservas con sus relaciones.
     *
     * @return Collection
     */
    public function allWithRelations(): Collection;

    /**
     * Obtiene reservas pendientes con sus relaciones.
     *
     * @return Collection
     */
    public function getPendientes(): Collection;

    /**
     * Busca una reserva por ID.
     *
     * @param  int  $id
     * @return Reserva
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function find(int $id): Reserva;

    /**
     * Busca una reserva por ID con sus relaciones.
     *
     * @param  int  $id
     * @return Reserva
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findWithRelations(int $id): Reserva;

    /**
     * Busca una reserva por calendario_id.
     *
     * @param  int  $calendarioId
     * @return Reserva|null
     */
    public function findByCalendarioId(int $calendarioId): ?Reserva;

    /**
     * Crea una nueva reserva.
     *
     * @param  array  $data
     * @return Reserva
     */
    public function create(array $data): Reserva;

    /**
     * Actualiza una reserva existente.
     *
     * @param  int  $id
     * @param  array  $data
     * @return bool
     */
    public function update(int $id, array $data): bool;

    /**
     * Elimina una reserva.
     *
     * @param  int  $id
     * @return bool
     */
    public function delete(int $id): bool;
}

