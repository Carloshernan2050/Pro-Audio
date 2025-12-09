<?php

namespace App\Repositories;

use App\Models\Inventario;
use App\Repositories\Interfaces\InventarioRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class InventarioRepository implements InventarioRepositoryInterface
{
    public function all(): Collection
    {
        return Inventario::all();
    }

    public function find(int $id): Inventario
    {
        return Inventario::findOrFail($id);
    }

    public function create(array $data): Inventario
    {
        return Inventario::create($data);
    }

    public function update(int $id, array $data): bool
    {
        $inventario = Inventario::findOrFail($id);

        return $inventario->update($data);
    }

    public function delete(int $id): bool
    {
        $inventario = Inventario::findOrFail($id);

        return $inventario->delete();
    }

    public function incrementStock(int $id, int $cantidad): bool
    {
        return Inventario::where('id', $id)->increment('stock', $cantidad) > 0;
    }

    public function decrementStock(int $id, int $cantidad): bool
    {
        return Inventario::where('id', $id)->decrement('stock', $cantidad) > 0;
    }
}

