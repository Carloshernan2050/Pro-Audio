<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Servicios extends Model
{
    use HasFactory;

    /**
     * Nombre de la tabla en la base de datos.
     */
    protected $table = 'servicios';

    /**
     * Desactiva los timestamps automáticos (created_at, updated_at).
     */
    public $timestamps = false;

    /**
     * Campos que se pueden asignar masivamente.
     */
    protected $fillable = [
        'nombre_servicio',
        'descripcion',
        'icono',
    ];

    /**
     * Relación: Un servicio tiene muchos subservicios.
     * Clave foránea en la tabla subservicios: servicios_id
     */
    public function subServicios()
    {
        return $this->hasMany(SubServicios::class, 'servicios_id');
    }

    /**
     * Método auxiliar para crear o devolver un servicio sin duplicarlo.
     * Ideal si llamas desde seeders o controladores.
     */
    public static function crearUnico($nombre)
    {
        return self::firstOrCreate(['nombre_servicio' => $nombre]);
    }
}
