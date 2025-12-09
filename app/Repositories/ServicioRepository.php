<?php

namespace App\Repositories;

use App\Models\Servicios;
use App\Repositories\Interfaces\ServicioRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class ServicioRepository implements ServicioRepositoryInterface
{
    public function all(): Collection
    {
        return Servicios::orderBy('id', 'desc')->get();
    }

    public function find(int $id): Servicios
    {
        return Servicios::findOrFail($id);
    }

    public function findWithRelations(int $id): Servicios
    {
        return Servicios::with('subServicios')->findOrFail($id);
    }

    public function create(array $data): Servicios
    {
        return Servicios::create($data);
    }

    public function update(int $id, array $data): bool
    {
        $servicio = Servicios::findOrFail($id);

        return $servicio->update($data);
    }

    public function delete(int $id): bool
    {
        $servicio = Servicios::findOrFail($id);

        return $servicio->delete();
    }

    public function findByNombre(string $nombre): ?Servicios
    {
        return Servicios::where('nombre_servicio', $nombre)->first();
    }

    public function obtenerNombres(): Collection
    {
        return Servicios::query()->select('nombre_servicio')->get();
    }
}

