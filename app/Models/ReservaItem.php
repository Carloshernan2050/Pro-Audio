<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReservaItem extends Model
{
    use HasFactory;

    protected $table = 'reserva_items';

    protected $fillable = [
        'reserva_id',
        'inventario_id',
        'cantidad',
    ];

    public function reserva()
    {
        return $this->belongsTo(Reserva::class);
    }

    public function inventario()
    {
        return $this->belongsTo(Inventario::class);
    }
}

