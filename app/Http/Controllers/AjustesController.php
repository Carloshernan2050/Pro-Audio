<?php

namespace App\Http\Controllers;

use App\Models\Servicios;
use App\Models\Cotizacion;

class AjustesController extends Controller
{
    public function index()
    {
        $servicios = Servicios::all();
        
        // Cargar cotizaciones con relaciones para el historial
        $cotizaciones = Cotizacion::with(['persona', 'subServicio.servicio'])
            ->orderBy('fecha_cotizacion', 'desc')
            ->get();
        
        return view('usuarios.ajustes', compact('servicios', 'cotizaciones'));
    }
}