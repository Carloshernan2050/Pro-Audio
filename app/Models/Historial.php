<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Historial extends Model
{
    protected $table = 'historial';

    public $timestamps = false;

    protected $fillable = [
        'calendario_id',
    ];

    public function calendario()
    {
        return $this->belongsTo(Calendario::class, 'calendario_id');
    }
}


