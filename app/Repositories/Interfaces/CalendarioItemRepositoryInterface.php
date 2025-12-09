<?php

namespace App\Repositories\Interfaces;

use App\Models\CalendarioItem;
use Illuminate\Database\Eloquent\Collection;

interface CalendarioItemRepositoryInterface
{
    /**
     * Crea un nuevo item de calendario.
     *
     * @param  array  $data
     * @return CalendarioItem
     */
    public function create(array $data): CalendarioItem;

    /**
     * Elimina todos los items de un calendario.
     *
     * @param  int  $calendarioId
     * @return bool
     */
    public function deleteByCalendarioId(int $calendarioId): bool;

    /**
     * Obtiene todos los items de un calendario.
     *
     * @param  int  $calendarioId
     * @return Collection
     */
    public function getByCalendarioId(int $calendarioId): Collection;
}

