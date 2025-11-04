@extends('layouts.app')

@section('title', 'Alquiler')

{{-- Define la sección 'content' que se insertará en el @yield('content') del layout --}}
@section('content')
    <h2 class="page-title">Alquiler de Equipo de Sonido</h2>
    <p class="page-subtitle">Equipos profesionales de alta calidad para tus eventos. Disponibles por día.</p>
    
    <section class="productos-servicio">
        <div class="productos-grid">
            @forelse($subServicios as $subServicio)
                <div class="producto-item">
                    @php
                        $nombreImagen = strtolower(str_replace(' ', '_', $subServicio->nombre)) . '.jpg';
                        $rutaImagen = public_path('images/alquiler/' . $nombreImagen);
                        $existeImagen = file_exists($rutaImagen);
                    @endphp
                    @if($existeImagen)
                        <img src="/images/alquiler/{{ $nombreImagen }}" 
                                alt="{{ $subServicio->nombre }}" 
                                class="producto-imagen">
                    @else
                        <div class="producto-imagen-placeholder">
                            <i class="fas fa-image"></i>
                        </div>
                    @endif
                    <h4 class="producto-nombre">{{ $subServicio->nombre }}</h4>
                    @if($subServicio->descripcion)
                        <p class="producto-descripcion">{{ $subServicio->descripcion }}</p>
                    @endif
                </div>
            @empty
                <div class="no-services">
                    <p>No hay sub-servicios disponibles para alquiler en este momento.</p>
                </div>
            @endforelse
        </div>
    </section>
@endsection