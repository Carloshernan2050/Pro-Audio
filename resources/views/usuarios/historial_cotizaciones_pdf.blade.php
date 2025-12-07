@php
    $generatedAt = $generatedAt ?? now();
@endphp
<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="utf-8" />
        <title>Historial de Cotizaciones</title>
        <style>
            body { font-family: DejaVu Sans, sans-serif; font-size: 11px; }
            h1 { font-size: 16px; margin: 0 0 8px 0; }
            .muted { color: #666; font-size: 10px; margin-bottom: 15px; }
            .item { margin-bottom: 15px; padding: 10px; border-bottom: 1px solid #eee; }
            .item-title { font-weight: bold; font-size: 12px; margin-bottom: 5px; }
            .item-detail { margin: 3px 0; font-size: 10px; }
            .item-label { font-weight: bold; display: inline-block; min-width: 120px; }
        </style>
    </head>
    <body>
        <h1>Historial de Cotizaciones</h1>
        <div class="muted">Generado: {{ $generatedAt->format('Y-m-d H:i') }}</div>

        @forelse($cotizaciones as $c)
            <div class="item">
                <div class="item-title">Cotización #{{ $c->id }}</div>
                <div class="item-detail">
                    <span class="item-label">Fecha y Hora:</span>
                    {{ optional($c->fecha_cotizacion)->format('d/m/Y H:i:s') }}
                </div>
                <div class="item-detail">
                    <span class="item-label">Cliente:</span>
                    @if($c->persona)
                        {{ $c->persona->primer_nombre }} {{ $c->persona->primer_apellido }}
                        @if($c->persona->correo)
                            ({{ $c->persona->correo }})
                        @endif
                    @else
                        N/A
                    @endif
                </div>
                <div class="item-detail">
                    <span class="item-label">Subservicio:</span>
                    {{ $c->subServicio->nombre ?? 'N/A' }}
                </div>
                <div class="item-detail">
                    <span class="item-label">Servicio:</span>
                    {{ optional(optional($c->subServicio)->servicio)->nombre_servicio ?? 'N/A' }}
                </div>
                <div class="item-detail">
                    <span class="item-label">Monto:</span>
                    ${{ number_format($c->monto ?? 0, 0, ',', '.') }}
                </div>
            </div>
        @empty
            <div class="item">
                <p>No hay cotizaciones en el historial.</p>
            </div>
        @endforelse
    </body>
</html>

