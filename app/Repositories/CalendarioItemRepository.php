<?php

namespace App\Repositories;

use App\Models\CalendarioItem;
use App\Repositories\Interfaces\CalendarioItemRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class CalendarioItemRepository implements CalendarioItemRepositoryInterface
{
    public function create(array $data): CalendarioItem
    {
        return CalendarioItem::create($data);
    }

    public function deleteByCalendarioId(int $calendarioId): bool
    {
        return CalendarioItem::where('calendario_id', $calendarioId)->delete() > 0;
    }

    public function getByCalendarioId(int $calendarioId): Collection
    {
        return CalendarioItem::where('calendario_id', $calendarioId)->get();
    }
}

