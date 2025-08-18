<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Calendario extends Model
{
    use HasFactory;

    protected $table = 'calendario';

    protected $fillable = [
        'personas_id',
        'movimientos_inventario_id',
        'fecha',
        'descripcion_evento',
        'fecha_inicio',
        'fecha_fin',
        'evento'
    ];

    public function usuario()
    {
        return $this->belongsTo(\App\Models\Usuario::class, 'personas_id');
    }
}
