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
            .muted { color: #666; font-size: 10px; }
            table { width: 100%; border-collapse: collapse; margin-top: 10px; }
            th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
            th { background: #f5f5f5; }
        </style>
    </head>
    <body>
        <h1>Historial de Reservas</h1>
        <div class="muted">Generado: {{ $generatedAt->format('Y-m-d H:i') }}</div>

        <table aria-label="Historial detallado de reservas confirmadas">
            <caption>Resumen de reservas confirmadas.</caption>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Fecha Confirmación</th>
                    <th>Cliente</th>
                    <th>Descripción</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
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
                    <tr>
                        <td>{{ $historial->id }}</td>
                        <td>{{ $historial->confirmado_en ? $historial->confirmado_en->format('d/m/Y H:i:s') : 'N/A' }}</td>
                        <td>
                            @if($persona)
                                {{ $persona->primer_nombre }} {{ $persona->primer_apellido }}
                                @if($persona->correo)
                                    <br><small>{{ $persona->correo }}</small>
                                @endif
                            @else
                                N/A
                            @endif
                        </td>
                        <td>{{ $descripcion }}</td>
                        <td>{{ $estado }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">No hay reservas confirmadas en el historial.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </body>
</html>

