@extends('layouts.app')

@section('title', 'Animación')

@section('content')
       <main class="main-content">
            <h2 class="page-title">Test Exception</h2>
            <p class="page-subtitle"></p>
            
            <section class="productos-servicio">
                <div class="productos-grid">
                    @forelse($subServicios as $subServicio)
                        <div class="producto-item">
                            @if($subServicio->imagen)
                                <img src="{{ asset('storage/subservicios/' . $subServicio->imagen) }}"
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
                            <p>No hay sub-servicios disponibles para Test Exception en este momento.</p>
                        </div>
                    @endforelse
                </div>
            </section>
        </main>
@endsection
