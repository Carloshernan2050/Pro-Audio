@extends('layouts.app')

@section('title', 'Animación')

@section('content')
       <main class="main-content">
            <h2 class="page-title">H</h2>
            <p class="page-subtitle"></p>
            
            <section class="productos-servicio">
                <div class="productos-grid">
                    @forelse($subServicios as $subServicio)
                        <div class="producto-item">
                            @php
                                $nombreImagen = strtolower(str_replace(' ', '_', $subServicio->nombre)) . '.jpg';
                                $rutaImagen = public_path('images/animacion/' . $nombreImagen);
                                $existeImagen = file_exists($rutaImagen);
                            @endphp
                            @if($existeImagen)
                                <img src="/images/h/{{ $nombreImagen }}" 
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
                            <p>No hay sub-servicios disponibles para H en este momento.</p>
                        </div>
                    @endforelse
                </div>
            </section>
        </main>
@endsection
