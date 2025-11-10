<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reserva extends Model
{
    use HasFactory;

    protected $table = 'reservas';

    protected $fillable = [
        'personas_id',
        'servicio',
        'fecha_inicio',
        'fecha_fin',
        'descripcion_evento',
        'cantidad_total',
        'estado',
        'meta',
        'calendario_id',
    ];

    protected $casts = [
        'fecha_inicio' => 'datetime',
        'fecha_fin' => 'datetime',
        'meta' => 'array',
    ];

    public function persona()
    {
        return $this->belongsTo(Usuario::class, 'personas_id');
    }

    public function items()
    {
        return $this->hasMany(ReservaItem::class);
    }
}

