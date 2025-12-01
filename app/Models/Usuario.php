<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Traits\HasRoles;

class Usuario extends Model
{
    use HasFactory, HasRoles;

    /**
     * Guard por defecto para spatie/permission
     */
    protected string $guardName = 'web';

    /**
     * Get the guard name for spatie/permission
     */
    public function guardName(): string
    {
        return $this->guardName;
    }

    protected $table = 'personas';

    protected $fillable = [
        'primer_nombre',
        'segundo_nombre',
        'primer_apellido',
        'segundo_apellido',
        'correo',
        'telefono',
        'direccion',
        'contrasena',
        'fecha_registro',
        'estado',
        'foto_perfil',
    ];
}
