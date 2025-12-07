@php
    $generatedAt = $generatedAt ?? now();
@endphp
<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="utf-8" />
        <title>Historial de Reservas</title>
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
        <h1>Historial de Reservas</h1>
        <div class="muted">Generado: {{ $generatedAt->format('Y-m-d H:i') }}</div>

        @forelse($reservas as $historial)
            @php
                $persona = $historial->reserva?->persona;
                $descripcion = $historial->reserva?->descripcion_evento ?? 'N/A';
                if (!$historial->reserva || $historial->accion === 'finalizada') {
                    $estado = 'Finalizada';
                } else {
                    $estado = 'Confirmada';
                }
            @endphp
            <div class="item">
                <div class="item-title">Reserva #{{ $historial->id }}</div>
                <div class="item-detail">
                    <span class="item-label">Fecha Confirmación:</span>
                    {{ $historial->confirmado_en ? $historial->confirmado_en->format('d/m/Y H:i:s') : 'N/A' }}
                </div>
                <div class="item-detail">
                    <span class="item-label">Cliente:</span>
                    @if($persona)
                        {{ $persona->primer_nombre }} {{ $persona->primer_apellido }}
                        @if($persona->correo)
                            ({{ $persona->correo }})
                        @endif
                    @else
                        N/A
                    @endif
                </div>
                <div class="item-detail">
                    <span class="item-label">Descripción:</span>
                    {{ $descripcion }}
                </div>
                <div class="item-detail">
                    <span class="item-label">Estado:</span>
                    {{ $estado }}
                </div>
            </div>
        @empty
            <div class="item">
                <p>No hay reservas confirmadas en el historial.</p>
            </div>
        @endforelse
    </body>
</html>

