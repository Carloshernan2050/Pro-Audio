@extends('layouts.app')

@section('title', 'Servicio Sin Plantilla')

@section('content')
       <main class="main-content">
            <h2 class="page-title">Servicio Sin Plantilla</h2>
            <p class="page-subtitle">Descripción de prueba sin plantilla</p>

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
                                $rutaImagen = public_path('images/servicio_sin_plantilla/' . $nombreImagen);
                                $existeImagen = file_exists($rutaImagen);
                            @endphp
                            @if($existeImagen)
                                <img src="/images/servicio_sin_plantilla/{{ $nombreImagen }}"
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
                            <p>No hay sub-servicios disponibles para Servicio Sin Plantilla en este momento.</p>
                        </div>
                    @endforelse
                </div>
            </section>
        </main>
@endsection