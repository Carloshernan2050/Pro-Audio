@extends('layouts.app')

@section('title', 'Historial de Cotizaciones')

@push('styles')
    @vite('resources/css/historial_cotizaciones.css')
@endpush

@section('content')
    <main class="main-content">
        <div class="historial-container">
            <div class="historial-header">
                <h2 class="historial-title">HISTORIAL DE COTIZACIONES</h2>
            </div>

            @if(session('error'))
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
                </div>
            @endif

            {{-- Selector de agrupación --}}
            @if(isset($cotizaciones) && $cotizaciones->count() > 0)
                <div class="historial-filter-container">
                    <form method="GET" action="{{ route('cotizaciones.historial') }}" class="historial-filter-form">
                        <label for="group_by">Agrupar por:</label>
                        <select id="group_by" name="group_by" onchange="this.form.submit()">
                            <option value="" {{ empty($groupBy) ? 'selected' : '' }}>Sin agrupación</option>
                            <option value="consulta" {{ ($groupBy ?? '') === 'consulta' ? 'selected' : '' }}>Consulta</option>
                            <option value="dia" {{ ($groupBy ?? '') === 'dia' ? 'selected' : '' }}>Día</option>
                        </select>
                    </form>
                </div>
            @endif

            {{-- Vista sin agrupación --}}
            @if(empty($groupBy) && isset($cotizaciones) && $cotizaciones->count() > 0)
                <div class="cotizaciones-table-wrapper">
                    <table class="cotizaciones-table">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Servicio</th>
                                <th>Subservicio</th>
                                <th>Monto</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($cotizaciones as $cotizacion)
                                <tr>
                                    <td class="fecha">
                                        {{ $cotizacion->fecha_cotizacion ? $cotizacion->fecha_cotizacion->format('d/m/Y H:i') : 'N/A' }}
                                    </td>
                                    <td>
                                        @if($cotizacion->subServicio && $cotizacion->subServicio->servicio)
                                            <span class="servicio-badge">
                                                {{ $cotizacion->subServicio->servicio->nombre_servicio }}
                                            </span>
                                        @else
                                            <span class="servicio-badge">N/A</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="subservicio-name">
                                            {{ $cotizacion->subServicio->nombre ?? 'N/A' }}
                                        </span>
                                    </td>
                                    <td class="monto">
                                        ${{ number_format($cotizacion->monto ?? 0, 0, ',', '.') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @elseif(($groupBy ?? '') === 'dia' && isset($groupedCotizaciones))
                {{-- Vista agrupada por día --}}
                <div class="cotizaciones-table-wrapper">
                    <table class="cotizaciones-table">
                        <thead>
                            <tr>
                                <th style="width: 25%;">Fecha</th>
                                <th style="width: 25%;">Servicio</th>
                                <th style="width: 50%;">Subservicio y Monto</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($groupedCotizaciones as $day => $data)
                                <tr class="group-header">
                                    <td colspan="3">
                                        <strong>{{ \Carbon\Carbon::parse($day)->format('d/m/Y') }}</strong> —
                                        Total: ${{ number_format($data['total'] ?? 0, 0, ',', '.') }}
                                        ({{ $data['count'] }} items)
                                    </td>
                                </tr>
                                @foreach($data['items'] as $cotizacion)
                                    <tr>
                                        <td class="fecha">{{ $cotizacion->fecha_cotizacion?->format('d/m/Y H:i') }}</td>
                                        <td>
                                            @if($cotizacion->subServicio && $cotizacion->subServicio->servicio)
                                                <span class="servicio-badge">
                                                    {{ $cotizacion->subServicio->servicio->nombre_servicio }}
                                                </span>
                                            @else
                                                <span class="servicio-badge">N/A</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="subservicio-name">{{ $cotizacion->subServicio->nombre ?? 'N/A' }}</span> —
                                            <span class="monto">${{ number_format($cotizacion->monto ?? 0, 0, ',', '.') }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            @empty
                                <tr>
                                    <td colspan="3" class="no-cotizaciones">No hay cotizaciones en el historial.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @elseif(($groupBy ?? '') === 'consulta' && isset($groupedCotizaciones))
                {{-- Vista agrupada por consulta --}}
                <div class="cotizaciones-table-wrapper">
                    <table class="cotizaciones-table">
                        <thead>
                            <tr>
                                <th style="width: 65%;">Consulta</th>
                                <th style="width: 35%;">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($groupedCotizaciones as $key => $data)
                                <tr class="group-header">
                                    <td colspan="2">
                                        <strong>{{ optional($data['timestamp'])->format('d/m/Y H:i:s') }}</strong> —
                                        {{ $data['count'] }} items
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <ul class="ul-compact">
                                            @foreach($data['items'] as $cotizacion)
                                                <li>
                                                    {{ $cotizacion->subServicio->nombre ?? 'N/A' }}
                                                    ({{ optional(optional($cotizacion->subServicio)->servicio)->nombre_servicio ?? 'N/A' }}) —
                                                    ${{ number_format($cotizacion->monto ?? 0, 0, ',', '.') }}
                                                </li>
                                            @endforeach
                                        </ul>
                                    </td>
                                    <td class="monto">${{ number_format($data['total'] ?? 0, 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="no-cotizaciones">No hay cotizaciones en el historial.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @else
                <div class="no-cotizaciones">
                    <i class="fas fa-file-invoice"></i>
                    <p><strong>No tienes cotizaciones aún</strong></p>
                    <p>Las cotizaciones que realices aparecerán aquí.</p>
                    <a href="{{ route('inicio') }}" class="btn btn-volver">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                </div>
            @endif

            {{-- Botón para volver --}}
            @if(isset($cotizaciones) && $cotizaciones->count() > 0)
                <div class="btn-volver-container">
                    <a href="{{ route('inicio') }}" class="btn btn-volver" title="Volver al inicio">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                </div>
            @endif
        </div>
    </main>
@endsection

