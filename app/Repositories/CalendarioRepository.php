<?php

namespace App\Repositories;

use App\Models\Calendario;
use App\Repositories\Interfaces\CalendarioRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class CalendarioRepository implements CalendarioRepositoryInterface
{
    public function allWithRelations(): Collection
    {
        return Calendario::with(['items', 'reserva'])
            ->orderBy('fecha', 'desc')
            ->orderBy('id', 'desc')
            ->get();
    }

    public function find(int $id): Calendario
    {
        return Calendario::findOrFail($id);
    }

    public function findWithRelations(int $id): Calendario
    {
        return Calendario::with(['items.movimientoInventario', 'reserva'])->findOrFail($id);
    }

    public function create(array $data): Calendario
    {
        return Calendario::create($data);
    }

    public function update(int $id, array $data): bool
    {
        $calendario = Calendario::findOrFail($id);

        return $calendario->update($data);
    }

    public function delete(int $id): bool
    {
        $calendario = Calendario::findOrFail($id);

        return $calendario->delete();
    }
}

