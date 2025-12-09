<?php

namespace App\Repositories;

use App\Models\MovimientosInventario;
use App\Repositories\Interfaces\MovimientoInventarioRepositoryInterface;

class MovimientoInventarioRepository implements MovimientoInventarioRepositoryInterface
{
    public function create(array $data): MovimientosInventario
    {
        return MovimientosInventario::create($data);
    }

    public function find(int $id): MovimientosInventario
    {
        return MovimientosInventario::findOrFail($id);
    }

    public function update(int $id, array $data): bool
    {
        $movimiento = MovimientosInventario::findOrFail($id);

        return $movimiento->update($data);
    }

    public function delete(int $id): bool
    {
        $movimiento = MovimientosInventario::findOrFail($id);

        return $movimiento->delete();
    }
}

