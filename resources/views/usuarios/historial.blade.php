@php
    $hasItems = isset($items) && count($items) > 0;
@endphp

<div class="container" style="padding: 1rem;">
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1rem;">
        <h2 style="margin:0;">Historial</h2>
        <div style="display:flex; gap:0.5rem;">
            <a href="{{ route('historial.pdf.reservas') }}" class="btn btn-primary">Descargar PDF Reservas</a>
            <a href="{{ route('historial.pdf.cotizaciones') }}" class="btn btn-primary">Descargar PDF Cotizaciones</a>
        </div>
    </div>

    @if(!$hasItems)
        <p>No hay registros en el historial.</p>
    @else
        <div style="overflow:auto;">
            <table class="table" style="width:100%; border-collapse:collapse;">
                <thead>
                    <tr>
                        <th style="text-align:left; border-bottom:1px solid #ddd; padding:8px;">ID</th>
                        <th style="text-align:left; border-bottom:1px solid #ddd; padding:8px;">Tipo</th>
                        <th style="text-align:left; border-bottom:1px solid #ddd; padding:8px;">Descripción</th>
                        <th style="text-align:left; border-bottom:1px solid #ddd; padding:8px;">Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $h)
                        @php
                            $tipo = $h->reserva ? 'Reserva' : 'Evento';
                            $descripcion = $h->reserva?->descripcion_evento ?? 'N/A';
                            $estado = $h->reserva?->estado ?? ucfirst($h->accion ?? 'N/A');
                        @endphp
                        <tr>
                            <td style="border-bottom:1px solid #f0f0f0; padding:8px;">{{ $h->id }}</td>
                            <td style="border-bottom:1px solid #f0f0f0; padding:8px;">{{ $tipo }}</td>
                            <td style="border-bottom:1px solid #f0f0f0; padding:8px;">{{ $descripcion }}</td>
                            <td style="border-bottom:1px solid #f0f0f0; padding:8px; text-transform:capitalize;">{{ $estado }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>


