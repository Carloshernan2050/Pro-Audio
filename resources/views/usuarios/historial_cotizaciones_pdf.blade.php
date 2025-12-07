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
            .muted { color: #666; font-size: 10px; }
            table { width: 100%; border-collapse: collapse; margin-top: 10px; }
            th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
            th { background: #f5f5f5; }
        </style>
    </head>
    <body>
        <h1>Historial de Cotizaciones</h1>
        <div class="muted">Generado: {{ $generatedAt->format('Y-m-d H:i') }}</div>

        <table aria-label="Historial detallado de cotizaciones generadas para clientes">
            <caption>Resumen de cotizaciones emitidas.</caption>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Fecha y Hora</th>
                    <th>Cliente</th>
                    <th>Subservicio</th>
                    <th>Servicio</th>
                    <th>Monto</th>
                </tr>
            </thead>
            <tbody>
                @forelse($cotizaciones as $c)
                    <tr>
                        <td>{{ $c->id }}</td>
                        <td>{{ optional($c->fecha_cotizacion)->format('d/m/Y H:i:s') }}</td>
                        <td>
                            @if($c->persona)
                                {{ $c->persona->primer_nombre }} {{ $c->persona->primer_apellido }}
                                @if($c->persona->correo)
                                    <br><small>{{ $c->persona->correo }}</small>
                                @endif
                            @else
                                N/A
                            @endif
                        </td>
                        <td>{{ $c->subServicio->nombre ?? 'N/A' }}</td>
                        <td>{{ optional(optional($c->subServicio)->servicio)->nombre_servicio ?? 'N/A' }}</td>
                        <td>${{ number_format($c->monto ?? 0, 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">No hay cotizaciones en el historial.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </body>
</html>

