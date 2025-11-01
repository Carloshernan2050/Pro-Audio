@php
    $hasItems = isset($items) && count($items) > 0;
@endphp

<div class="container" style="padding: 1rem;">
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1rem;">
        <h2 style="margin:0;">Historial</h2>
        <a href="{{ route('historial.pdf') }}" class="btn btn-primary">Descargar PDF</a>
    </div>

    @if(!$hasItems)
        <p>No hay registros en el historial.</p>
    @else
        <div style="overflow:auto;">
            <table class="table" style="width:100%; border-collapse:collapse;">
                <thead>
                    <tr>
                        <th style="text-align:left; border-bottom:1px solid #ddd; padding:8px;">ID</th>
                        <th style="text-align:left; border-bottom:1px solid #ddd; padding:8px;">Calendario</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $h)
                        <tr>
                            <td style="border-bottom:1px solid #f0f0f0; padding:8px;">{{ $h->id }}</td>
                            <td style="border-bottom:1px solid #f0f0f0; padding:8px;">{{ optional($h->calendario)->descripcion_evento ?? 'N/A' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>


