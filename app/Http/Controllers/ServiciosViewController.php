<?php

namespace App\Http\Controllers;

use App\Models\Servicios;

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

    /**
     * Mostrar página dinámica de servicio por slug
     */
    public function servicioPorSlug($slug)
    {
        // Buscar servicio por nombre normalizado
        $servicios = Servicios::all();
        $servicio = $servicios->first(function ($s) use ($slug) {
            return \Illuminate\Support\Str::slug($s->nombre_servicio, '_') === $slug;
        });

        if (! $servicio) {
            abort(404, 'Servicio no encontrado');
        }

        $subServicios = $servicio->subServicios;
        $nombreVista = \Illuminate\Support\Str::slug($servicio->nombre_servicio, '_');

        // Verificar si existe la vista
        $rutaVista = resource_path("views/usuarios/{$nombreVista}.blade.php");
        if (! file_exists($rutaVista)) {
            abort(404, 'Vista del servicio no encontrada');
        }

        return view("usuarios.{$nombreVista}", compact('subServicios', 'servicio'));
    }
}
