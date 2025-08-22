<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Calendario de Alquileres</title>

    {{-- FullCalendar --}}
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.9/index.global.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.9/index.global.min.js"></script>

    {{-- Bootstrap --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>

    {{-- Llamada al archivo CSS principal usando Vite --}}
    @vite('resources/css/app.css')
</head>

<body>
    {{-- Contenedor principal --}}
    <div class="dashboard-container">
        <div class="container mt-4">
            <div class="calendar-section">
                {{-- ENCABEZADO --}}
                <div class="calendar-header">
                    <h2 class="calendar-title">Calendario de Alquileres</h2>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCrear">Nuevo alquiler</button>
                </div>

                {{-- CALENDARIO --}}
                <div id="calendar" class="mb-4"></div>

                {{-- TABLA DE REGISTROS --}}
                <div class="table-container">
                    <h4 class="text-light">Listado de registros</h4>
                    <table class="table table-bordered table-striped calendar-table">
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
                                <td>{{ DB::table('inventario')->where('id', $r->movimientos_inventario_id)->value('descripcion') }}</td>
                                <td>{{ $r->fecha_inicio }}</td>
                                <td>{{ $r->fecha_fin }}</td>
                                <td>{{ $r->descripcion_evento }}</td>
                                <td>
                                    {{-- Botón Editar --}}
                                    <button class="btn calendar-btn-action edit" data-bs-toggle="modal" data-bs-target="#modalEditar{{ $r->id }}">
                                        Editar
                                    </button>

                                    {{-- Botón Eliminar --}}
                                    <form action="{{ route('calendario.eliminar',$r->id) }}" method="POST" class="d-inline">
                                        @csrf @method('DELETE')
                                        <button class="btn calendar-btn-action delete" onclick="return confirm('¿Eliminar?')">Eliminar</button>
                                    </form>
                                </td>
                            </tr>

                            {{-- MODAL EDITAR --}}
                            <div class="modal fade calendar-modal" id="modalEditar{{ $r->id }}">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form action="{{ route('calendario.actualizar', $r->id) }}" method="POST">
                                            @csrf
                                            @method('PUT')

                                            <div class="modal-header">
                                                <h5 class="modal-title">Editar Reserva</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>

                                            <div class="modal-body">
                                                <div class="mb-3">
                                                    <label class="form-label">Producto</label>
                                                    <select name="movimientos_inventario_id" class="form-select">
                                                        @foreach($inventarios as $inv)
                                                        <option value="{{ $inv->id }}" {{ $inv->id == $r->movimientos_inventario_id ? 'selected' : '' }}>
                                                            {{ $inv->descripcion }}
                                                        </option>
                                                        @endforeach
                                                    </select>
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label">Fecha inicio</label>
                                                    <input type="date" name="fecha_inicio" value="{{ $r->fecha_inicio }}" class="form-control">
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label">Fecha fin</label>
                                                    <input type="date" name="fecha_fin" value="{{ $r->fecha_fin }}" class="form-control">
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label">Descripción</label>
                                                    <textarea name="descripcion_evento" class="form-control" rows="3">{{ $r->descripcion_evento }}</textarea>
                                                </div>
                                            </div>

                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                                                <button type="submit" class="btn btn-primary">Actualizar</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- MODAL CREAR --}}
        <div class="modal fade calendar-modal" id="modalCrear" tabindex="-1">
            <div class="modal-dialog">
                <form class="modal-content" method="POST" action="{{ route('calendario.guardar') }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Nueva Reserva</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
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
                var eventos = @json($eventos);

                var calendar = new FullCalendar.Calendar(calendarEl, {
                    initialView: 'dayGridMonth',
                    locale: 'es',
                    events: eventos,
                    eventClick: function(info) {
                        alert("Producto: " + info.event.title + "\nDescripción: " + info.event.extendedProps.description);
                    }
                });

                calendar.render();
            });
        </script>
    </div>
</body>
</html>