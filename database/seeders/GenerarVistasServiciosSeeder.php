<?php

namespace Database\Seeders;

use App\Models\Servicios;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class GenerarVistasServiciosSeeder extends Seeder
{
    /**
     * Genera vistas Blade para todos los servicios existentes que no tengan vista
     * Este seeder se ejecuta después de las migraciones para asegurar que todas las vistas existan
     */
    public function run(): void
    {
        $servicios = Servicios::all();

        foreach ($servicios as $servicio) {
            $this->generarVistaServicio($servicio);
        }
    }

    /**
     * Genera la vista Blade para un servicio si no existe
     */
    private function generarVistaServicio(Servicios $servicio): void
    {
        $nombreVista = Str::slug($servicio->nombre_servicio, '_');
        $rutaVista = resource_path("views/usuarios/{$nombreVista}.blade.php");
        $directorioVista = resource_path('views/usuarios');

        // Asegurar que el directorio existe
        if (!File::exists($directorioVista)) {
            File::makeDirectory($directorioVista, 0755, true);
        }

        // Si la vista ya existe, no la sobrescribimos
        if (File::exists($rutaVista)) {
            return;
        }

        // Generar el contenido de la vista basándose en la plantilla estándar
        $contenidoBlade = $this->generarContenidoVista($servicio->nombre_servicio);

        File::put($rutaVista, $contenidoBlade);
    }

    /**
     * Genera el contenido de la vista Blade para un servicio
     */
    private function generarContenidoVista(string $nombreServicio): string
    {
        $nombreServicioEscapado = e($nombreServicio);
        
        return <<<BLADE
@extends('layouts.app')

@section('title', '{$nombreServicioEscapado}')

@section('content')
       <main class="main-content">
            <h2 class="page-title">{$nombreServicioEscapado}</h2>
            <p class="page-subtitle"></p>
            
            <section class="productos-servicio">
                <div class="productos-grid">
                    @forelse(\$subServicios as \$subServicio)
                        <div class="producto-item">
                            @if(\$subServicio->imagen)
                                <img src="{{ asset('storage/subservicios/' . \$subServicio->imagen) }}"
                                     alt="{{ \$subServicio->nombre }}"
                                     class="producto-imagen">
                            @else
                                <div class="producto-imagen-placeholder">
                                    <i class="fas fa-image"></i>
                                </div>
                            @endif
                            <h4 class="producto-nombre">{{ \$subServicio->nombre }}</h4>
                            @if(\$subServicio->descripcion)
                                <p class="producto-descripcion">{{ \$subServicio->descripcion }}</p>
                            @endif
                        </div>
                    @empty
                        <div class="no-services">
                            <p>No hay sub-servicios disponibles para {$nombreServicioEscapado} en este momento.</p>
                        </div>
                    @endforelse
                </div>
            </section>
        </main>
@endsection
BLADE;
    }
}

