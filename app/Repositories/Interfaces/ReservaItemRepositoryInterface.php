<?php

namespace App\Repositories\Interfaces;

use App\Models\ReservaItem;
use Illuminate\Database\Eloquent\Collection;

interface ReservaItemRepositoryInterface
{
    /**
     * Crea un nuevo item de reserva.
     *
     * @param  array  $data
     * @return ReservaItem
     */
    public function create(array $data): ReservaItem;

    /**
     * Elimina todos los items de una reserva.
     *
     * @param  int  $reservaId
     * @return bool
     */
    public function deleteByReservaId(int $reservaId): bool;

    /**
     * Obtiene todos los items de una reserva.
     *
     * @param  int  $reservaId
     * @return Collection
     */
    public function getByReservaId(int $reservaId): Collection;
}

