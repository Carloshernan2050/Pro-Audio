<?php

namespace App\Repositories;

use App\Models\Historial;
use App\Repositories\Interfaces\HistorialRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class HistorialRepository implements HistorialRepositoryInterface
{
    public function allWithRelations(): Collection
    {
        return Historial::with(['reserva'])->get();
    }

    public function getReservasConfirmadas(): Collection
    {
        return Historial::with(['reserva.persona'])
            ->whereNotNull('confirmado_en')
            ->whereNotNull('reserva_id')
            ->orderBy('confirmado_en', 'desc')
            ->orderBy('id', 'desc')
            ->get();
    }

    public function create(array $data): Historial
    {
        return Historial::create($data);
    }

    public function update(int $id, array $data): bool
    {
        $historial = Historial::findOrFail($id);

        return $historial->update($data);
    }
}

