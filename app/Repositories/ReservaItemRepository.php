<?php

namespace App\Repositories;

use App\Models\ReservaItem;
use App\Repositories\Interfaces\ReservaItemRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class ReservaItemRepository implements ReservaItemRepositoryInterface
{
    public function create(array $data): ReservaItem
    {
        return ReservaItem::create($data);
    }

    public function deleteByReservaId(int $reservaId): bool
    {
        return ReservaItem::where('reserva_id', $reservaId)->delete() > 0;
    }

    public function getByReservaId(int $reservaId): Collection
    {
        return ReservaItem::where('reserva_id', $reservaId)->get();
    }
}

