@extends('layouts.app')

@section('title', 'Búsqueda de Servicios')

@section('content')
    <div style="margin-bottom: 20px;">
        <h2 class="page-title">Resultados de Búsqueda</h2>
        @if(isset($termino) && $termino)
            <p class="page-subtitle">
                <i class="fas fa-search"></i> Búsqueda: "<strong>{{ $termino }}</strong>"
                @if(isset($resultados) && $resultados->count() > 0)
                    - {{ $resultados->count() }} {{ $resultados->count() === 1 ? 'resultado encontrado' : 'resultados encontrados' }}
                @endif
            </p>
        @endif
    </div>

    <section class="productos-servicio">
        @if(isset($resultados) && $resultados->count() > 0)
            @php
                $resultadosPorServicio = [];
                foreach ($resultados as $resultado) {
                    $servicioNombre = is_object($resultado) ? $resultado->nombre_servicio : $resultado['nombre_servicio'];
                    if (!isset($resultadosPorServicio[$servicioNombre])) {
                        $resultadosPorServicio[$servicioNombre] = [];
                    }
                    $resultadosPorServicio[$servicioNombre][] = $resultado;
                }
            @endphp

            @foreach($resultadosPorServicio as $servicioNombre => $items)
                <div style="margin-bottom: 40px;">
                    <h3 style="color: #e91c1c; font-size: 1.5em; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 2px solid #e91c1c;">
                        <i class="fas fa-tag"></i> {{ $servicioNombre }}
                    </h3>
                    <div class="productos-grid">
                        @foreach($items as $item)
                            @php
                                $nombre = is_object($item) ? $item->nombre : $item['nombre'];
                                $precio = null; // ocultar precio en resultados de búsqueda
                                $descripcion = is_object($item) && isset($item->descripcion) ? $item->descripcion : (isset($item['descripcion']) ? $item['descripcion'] : null);
                                $imagenPath = strtolower(str_replace(' ', '_', $nombre));
                                $servicioPath = strtolower(str_replace(' ', '_', $servicioNombre));
                            @endphp
                            <div class="producto-item">
                                <img src="/images/{{ $servicioPath }}/{{ $imagenPath }}.jpg" 
                                     alt="{{ $nombre }}" 
                                     class="producto-imagen"
                                     onerror="this.src='/images/default.jpg'">
                                <h4 class="producto-nombre">{{ $nombre }}</h4>
                                {{-- Precio oculto en la búsqueda --}}
                                @if($descripcion)
                                    <p class="producto-descripcion">{{ $descripcion }}</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        @else
            <div class="no-services" style="text-align: center; padding: 40px;">
                <i class="fas fa-search" style="font-size: 3em; color: #999; margin-bottom: 20px;"></i>
                <p style="font-size: 1.2em; color: #666;">
                    @if(isset($termino) && $termino)
                        No se encontraron resultados para "<strong>{{ $termino }}</strong>"
                    @else
                        Ingresa un término de búsqueda para encontrar servicios
                    @endif
                </p>
                <p style="color: #999; margin-top: 10px;">
                    Intenta con términos diferentes o explora nuestras categorías principales.
                </p>
            </div>
        @endif
    </section>
@endsection

