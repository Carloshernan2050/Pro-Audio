<?php

namespace App\Repositories;

use App\Models\Usuario;
use App\Repositories\Interfaces\UsuarioRepositoryInterface;

class UsuarioRepository implements UsuarioRepositoryInterface
{
    public function findByCorreo(string $correo): ?Usuario
    {
        return Usuario::where('correo', $correo)->first();
    }

    public function find(int $id): ?Usuario
    {
        return Usuario::find($id);
    }

    public function create(array $data): Usuario
    {
        return Usuario::create($data);
    }

    public function update(int $id, array $data): bool
    {
        $usuario = Usuario::find($id);
        if (! $usuario) {
            return false;
        }

        return $usuario->update($data);
    }
}


