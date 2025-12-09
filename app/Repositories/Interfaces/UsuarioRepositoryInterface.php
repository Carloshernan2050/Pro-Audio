<?php

namespace App\Repositories\Interfaces;

use App\Models\Usuario;

interface UsuarioRepositoryInterface
{
    /**
     * Busca un usuario por correo electrónico.
     *
     * @param  string  $correo
     * @return Usuario|null
     */
    public function findByCorreo(string $correo): ?Usuario;

    /**
     * Busca un usuario por ID.
     *
     * @param  int  $id
     * @return Usuario|null
     */
    public function find(int $id): ?Usuario;

    /**
     * Crea un nuevo usuario.
     *
     * @param  array  $data
     * @return Usuario
     */
    public function create(array $data): Usuario;

    /**
     * Actualiza un usuario existente.
     *
     * @param  int  $id
     * @param  array  $data
     * @return bool
     */
    public function update(int $id, array $data): bool;
}



