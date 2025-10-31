<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cotizacion extends Model
{
    use HasFactory;

    protected $table = 'cotizacion';

    public $timestamps = false;

    protected $fillable = [
        'personas_id',
        'sub_servicios_id',
        'monto',
        'fecha_cotizacion',
    ];

    protected $casts = [
        'fecha_cotizacion' => 'datetime',
        'monto' => 'decimal:2',
    ];

    /**
     * Relación con Personas (Usuario)
     */
    public function persona()
    {
        return $this->belongsTo(Usuario::class, 'personas_id');
    }

    /**
     * Relación con SubServicios
     */
    public function subServicio()
    {
        return $this->belongsTo(SubServicios::class, 'sub_servicios_id');
    }
}
