@extends('layouts.app')

@section('title', 'Publicidad')

@section('content')
        <h2 class="page-title">Publicidad Sonora</h2>
        <p class="page-subtitle">Creamos audio que captura la atención y refuerza el mensaje de tu marca.</p>

        <section class="productos-servicio">
            <div class="productos-grid">
                @forelse($subServicios as $subServicio)
                    <div class="producto-item">
                        <img src="/images/publicidad/{{ strtolower(str_replace(' ', '_', $subServicio->nombre)) }}.jpg" 
                             alt="{{ $subServicio->nombre }}" 
                             class="producto-imagen"
                             onerror="this.src='/images/default.jpg'">
                        <h4 class="producto-nombre">{{ $subServicio->nombre }}</h4>
                        @if($subServicio->descripcion)
                            <p class="producto-descripcion">{{ $subServicio->descripcion }}</p>
                        @endif
                    </div>
                @empty
                    <div class="no-services">
                        <p>No hay sub-servicios disponibles para publicidad en este momento.</p>
                    </div>
                @endforelse
            </div>
        </section>
@endsection