<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inventario extends Model
{
    use HasFactory;

    protected $table = 'inventario';

    public $timestamps = false; // 👈 Desactiva created_at y updated_at

    protected $fillable = [
        'descripcion',
        'stock',
    ];

    // Relación con movimientos de inventario
    public function movimientos()
    {
        return $this->hasMany(MovimientosInventario::class, 'inventario_id');
    }
}
