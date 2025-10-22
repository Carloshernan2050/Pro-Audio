<?php

namespace App\Http\Controllers;

use App\Models\Servicios;
use App\Models\SubServicios;

class ServiciosViewController extends Controller
{
    /**
     * Mostrar página de alquiler con sub-servicios
     */
    public function alquiler()
    {
        $servicio = Servicios::where('nombre_servicio', 'Alquiler')->first();
        $subServicios = $servicio ? $servicio->subServicios : collect();
        
        return view('usuarios.alquiler', compact('subServicios'));
    }

    /**
     * Mostrar página de animación con sub-servicios
     */
    public function animacion()
    {
        $servicio = Servicios::where('nombre_servicio', 'Animación')->first();
        $subServicios = $servicio ? $servicio->subServicios : collect();
        
        return view('usuarios.animacion', compact('subServicios'));
    }

    /**
     * Mostrar página de publicidad con sub-servicios
     */
    public function publicidad()
    {
        $servicio = Servicios::where('nombre_servicio', 'Publicidad')->first();
        $subServicios = $servicio ? $servicio->subServicios : collect();
        
        return view('usuarios.publicidad', compact('subServicios'));
    }
}
