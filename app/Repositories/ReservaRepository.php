<?php

namespace App\Repositories;

use App\Models\Reserva;
use App\Repositories\Interfaces\ReservaRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class ReservaRepository implements ReservaRepositoryInterface
{
    public function allWithRelations(): Collection
    {
        return Reserva::with(['items.inventario'])
            ->orderBy('estado')
            ->orderByDesc('created_at')
            ->get();
    }

    public function getPendientes(): Collection
    {
        return Reserva::with(['items.inventario'])
            ->where('estado', 'pendiente')
            ->orderByDesc('created_at')
            ->get();
    }

    public function find(int $id): Reserva
    {
        return Reserva::findOrFail($id);
    }

    public function findWithRelations(int $id): Reserva
    {
        return Reserva::with(['items.inventario'])->findOrFail($id);
    }

    public function findByCalendarioId(int $calendarioId): ?Reserva
    {
        return Reserva::where('calendario_id', $calendarioId)->first();
    }

    public function create(array $data): Reserva
    {
        return Reserva::create($data);
    }

    public function update(int $id, array $data): bool
    {
        $reserva = Reserva::findOrFail($id);

        return $reserva->update($data);
    }

    public function delete(int $id): bool
    {
        $reserva = Reserva::findOrFail($id);

        return $reserva->delete();
    }
}

