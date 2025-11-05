<?php

namespace App\Http\Controllers;

use App\Models\Servicios;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ServiciosController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $servicios = Servicios::orderBy('id', 'desc')->get();
            
            // Debug: verificar que hay servicios
            if ($servicios->isEmpty()) {
                \Log::info('ServiciosController@index: No hay servicios en la base de datos');
            } else {
                \Log::info('ServiciosController@index: Se encontraron ' . $servicios->count() . ' servicios');
            }
            
            return view('usuarios.ajustes', compact('servicios'));
        } catch (\Exception $e) {
            \Log::error('ServiciosController@index Error: ' . $e->getMessage());
            return view('usuarios.ajustes', ['servicios' => collect()])
                ->with('error', 'Error al cargar los servicios: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('usuarios.ajustes');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre_servicio' => 'required|string|max:100|unique:servicios,nombre_servicio',
            'descripcion' => 'nullable|string|max:500',
        ]);

        try {
            // PASO 1: Primero guardar en la base de datos
            $servicio = Servicios::create([
                'nombre_servicio' => $request->nombre_servicio,
                'descripcion' => $request->descripcion ?? '',
            ]);

            // Verificar que se guardó correctamente
            if (!$servicio || !$servicio->id) {
                throw new \Exception('Error: El servicio no se guardó correctamente en la base de datos.');
            }

            // PASO 2: Después de guardar en DB, generar el archivo blade
            try {
                $this->generarBlade($servicio);
            } catch (\Exception $bladeError) {
                // Si falla la generación del blade, el servicio ya está guardado en DB
                // Solo mostramos un mensaje de advertencia
                return redirect()->route('servicios.index')
                    ->with('success', 'Servicio creado exitosamente en la base de datos.')
                    ->with('warning', 'Advertencia: No se pudo generar la vista automáticamente. ' . $bladeError->getMessage());
            }

            return redirect()->route('servicios.index')
                ->with('success', 'Servicio creado exitosamente y vista generada automáticamente.');
        } catch (\Exception $e) {
            return redirect()->route('servicios.index')
                ->with('error', 'Error al crear el servicio: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $servicio = Servicios::findOrFail($id);
        $subServicios = $servicio->subServicios;
        
        // Nombre normalizado para la vista
        $nombreVista = Str::slug($servicio->nombre_servicio, '_');
        
        return view("usuarios.{$nombreVista}", compact('subServicios', 'servicio'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $servicio = Servicios::findOrFail($id);
        return view('usuarios.ajustes', compact('servicio'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $servicio = Servicios::findOrFail($id);
        
        $request->validate([
            'nombre_servicio' => 'required|string|max:100|unique:servicios,nombre_servicio,' . $id,
            'descripcion' => 'nullable|string|max:500',
        ]);

        try {
            $nombreAnterior = $servicio->nombre_servicio;
            
            $servicio->update([
                'nombre_servicio' => $request->nombre_servicio,
                'descripcion' => $request->descripcion ?? '',
            ]);

            // Si cambió el nombre, regenerar el blade con el nuevo nombre
            if ($nombreAnterior !== $request->nombre_servicio) {
                $this->eliminarBladeAnterior($nombreAnterior);
                $this->generarBlade($servicio);
            } else {
                // Si solo cambió la descripción, actualizar el blade existente
                $this->actualizarDescripcionBlade($servicio);
            }

            return redirect()->route('servicios.index')
                ->with('success', 'Servicio actualizado exitosamente.');
        } catch (\Exception $e) {
            return redirect()->route('servicios.index')
                ->with('error', 'Error al actualizar el servicio: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            $servicio = Servicios::findOrFail($id);
            $nombreVista = Str::slug($servicio->nombre_servicio, '_');
            
            // Eliminar el servicio
            $servicio->delete();
            
            // Eliminar el archivo blade asociado
            $this->eliminarBlade($nombreVista);

            return redirect()->route('servicios.index')
                ->with('success', 'Servicio eliminado exitosamente.');
        } catch (\Exception $e) {
            return redirect()->route('servicios.index')
                ->with('error', 'Error al eliminar el servicio: ' . $e->getMessage());
        }
    }

    /**
     * Genera automáticamente el archivo blade para un servicio
     */
    private function generarBlade($servicio)
    {
        $nombreVista = Str::slug($servicio->nombre_servicio, '_');
        $nombreServicio = $servicio->nombre_servicio;
        $descripcion = $servicio->descripcion ?? 'Servicios profesionales de alta calidad para tus eventos.';
        
        $rutaVista = resource_path("views/usuarios/{$nombreVista}.blade.php");
        $directorioVista = resource_path('views/usuarios');
        
        // Asegurar que el directorio existe
        if (!File::exists($directorioVista)) {
            File::makeDirectory($directorioVista, 0755, true);
        }

        // Usar una plantilla existente como base y personalizarla
        $plantillaBase = resource_path('views/usuarios/animacion.blade.php');
        
        if (File::exists($plantillaBase)) {
            // Leer la plantilla base
            $contenidoBlade = File::get($plantillaBase);
            
            // Reemplazar los valores específicos
            $contenidoBlade = str_replace('PRO AUDIO - Animación', 'PRO AUDIO - ' . e($nombreServicio), $contenidoBlade);
            $contenidoBlade = str_replace('Animación de Eventos', e($nombreServicio), $contenidoBlade);
            $contenidoBlade = str_replace('Personal capacitado y sistemas de última generación para crear el ambiente perfecto en tu evento.', e($descripcion), $contenidoBlade);
            $contenidoBlade = str_replace('/images/animacion/', '/images/' . e($nombreVista) . '/', $contenidoBlade);
            $contenidoBlade = str_replace('para animación', 'para ' . e($nombreServicio), $contenidoBlade);
        } else {
            // Si no existe la plantilla, crear una desde cero
            $contenidoBlade = $this->crearContenidoBladeDesdeCero($nombreServicio, $descripcion, $nombreVista);
        }

        File::put($rutaVista, $contenidoBlade);
    }

    /**
     * Actualiza solo la descripción en el blade existente
     */
    private function actualizarDescripcionBlade($servicio)
    {
        $nombreVista = Str::slug($servicio->nombre_servicio, '_');
        $rutaVista = resource_path("views/usuarios/{$nombreVista}.blade.php");
        
        if (File::exists($rutaVista)) {
            $contenido = File::get($rutaVista);
            $descripcion = $servicio->descripcion ?? 'Servicios profesionales de alta calidad para tus eventos.';
            
            // Reemplazar la descripción en el blade usando regex más específico
            $contenido = preg_replace(
                '/<p class="page-subtitle">[^<]*<\/p>/',
                '<p class="page-subtitle">' . e($descripcion) . '</p>',
                $contenido
            );
            
            File::put($rutaVista, $contenido);
        }
    }

    /**
     * Elimina el archivo blade asociado
     */
    private function eliminarBlade($nombreVista)
    {
        $rutaVista = resource_path("views/usuarios/{$nombreVista}.blade.php");
        if (File::exists($rutaVista)) {
            File::delete($rutaVista);
        }
    }

    /**
     * Elimina el blade con el nombre anterior
     */
    private function eliminarBladeAnterior($nombreAnterior)
    {
        $nombreVistaAnterior = Str::slug($nombreAnterior, '_');
        $this->eliminarBlade($nombreVistaAnterior);
    }

    /**
     * Crea el contenido del blade desde cero usando la misma estructura de las vistas existentes
     */
    private function crearContenidoBladeDesdeCero($nombreServicio, $descripcion, $nombreVista)
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
                            @if($subServicio->precio)
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
