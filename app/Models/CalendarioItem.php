<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CalendarioItem extends Model
{
    use HasFactory;

    protected $table = 'calendario_items';

    protected $fillable = [
        'calendario_id',
        'movimientos_inventario_id',
        'cantidad',
    ];

    public function calendario()
    {
        return $this->belongsTo(Calendario::class);
    }

    public function movimientoInventario()
    {
        return $this->belongsTo(\App\Models\MovimientosInventario::class, 'movimientos_inventario_id');
    }
}
