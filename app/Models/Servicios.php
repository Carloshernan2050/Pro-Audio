<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Servicios extends Model
{
    use HasFactory;

    protected $table = 'servicios';

    public $timestamps = false; // 👈 Desactiva created_at y updated_at

    protected $fillable = [
        'nombre_servicio'
    ];
}
