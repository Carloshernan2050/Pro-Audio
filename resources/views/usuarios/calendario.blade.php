<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Calendario de Alquileres</title>
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.9/index.global.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.9/index.global.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
<div class="container mt-4">

    <div class="d-flex justify-content-between">
        <h2>Calendario de Alquileres</h2>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCrear">Nuevo alquiler</button>
    </div>
    <hr>

    <div id="calendar"></div>
    <hr>
    <h4>Listado de registros</h4>
    <table class="table table-bordered">
        <thead>
        <tr>
            <th>Producto</th>
            <th>Inicio</th>
            <th>Fin</th>
            <th>Descripción</th>
            <th>Acciones</th>
        </tr>
        </thead>
        <tbody>
        @foreach($registros as $r)
            <tr>
                <td>{{ DB::table('inventario')->where('id',$r->movimientos_inventario_id)->value('descripcion') }}</td>
                <td>{{ $r->fecha_inicio }}</td>
                <td>{{ $r->fecha_fin }}</td>
                <td>{{ $r->descripcion_evento }}</td>
                <td>
                    <form action="{{ route('calendario.destroy',$r->id) }}" method="POST">
                        @csrf @method('DELETE')
                        <button class="btn btn-danger btn-sm" onclick="return confirm('¿Eliminar?')">Eliminar</button>
                    </form>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>

{{-- MODAL CREAR --}}
<div class="modal fade" id="modalCrear" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content" method="POST" action="{{ route('calendario.store') }}">
            @csrf
            <div class="modal-header"><h5>Nueva Reserva</h5></div>
            <div class="modal-body">
                <div class="mb-2">
                    <label>Producto</label>
                    <select name="movimientos_inventario_id" class="form-control" required>
                        <option value="">Seleccione</option>
                        @foreach($inventarios as $inv)
                            <option value="{{ $inv->id }}">{{ $inv->descripcion }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-2">
                    <label>Fecha inicio</label>
                    <input type="date" name="fecha_inicio" class="form-control" required>
                </div>
                <div class="mb-2">
                    <label>Fecha fin</label>
                    <input type="date" name="fecha_fin" class="form-control" required>
                </div>
                <div class="mb-2">
                    <label>Descripción</label>
                    <textarea name="descripcion_evento" class="form-control" required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button class="btn btn-primary">Guardar</button>
            </div>
        </form>
    </div>
</div>


{{-- FULLCALENDAR SCRIPT --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');
    // datos enviados desde el controlador
    var eventos = {!! json_encode($eventos) !!};

    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        locale: 'es',
        events: eventos,
        eventClick: function(info) {
            alert("Producto: " + info.event.title + "\nDescripción: " + info.event.extendedProps.description);
        }
    });
    calendar.render();


</script>
</body>
</html>