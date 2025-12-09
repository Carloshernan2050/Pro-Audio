<?php

namespace App\Services;

use App\Models\Servicios;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Servicio para generar archivos Blade dinámicamente.
 * Aplica Single Responsibility Principle (SRP).
 */
class BladeGeneratorService
{
    /**
     * Genera automáticamente el archivo blade para un servicio.
     *
     * @param  Servicios  $servicio
     * @return void
     * @throws \Exception
     */
    public function generar(Servicios $servicio): void
    {
        $nombreVista = Str::slug($servicio->nombre_servicio, '_');
        $nombreServicio = $servicio->nombre_servicio;
        $descripcion = $servicio->descripcion ?? 'Servicios profesionales de alta calidad para tus eventos.';

        $rutaVista = resource_path("views/usuarios/{$nombreVista}.blade.php");
        $directorioVista = resource_path('views/usuarios');

        // Asegurar que el directorio existe
        if (! File::exists($directorioVista)) {
            File::makeDirectory($directorioVista, 0755, true);
        }

        // Usar una plantilla existente como base y personalizarla
        $plantillaBase = resource_path('views/usuarios/animacion.blade.php');

        if (File::exists($plantillaBase)) {
            // Leer la plantilla base
            $contenidoBlade = File::get($plantillaBase);

            // Reemplazar los valores específicos
            $contenidoBlade = str_replace('PRO AUDIO - Animación', 'PRO AUDIO - '.e($nombreServicio), $contenidoBlade);
            $contenidoBlade = str_replace('Animación de Eventos', e($nombreServicio), $contenidoBlade);
            $contenidoBlade = str_replace('Personal capacitado y sistemas de última generación para crear el ambiente perfecto en tu evento.', e($descripcion), $contenidoBlade);
            $contenidoBlade = str_replace('/images/animacion/', '/images/'.e($nombreVista).'/', $contenidoBlade);
            $contenidoBlade = str_replace('para animación', 'para '.e($nombreServicio), $contenidoBlade);
        } else {
            // Si no existe la plantilla, crear una desde cero
            $contenidoBlade = $this->crearContenidoBladeDesdeCero($nombreServicio, $descripcion, $nombreVista);
        }

        File::put($rutaVista, $contenidoBlade);
    }

    /**
     * Actualiza solo la descripción en el blade existente.
     *
     * @param  Servicios  $servicio
     * @return void
     */
    public function actualizarDescripcion(Servicios $servicio): void
    {
        $nombreVista = Str::slug($servicio->nombre_servicio, '_');
        $rutaVista = resource_path("views/usuarios/{$nombreVista}.blade.php");

        if (File::exists($rutaVista)) {
            $contenido = File::get($rutaVista);
            $descripcion = $servicio->descripcion ?? 'Servicios profesionales de alta calidad para tus eventos.';

            // Reemplazar la descripción en el blade usando regex más específico
            $contenido = preg_replace(
                '/<p class="page-subtitle">[^<]*<\/p>/',
                '<p class="page-subtitle">'.e($descripcion).'</p>',
                $contenido
            );

            File::put($rutaVista, $contenido);
        }
    }

    /**
     * Elimina el archivo blade asociado.
     *
     * @param  string  $nombreVista
     * @return void
     */
    public function eliminar(string $nombreVista): void
    {
        $rutaVista = resource_path("views/usuarios/{$nombreVista}.blade.php");
        if (File::exists($rutaVista)) {
            File::delete($rutaVista);
        }
    }

    /**
     * Crea el contenido del blade desde cero usando la misma estructura de las vistas existentes.
     *
     * @param  string  $nombreServicio
     * @param  string  $descripcion
     * @param  string  $nombreVista
     * @return string
     */
    private function crearContenidoBladeDesdeCero(string $nombreServicio, string $descripcion, string $nombreVista): string
    {
        $contenido = <<<'BLADE'
@extends('layouts.app')

@section('title', '{NOMBRE_SERVICIO}')

@section('content')
       <main class="main-content">
            <h2 class="page-title">{NOMBRE_SERVICIO}</h2>
            <p class="page-subtitle">{DESCRIPCION}</p>

            <section class="productos-servicio">
                <div class="productos-grid">
                    @php
                        $rolesSesion = session('roles');
                        $rolesSesion = is_array($rolesSesion) ? $rolesSesion : array_filter([$rolesSesion]);
                        $puedeVerPrecios = count(array_intersect($rolesSesion, ['Superadmin', 'Admin', 'Usuario'])) > 0;
                    @endphp
                    @forelse($subServicios as $subServicio)
                        <div class="producto-item">
                            @php
                                $nombreImagen = strtolower(str_replace(' ', '_', $subServicio->nombre)) . '.jpg';
                                $rutaImagen = public_path('images/{NOMBRE_VISTA}/' . $nombreImagen);
                                $existeImagen = file_exists($rutaImagen);
                            @endphp
                            @if($existeImagen)
                                <img src="/images/{NOMBRE_VISTA}/{{ $nombreImagen }}"
                                     alt="{{ $subServicio->nombre }}"
                                     class="producto-imagen">
                            @else
                                <div class="producto-imagen-placeholder">
                                    <i class="fas fa-image"></i>
                                </div>
                            @endif
                            <h4 class="producto-nombre">{{ $subServicio->nombre }}</h4>
                            @if($puedeVerPrecios && $subServicio->precio)
                                <p style="color: #2563eb; font-weight: bold; font-size: 1.1em; margin: 8px 0;">
                                    ${{ number_format($subServicio->precio, 0, ',', '.') }}
                                </p>
                            @endif
                            @if($subServicio->descripcion)
                                <p class="producto-descripcion">{{ $subServicio->descripcion }}</p>
                            @endif
                        </div>
                    @empty
                        <div class="no-services">
                            <p>No hay sub-servicios disponibles para {NOMBRE_SERVICIO} en este momento.</p>
                        </div>
                    @endforelse
                </div>
            </section>
        </main>
@endsection
BLADE;

        // Reemplazar los placeholders
        $contenido = str_replace('{NOMBRE_SERVICIO}', e($nombreServicio), $contenido);
        $contenido = str_replace('{DESCRIPCION}', e($descripcion), $contenido);
        $contenido = str_replace('{NOMBRE_VISTA}', e($nombreVista), $contenido);

        return $contenido;
    }
}

