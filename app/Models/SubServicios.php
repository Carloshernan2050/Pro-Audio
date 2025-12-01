<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubServicios extends Model
{
    use HasFactory;

    protected $table = 'sub_servicios';

    public $timestamps = false; // 👈 Desactiva created_at y updated_at

    protected $fillable = [
        'servicios_id',
        'nombre',
        'descripcion',
        'precio',
        'imagen',
    ];

    // Relación con el modelo Servicios
    public function servicio()
    {
        return $this->belongsTo(Servicios::class, 'servicios_id');
    }
}
