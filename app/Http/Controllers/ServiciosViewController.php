<?php

namespace App\Http\Controllers;

use App\Repositories\Interfaces\ServicioRepositoryInterface;

class ServiciosViewController extends Controller
{
    private ServicioRepositoryInterface $servicioRepository;

    public function __construct(ServicioRepositoryInterface $servicioRepository)
    {
        $this->servicioRepository = $servicioRepository;
    }
    /**
     * Mostrar página de alquiler con sub-servicios
     */
    public function alquiler()
    {
        // Usar repositorio en lugar de modelo directo (DIP)
        $servicio = $this->servicioRepository->findByNombre('Alquiler');
        $subServicios = $servicio ? $servicio->subServicios : collect();

        return view('usuarios.alquiler', compact('subServicios'));
    }

    /**
     * Mostrar página de animación con sub-servicios
     */
    public function animacion()
    {
        // Usar repositorio en lugar de modelo directo (DIP)
        $servicio = $this->servicioRepository->findByNombre('Animación');
        $subServicios = $servicio ? $servicio->subServicios : collect();

        return view('usuarios.animacion', compact('subServicios'));
    }

    /**
     * Mostrar página de publicidad con sub-servicios
     */
    public function publicidad()
    {
        // Usar repositorio en lugar de modelo directo (DIP)
        $servicio = $this->servicioRepository->findByNombre('Publicidad');
        $subServicios = $servicio ? $servicio->subServicios : collect();

        return view('usuarios.publicidad', compact('subServicios'));
    }

    /**
     * Mostrar página dinámica de servicio por slug
     */
    public function servicioPorSlug($slug)
    {
        // Usar repositorio en lugar de modelo directo (DIP)
        $servicios = $this->servicioRepository->all();
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
