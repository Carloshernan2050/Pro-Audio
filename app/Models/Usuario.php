<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Usuario extends Model
{
    use HasFactory;

    protected $table = 'personas';
    public $timestamps = false; 

    protected $fillable = [
    'primer_nombre',
    'segundo_nombre',
    'primer_apellido',
    'segundo_apellido',
    'correo',
    'telefono',
    'direccion',
    'fecha_registro',
    'estado'
    ];


}

