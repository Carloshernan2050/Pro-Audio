<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MovimientosInventario extends Model
{
    use HasFactory;

    protected $table = 'movimientos_inventario';

    public $timestamps = false; // 👈 Desactiva created_at y updated_at

    protected $fillable = [
        'inventario_id',
        'tipo_movimiento',
        'cantidad',
        'fecha_movimiento',
        'descripcion'
    ];

    // Relación con inventario
    public function inventario()
    {
        return $this->belongsTo(Inventario::class, 'inventario_id');
    }
}
