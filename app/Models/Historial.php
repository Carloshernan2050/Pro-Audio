<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Historial extends Model
{
    protected $table = 'historial';

    public $timestamps = false;

    protected $fillable = [
        'calendario_id',
        'reserva_id',
        'accion',
        'confirmado_en',
        'observaciones',
    ];

    protected $casts = [
        'confirmado_en' => 'datetime',
    ];

    public function reserva()
    {
        return $this->belongsTo(Reserva::class, 'reserva_id');
    }
}


