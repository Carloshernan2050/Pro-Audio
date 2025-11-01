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
    {{-- CSS específico del calendario --}}
    @vite('resources/css/calendario.css')

    {{-- Font Awesome --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body>
    {{-- Contenedor principal --}}
    <div class="dashboard-container">
        @php
            $rolesSesion = session('roles') ?? [session('role')];
            $rolesSesion = is_array($rolesSesion) ? $rolesSesion : [$rolesSesion];
            $esAdmin = in_array('Administrador', $rolesSesion, true) || in_array('Admin', $rolesSesion, true) || in_array('Superadmin', $rolesSesion, true);
        @endphp
        {{-- Contenedor del calendario con sidebar --}}
        <div class="calendario-wrapper">
            {{-- Contenido principal: Calendario --}}
            <div class="calendario-main-content">
                <div class="calendar-section">
                    {{-- ENCABEZADO --}}
                    <div class="calendar-header">
                        <h2 class="calendar-title">
                            <i class="fas fa-calendar-alt"></i> Calendario de Alquileres
                        </h2>
                        @if($esAdmin)
                        <button class="btn btn-crear-alquiler" data-bs-toggle="modal" data-bs-target="#modalCrear">
                            <i class="fas fa-plus"></i> Nuevo alquiler
                        </button>
                        @endif
                    </div>

                    @if(session('ok'))
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i> {{ session('ok') }}
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i> {{ $errors->first() }}
                        </div>
                    @endif

                    {{-- CALENDARIO --}}
                    <div id="calendar" class="calendar-container"></div>

                    <div class="mb-3 mt-4">
                        <a href="{{ route('inicio') }}" class="btn btn-volver">
                            <i class="fas fa-arrow-left"></i> Volver al inicio
                        </a>
                    </div>
                </div>
            </div>

            {{-- SIDEBAR: Listado de registros --}}
            @if($esAdmin)
            <aside class="calendario-sidebar">
                <div class="sidebar-header">
                    <h3 class="sidebar-title">
                        <i class="fas fa-list"></i> Listado de Registros
                    </h3>
                </div>
                
                <div class="sidebar-content">
                    <div class="table-container-sidebar">
                        <div class="table-wrapper">
                            <table class="calendar-table-sidebar">
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
                                    @forelse($registros as $r)
                                    @php
                                        $movimiento = collect($movimientos ?? [])->first(function($m) use ($r) {
                                            return $m->id == $r->movimientos_inventario_id;
                                        });
                                        $nombreInventario = 'Sin producto';
                                        if ($movimiento && isset($inventarios[$movimiento->inventario_id])) {
                                            $nombreInventario = $inventarios[$movimiento->inventario_id]->descripcion;
                                        }
                                    @endphp
                                    <tr>
                                        <td>
                                            <span class="producto-name">
                                                {{ $nombreInventario }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="fecha-badge">
                                                {{ \Carbon\Carbon::parse($r->fecha_inicio)->format('d/m/Y') }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="fecha-badge">
                                                {{ \Carbon\Carbon::parse($r->fecha_fin)->format('d/m/Y') }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="descripcion-text" title="{{ $r->descripcion_evento }}">
                                                {{ Str::limit($r->descripcion_evento, 30) }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="acciones-buttons">
                                                <button class="btn-action btn-edit" data-bs-toggle="modal" data-bs-target="#modalEditar{{ $r->id }}" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <form action="{{ route('calendario.eliminar',$r->id) }}" method="POST" class="d-inline delete-form">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="btn-action btn-delete" title="Eliminar">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>

                                    {{-- Modal de edición --}}
                                    <div class="modal fade calendar-modal" id="modalEditar{{ $r->id }}">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form action="{{ route('calendario.actualizar', $r->id) }}" method="POST">
                                                    @csrf
                                                    @method('PUT')

                                                    <div class="modal-header">
                                                        <h5 class="modal-title">
                                                            <i class="fas fa-edit"></i> Editar Reserva
                                                        </h5>
                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                    </div>

                                                    <div class="modal-body">
                                                        <div id="alertEditar{{ $r->id }}" class="alert alert-danger" style="display: none;"></div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Servicio *</label>
                                                            <select id="servicio_editar{{ $r->id }}" class="form-select" required>
                                                                <option value="">Seleccione un servicio</option>
                                                                <option value="Publicidad">Publicidad</option>
                                                                <option value="Alquiler">Alquiler</option>
                                                                <option value="Animación">Animación</option>
                                                            </select>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Subservicio / Producto *</label>
                                                            <select name="movimientos_inventario_id" id="movimiento_editar{{ $r->id }}" class="form-select" required>
                                                                <option value="">Seleccione un producto</option>
                                                                @foreach($movimientos ?? [] as $movimiento)
                                                                @php
                                                                    $desc = $inventarios[$movimiento->inventario_id]->descripcion ?? '';
                                                                    $serv = (stripos($desc,'publi')!==false) ? 'Publicidad' : ((stripos($desc,'anim')!==false) ? 'Animación' : 'Alquiler');
                                                                @endphp
                                                                <option value="{{ $movimiento->id }}" data-inv="{{ $movimiento->inventario_id }}" data-stock="{{ $inventarios[$movimiento->inventario_id]->stock ?? '' }}" data-servicio="{{ $serv }}" {{ $movimiento->id == $r->movimientos_inventario_id ? 'selected' : '' }}>
                                                                    {{ $desc ?: 'Sin descripción' }} 
                                                                </option>
                                                                @endforeach
                                                            </select>
                                                            <small id="stock_editar{{ $r->id }}" class="text-light" style="display:block;margin-top:6px;opacity:.85;"></small>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label class="form-label">Fecha inicio *</label>
                                                            <input type="datetime-local" name="fecha_inicio" id="fecha_inicio_editar{{ $r->id }}" value="{{ $r->fecha_inicio ? \Carbon\Carbon::parse($r->fecha_inicio)->format('Y-m-d\TH:i') : '' }}" class="form-control" required>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label class="form-label">Fecha fin *</label>
                                                            <input type="datetime-local" name="fecha_fin" id="fecha_fin_editar{{ $r->id }}" value="{{ $r->fecha_fin ? \Carbon\Carbon::parse($r->fecha_fin)->format('Y-m-d\TH:i') : '' }}" class="form-control" required>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label class="form-label">Cantidad *</label>
                                                            <input type="number" name="cantidad" id="cantidad_editar{{ $r->id }}" class="form-control" min="1" value="" required>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label class="form-label">Descripción *</label>
                                                            <textarea name="descripcion_evento" id="descripcion_editar{{ $r->id }}" class="form-control" rows="3" required>{{ $r->descripcion_evento }}</textarea>
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
                                    @empty
                                    <tr>
                                        <td colspan="5" class="no-records">
                                            <i class="fas fa-inbox"></i>
                                            <p>No hay registros disponibles</p>
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </aside>
            @endif
        </div>

        @if($esAdmin)
        {{-- MODAL CREAR --}}
        <div class="modal fade calendar-modal" id="modalCrear" tabindex="-1">
            <div class="modal-dialog">
                <form class="modal-content" id="formCrear" method="POST" action="{{ route('calendario.guardar') }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-plus-circle"></i> Nueva Reserva
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body alquiler-box">
                        <div id="alertCrear" class="alert alert-danger" style="display: none;"></div>
                        <div class="mb-2">
                            <label>Servicio *</label>
                            <select id="servicio_crear" class="form-control" required>
                                <option value="">Seleccione un servicio</option>
                                <option value="Publicidad">Publicidad</option>
                                <option value="Alquiler">Alquiler</option>
                                <option value="Animación">Animación</option>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label>Subservicio / Productos *</label>
                            <div id="productos_crear" class="table-container" style="max-height:280px;overflow:auto;padding:10px;">
                                <div style="position:relative;">
                                    <input type="text" id="buscar_producto" class="form-control" placeholder="Buscar en inventario por nombre...">
                                    <div id="sugerencias_productos" class="sugerencias-dropdown"></div>
                                </div>
                                <div style="display:flex;align-items:center;gap:10px;margin-top:10px;">
                                    <span class="badge-count" id="badge_seleccionados"><i class="fas fa-box"></i> <span id="count_sel">0</span> seleccionados</span>
                                </div>
                                <div id="seleccionados_contenedor" class="seleccionados-lista" style="margin-top:12px;"></div>
                            </div>
                            <small class="text-light field-hint">Busque productos, selecciónelos y asigne cantidad. La cantidad no puede exceder el stock disponible.</small>
                        </div>
                        <!-- Cantidades por producto arriba -->
                        <div class="mb-2">
                            <label>Fecha inicio *</label>
                            <input type="datetime-local" name="fecha_inicio" id="fecha_inicio_crear" class="form-control" required>
                        </div>
                        <div class="mb-2">
                            <label>Fecha fin *</label>
                            <input type="datetime-local" name="fecha_fin" id="fecha_fin_crear" class="form-control" required>
                        </div>
                        <div class="mb-2">
                            <label>Descripción *</label>
                            <textarea name="descripcion_evento" id="descripcion_crear" class="form-control" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                        <button type="submit" id="btn_guardar_crear" class="btn btn-primary">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
        @endif

        {{-- MODAL CONFIRMACIÓN ELIMINAR --}}
        <div class="modal fade calendar-modal" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="confirmDeleteLabel">
                            <i class="fas fa-exclamation-triangle"></i> Confirmar eliminación
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>¿Estás seguro de que deseas eliminar esta reserva? Esta acción no se puede deshacer.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-primary" id="confirmDeleteBtn">
                            <i class="fas fa-trash"></i> Eliminar
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- FULLCALENDAR SCRIPT --}}
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                var calendarEl = document.getElementById('calendar');
                var eventos = @json($eventos);
                var isAdmin = @json($esAdmin);

                // Construir set de días reservados (YYYY-MM-DD) solo por fecha de inicio
                function ymd(dateObj) {
                    var y = dateObj.getFullYear();
                    var m = String(dateObj.getMonth() + 1).padStart(2, '0');
                    var d = String(dateObj.getDate()).padStart(2, '0');
                    return y + '-' + m + '-' + d;
                }

                var reservedDates = new Set();
                (eventos || []).forEach(function(e) {
                    try {
                        var s = new Date(e.start);
                        reservedDates.add(ymd(s));
                    } catch(err) { /* noop */ }
                });

                var calendar = new FullCalendar.Calendar(calendarEl, {
                    initialView: 'dayGridMonth',
                    locale: 'es',
                    firstDay: 1,
                    events: eventos,
                    eventDisplay: 'none',
                    height: 'auto',
                    contentHeight: 'auto',
                    aspectRatio: 1.6,
                    dayCellDidMount: function(arg) {
                        var d = arg.date;
                        var key = ymd(d);
                        if (reservedDates.has(key)) {
                            arg.el.classList.add('day-reserved');
                        }
                    },
                    headerToolbar: isAdmin ? {
                        left: 'prev,next today',
                        center: 'title',
                        right: 'dayGridMonth,timeGridWeek,timeGridDay'
                    } : {
                        left: 'prev,next',
                        center: 'title',
                        right: ''
                    },
                    eventClick: function(info) {
                        if (!isAdmin) return; // Usuarios no interactúan con eventos
                        var descripcion = info.event.extendedProps.description || 'Sin descripción';
                        var inicio = new Date(info.event.start).toLocaleString('es-ES');
                        var fin = info.event.end ? new Date(info.event.end).toLocaleString('es-ES') : 'Sin fecha de fin';
                        alert("Producto: " + info.event.title + "\nDescripción: " + descripcion + "\nFecha de inicio: " + inicio + "\nFecha de fin: " + fin);
                    },
                    buttonText: {
                        today: 'Hoy',
                        month: 'Mes',
                        week: 'Semana',
                        day: 'Día'
                    },
                    allDayText: 'Todo el día',
                    moreLinkText: 'más',
                    noEventsText: 'No hay eventos para mostrar',
                    eventTimeFormat: {
                        hour: '2-digit',
                        minute: '2-digit',
                        meridiem: 'short'
                    },
                    locale: 'es',
                    eventBackgroundColor: '#e91c1c',
                    eventBorderColor: '#e91c1c'
                });

                calendar.render();
            });

            // Validación de fechas
            function validarFechas(fechaInicioId, fechaFinId, alertId, formId) {
                var fechaInicio = document.getElementById(fechaInicioId).value;
                var fechaFin = document.getElementById(fechaFinId).value;
                var alertElement = document.getElementById(alertId);

                // Ocultar alerta anterior
                if (alertElement) {
                    alertElement.style.display = 'none';
                    alertElement.textContent = '';
                }

                // Verificar que ambas fechas estén llenas
                if (!fechaInicio || !fechaFin) {
                    return true; // Permitir que HTML5 required valide esto
                }

                var inicio = new Date(fechaInicio);
                var fin = new Date(fechaFin);
                var ahora = new Date();

                // Validar que la fecha de inicio no sea en el pasado
                if (inicio < ahora) {
                    if (alertElement) {
                        alertElement.innerHTML = '<i class="fas fa-exclamation-circle"></i> La fecha de inicio no puede ser anterior a la fecha y hora actual.';
                        alertElement.style.display = 'block';
                    }
                    return false;
                }

                // Validar que la fecha fin no sea anterior a la fecha inicio
                if (fin <= inicio) {
                    if (alertElement) {
                        alertElement.innerHTML = '<i class="fas fa-exclamation-circle"></i> La fecha de fin debe ser posterior a la fecha de inicio.';
                        alertElement.style.display = 'block';
                    }
                    return false;
                }

                // Validar que la diferencia entre fechas sea razonable (no más de 1 año)
                var diferenciaDias = (fin - inicio) / (1000 * 60 * 60 * 24);
                if (diferenciaDias > 365) {
                    if (alertElement) {
                        alertElement.innerHTML = '<i class="fas fa-exclamation-circle"></i> El período de alquiler no puede exceder 1 año.';
                        alertElement.style.display = 'block';
                    }
                    return false;
                }

                return true;
            }

            // Validar formulario de crear
            var formCrear = document.getElementById('formCrear');
            if (formCrear) {
                formCrear.addEventListener('submit', function(e) {
                    if (!validarFechas('fecha_inicio_crear', 'fecha_fin_crear', 'alertCrear', 'formCrear')) {
                        e.preventDefault();
                        return false;
                    }
                });

                // Validar en tiempo real
                document.getElementById('fecha_inicio_crear').addEventListener('change', function() {
                    validarFechas('fecha_inicio_crear', 'fecha_fin_crear', 'alertCrear', 'formCrear');
                });

                document.getElementById('fecha_fin_crear').addEventListener('change', function() {
                    validarFechas('fecha_inicio_crear', 'fecha_fin_crear', 'alertCrear', 'formCrear');
                });
            }

            // Validar formularios de editar
            @if(session('role') === 'Administrador')
            @foreach($registros as $r)
            (function() {
                var formId = 'formEditar{{ $r->id }}';
                var fechaInicioId = 'fecha_inicio_editar{{ $r->id }}';
                var fechaFinId = 'fecha_fin_editar{{ $r->id }}';
                var alertId = 'alertEditar{{ $r->id }}';
                
                var form = document.querySelector('form[action="{{ route('calendario.actualizar', $r->id) }}"]');
                if (form) {
                    form.addEventListener('submit', function(e) {
                        if (!validarFechas(fechaInicioId, fechaFinId, alertId, formId)) {
                            e.preventDefault();
                            return false;
                        }
                    });

                    var inicioInput = document.getElementById(fechaInicioId);
                    var finInput = document.getElementById(fechaFinId);
                    
                    if (inicioInput) {
                        inicioInput.addEventListener('change', function() {
                            validarFechas(fechaInicioId, fechaFinId, alertId, formId);
                        });
                    }
                    
                    if (finInput) {
                        finInput.addEventListener('change', function() {
                            validarFechas(fechaInicioId, fechaFinId, alertId, formId);
                        });
                    }
                }
            })();
            @endforeach
            @endif

            // Confirmación estilizada para eliminar
            (function() {
                var formToDelete = null;
                var deleteModalEl = document.getElementById('confirmDeleteModal');
                var confirmBtn = document.getElementById('confirmDeleteBtn');
                var bsModal = deleteModalEl ? new bootstrap.Modal(deleteModalEl) : null;

                document.addEventListener('submit', function(e) {
                    var target = e.target;
                    if (target && target.classList && target.classList.contains('delete-form')) {
                        e.preventDefault();
                        formToDelete = target;
                        if (bsModal) { bsModal.show(); }
                    }
                }, true);

                if (confirmBtn) {
                    confirmBtn.addEventListener('click', function() {
                        if (formToDelete) {
                            formToDelete.submit();
                            formToDelete = null;
                            if (bsModal) { bsModal.hide(); }
                        }
                    });
                }
            })();

            // Mostrar stock disponible en select (crear/editar)
            (function() {
                function updateStock(selectEl, targetId) {
                    if (!selectEl) return;
                    var opt = selectEl.options[selectEl.selectedIndex];
                    var stock = opt ? opt.getAttribute('data-stock') : '';
                    var target = document.getElementById(targetId);
                    if (target) {
                        if (stock !== '' && stock !== null) {
                            target.textContent = 'Disponible: ' + stock;
                            target.style.display = 'block';
                        } else {
                            target.textContent = '';
                            target.style.display = 'none';
                        }
                    }
                }

            // Búsqueda y selección dinámica de productos
            function initCrearUI() {
                const inventarioItems = [
                    @foreach($inventarios ?? [] as $inv)
                        @php
                            $descI = $inv->descripcion ?? '';
                            $servI = (stripos($descI,'publi')!==false) ? 'Publicidad' : ((stripos($descI,'anim')!==false) ? 'Animación' : 'Alquiler');
                        @endphp
                        { inventario_id: {{ $inv->id }}, descripcion: @json($descI ?: 'Sin descripción'), stock: {{ (int)($inv->stock ?? 0) }}, servicio: @json($servI) },
                    @endforeach
                ];
                const movimientosPorInventario = {
                    @foreach($movimientos ?? [] as $mov)
                        {{ $mov->inventario_id }}: {{ $mov->id }},
                    @endforeach
                };

                const servicioCrear = document.getElementById('servicio_crear');
                const inputBuscar = document.getElementById('buscar_producto');
                const sugerencias = document.getElementById('sugerencias_productos');
                const contSeleccionados = document.getElementById('seleccionados_contenedor');
                const badgeCount = document.getElementById('count_sel');
                const saveBtn = document.getElementById('btn_guardar_crear');
                // Usaremos el DOM para contar seleccionados y una Map local para validación rápida
                const seleccionadosMap = new Map();

                // Evitar múltiples binds al reabrir el modal
                function bindOnce(el, eventName, key, handler) {
                    if (!el) return;
                    el.__binds = el.__binds || {};
                    const k = `${eventName}:${key}`;
                    if (el.__binds[k]) return;
                    el.addEventListener(eventName, handler);
                    el.__binds[k] = true;
                }

                function rangoActual() {
                    const s = document.getElementById('fecha_inicio_crear')?.value;
                    const f = document.getElementById('fecha_fin_crear')?.value;
                    return {s, f};
                }

                // Parsear cantidad desde descripción de eventos
                function parseCant(desc) {
                    if (!desc) return 0;
                    const m = desc.match(/Cantidad solicitada:\s*(\d+)/i);
                    return m ? parseInt(m[1] || '0') : 0;
                }

                // Calcular reservado en rango usando eventos del servidor (por movimiento)
                function reservadoEnRango(movId, s, f) {
                    if (!s || !f) return 0; // si no hay fechas aún, no bloquear
                    const si = new Date(s); const fi = new Date(f);
                    return (eventos || []).reduce((acc, ev) => {
                        try {
                            if ((ev.movId || ev.movid || ev.movid) == movId) {
                                const es = new Date(ev.start); const ef = ev.end ? new Date(ev.end) : new Date(ev.start);
                                const solapa = es <= fi && ef >= si;
                                if (solapa) acc += parseCant(ev.description);
                            }
                        } catch {}
                        return acc;
                    }, 0);
                }

                // Calcular reservado en rango a nivel de inventario (suma todas las reservas de ese inventario)
                function reservadoInventarioEnRango(inventarioId, s, f) {
                    if (!s || !f) return 0;
                    const si = new Date(s); const fi = new Date(f);
                    return (eventos || []).reduce((acc, ev) => {
                        try {
                            if ((ev.inventarioId || ev.inventarioid) == inventarioId) {
                                const es = new Date(ev.start); const ef = ev.end ? new Date(ev.end) : new Date(ev.start);
                                const solapa = es <= fi && ef >= si;
                                if (solapa) acc += parseCant(ev.description);
                            }
                        } catch {}
                        return acc;
                    }, 0);
                }

                function normTxt(t){
                    return (t||'').toString().normalize('NFD').replace(/[\u0300-\u036f]/g,'').toLowerCase();
                }

                function filtrarProductos(query, servicio) {
                    query = normTxt(query || '');
                    const {s, f} = rangoActual();
                    return inventarioItems.filter(it => {
                        const pasaServicio = !servicio || servicio === '' || it.servicio === servicio;
                        const pasaTexto = !query || normTxt(it.descripcion).includes(query);
                        return pasaServicio && pasaTexto;
                    }).slice(0, 20);
                }

                function renderSugerencias(lista) {
                    if (!lista.length) { sugerencias.style.display = 'none'; sugerencias.innerHTML=''; return; }
                    const {s, f} = rangoActual();
                    sugerencias.innerHTML = lista.map(it => {
                        const movId = movimientosPorInventario[it.inventario_id] || null;
                        const ya = reservadoInventarioEnRango(it.inventario_id, s, f);
                        const disp = Math.max(0, (it.stock || 0) - ya);
                        const disabled = disp <= 0 || !movId;
                        return (
                            `<div class="sugerencias-item" data-inv="${it.inventario_id}" data-disp="${disp}" style="${disabled?'opacity:.5;pointer-events:none;':''}">`+
                            `<strong>${it.descripcion}</strong><br><small>Disponible en fechas: ${disp}</small>`+
                            `</div>`
                        );
                    }).join('');
                    sugerencias.style.display = 'block';
                    sugerencias.querySelectorAll('.sugerencias-item').forEach(el => {
                        bindOnce(el, 'click', 'sug-click', function(){
                            const invId = parseInt(this.getAttribute('data-inv'));
                            const it = inventarioItems.find(x=>x.inventario_id===invId);
                            if (!it) return;
                            const movId = movimientosPorInventario[invId] || null;
                            if (!movId) { showToast('Producto sin movimiento asociado, no se puede reservar', 'error'); return; }
                            it._movId = movId;
                            if (!seleccionadosMap.has(invId)) {
                                seleccionadosMap.set(invId, it);
                                agregarSeleccionado(it);
                            }
                            sugerencias.style.display = 'none';
                            inputBuscar.value='';
                        });
                    });
                }

                function agregarSeleccionado(it) {
                    const row = document.createElement('div');
                    row.className = 'item-row';
                    row.style.display = 'flex';
                    row.style.gap = '10px';
                    row.style.alignItems = 'center';
                    row.style.border = '1px solid rgba(255,255,255,.15)';
                    row.style.borderRadius = '8px';
                    row.style.padding = '8px 10px';
                    row.setAttribute('data-inv-id', String(it.inventario_id));

                    const {s, f} = rangoActual();
                    const movId = it._movId;
                    const yaInv = reservadoInventarioEnRango(it.inventario_id, s, f);
                    const disp = Math.max(0, (it.stock || 0) - yaInv);
                    row.innerHTML = `
                        <span class="item-titulo">${it.descripcion} <small style="opacity:.8;">(Disp: ${it.stock})</small></span>
                        <div style="display:flex;flex-direction:column;gap:4px;width:130px;">
                            <input type="number" min="1" class="form-control form-control-sm" data-stock="${disp}" name="items[${movId}][cantidad]" placeholder="Cantidad (max ${disp})" required>
                            <small class="field-error" style="display:none;"></small>
                        </div>
                        <input type="hidden" name="items[${movId}][movimientos_inventario_id]" value="${movId}">
                        <button type="button" class="btn btn-sm btn-secondary" data-remove="${it.inventario_id}"><i class="fas fa-times"></i></button>
                    `;

                    row.querySelector('button[data-remove]')?.addEventListener('click', function(){
                        const rid = parseInt(this.getAttribute('data-remove'));
                        seleccionadosMap.delete(rid);
                        row.remove();
                    });

                    contSeleccionados.appendChild(row);
                    actualizarBadge();
                    showToast('Producto agregado a la reserva', 'success');
                    const qty = row.querySelector('input[type=number]');
                    qty.addEventListener('input', validarTodo);
                }

                // Mostrar sugerencias al escribir o al enfocar sin texto (top 10 del servicio)
                function refreshSugerencias() {
                    const lista = filtrarProductos(inputBuscar ? inputBuscar.value : '', servicioCrear ? servicioCrear.value : '');
                    renderSugerencias(lista);
                }
                bindOnce(inputBuscar, 'input', 'search-input', refreshSugerencias);
                bindOnce(inputBuscar, 'focus', 'search-focus', function(){
                    refreshSugerencias();
                });
                bindOnce(inputBuscar, 'click', 'search-click', function(){
                    refreshSugerencias();
                });

                bindOnce(servicioCrear, 'change', 'service-change', function(){
                    refreshSugerencias();
                });

                // Cerrar sugerencias solo si se hace click fuera; volverán a abrir en focus/click
                bindOnce(document, 'mousedown', 'doc-hide-sug', function(e){
                    if (sugerencias && !sugerencias.contains(e.target) && e.target !== inputBuscar) {
                        sugerencias.style.display = 'none';
                    }
                });

                function actualizarBadge() {
                    const total = contSeleccionados ? contSeleccionados.querySelectorAll('input[type="hidden"][name^="items"]').length : 0;
                    if (badgeCount) badgeCount.textContent = String(total);
                }

                function validarTodo() {
                    let valido = true;
                    contSeleccionados.querySelectorAll('.item-row').forEach(function(row){
                        const qty = row.querySelector('input[type=number]');
                        const err = row.querySelector('.field-error');
                        const max = parseInt(qty.getAttribute('data-stock') || '0');
                        const val = parseInt(qty.value || '0');
                        err.style.display = 'none';
                        if (!val || val < 1) {
                            err.textContent = 'Cantidad mínima: 1';
                            err.style.display = 'block';
                            valido = false;
                        } else if (max && val > max) {
                            err.textContent = 'No puede exceder el stock (' + max + ')';
                            err.style.display = 'block';
                            valido = false;
                        }
                    });
                    if (saveBtn) saveBtn.disabled = !valido || seleccionadosMap.size === 0;
                    return valido;
                }

                // Recalcular disponibilidad cuando cambien las fechas
                function recomputeAvailability() {
                    const s = document.getElementById('fecha_inicio_crear')?.value;
                    const f = document.getElementById('fecha_fin_crear')?.value;
                    if (!s || !f) return;
                    contSeleccionados.querySelectorAll('.item-row').forEach(function(row){
                        const invIdAttr = row.getAttribute('data-inv-id');
                        const invId = invIdAttr ? parseInt(invIdAttr) : null;
                        const qty = row.querySelector('input[type=number]');
                        const err = row.querySelector('.field-error');
                        if (!invId || !qty) return;
                        const yaInv = reservadoInventarioEnRango(invId, s, f);
                        const original = parseInt(qty.getAttribute('data-stock-original') || qty.getAttribute('data-stock') || '0');
                        const disp = Math.max(0, (original || 0) - yaInv);
                        qty.setAttribute('data-stock', String(disp));
                        qty.setAttribute('placeholder', 'Cantidad (max ' + disp + ')');
                        const val = parseInt(qty.value || '0');
                        if (val && disp && val > disp) {
                            err.textContent = 'La cantidad supera la disponibilidad en esas fechas (' + disp + ')';
                            err.style.display = 'block';
                            showToast('Cantidad supera disponibilidad para esas fechas', 'error');
                        } else {
                            if (err.textContent.indexOf('supera la disponibilidad') !== -1) {
                                err.style.display = 'none';
                            }
                        }
                    });
                    validarTodo();
                }

                // Guardar el stock original al agregar
                const observer = new MutationObserver(function(){
                    contSeleccionados.querySelectorAll('.item-row').forEach(function(row){
                        const qty = row.querySelector('input[type=number]');
                        if (qty && !qty.hasAttribute('data-stock-original')) {
                            qty.setAttribute('data-stock-original', qty.getAttribute('data-stock') || '0');
                        }
                    });
                });
                if (contSeleccionados) {
                    observer.observe(contSeleccionados, { childList: true, subtree: true });
                }

                document.getElementById('fecha_inicio_crear')?.addEventListener('change', recomputeAvailability);
                document.getElementById('fecha_fin_crear')?.addEventListener('change', recomputeAvailability);

                // Validar antes de enviar
                formCrear?.addEventListener('submit', async function(e){
                    e.preventDefault();
                    const alertBox = document.getElementById('alertCrear');
                    if (alertBox) { alertBox.style.display = 'none'; alertBox.textContent = ''; }
                    const total = contSeleccionados ? contSeleccionados.querySelectorAll('input[type="hidden"][name^="items"]').length : 0;
                    if (total === 0) {
                        showToast('Seleccione al menos un producto', 'error');
                        if (alertBox) { alertBox.textContent = 'Seleccione al menos un producto.'; alertBox.style.display = 'block'; }
                        return false;
                    }
                    if (!validarTodo()) {
                        showToast('Corrija las cantidades para continuar', 'error');
                        if (alertBox) { alertBox.textContent = 'Corrija las cantidades para continuar.'; alertBox.style.display = 'block'; }
                        return false;
                    }
                    const fd = new FormData(formCrear);
                    // Señalamos ajax/json
                    const url = formCrear.getAttribute('action');
                    const token = formCrear.querySelector('input[name="_token"]').value;
                    try {
                        const resp = await fetch(url, {
                            method: 'POST',
                            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json', 'X-CSRF-TOKEN': token },
                            body: fd
                        });
                        if (resp.ok) {
                            showToast('Alquiler registrado correctamente', 'success');
                            // Cerrar modal y limpiar
                            const modalEl = document.getElementById('modalCrear');
                            if (modalEl) {
                                const inst = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                                inst.hide();
                            }
                            formCrear.reset();
                            contSeleccionados.innerHTML = '';
                            if (badgeCount) badgeCount.textContent = '0';
                        } else if (resp.status === 422) {
                            const data = await resp.json();
                            const messages = data?.errors ? Object.values(data.errors).flat() : ['Error de validación'];
                            const msg = messages.join('\n');
                            showToast(messages[0] || 'Error de validación', 'error');
                            if (alertBox) { alertBox.textContent = msg; alertBox.style.display = 'block'; }
                        } else {
                            showToast('Ocurrió un error al guardar', 'error');
                        }
                    } catch (err) {
                        showToast('Ocurrió un error de conexión', 'error');
                    }
                    return false;
                });

                // Toasts
                function showToast(msg, type) {
                    let cont = document.querySelector('.toast-container');
                    if (!cont) {
                        cont = document.createElement('div');
                        cont.className = 'toast-container';
                        document.body.appendChild(cont);
                    }
                    const el = document.createElement('div');
                    el.className = 'toast-item ' + (type || '');
                    el.textContent = msg;
                    cont.appendChild(el);
                    setTimeout(() => { el.remove(); }, 3000);
                }
            }

            // Inicializar al cargar y al abrir el modal (por si el DOM cambia)
            initCrearUI();
            const modalCrearEl = document.getElementById('modalCrear');
            if (modalCrearEl) {
                modalCrearEl.addEventListener('shown.bs.modal', function(){
                    initCrearUI();
                });
            }

            // Reaccionar al cambio de Servicio en crear: refrescar sugerencias de búsqueda
            (function(){
                var filtroServCrear = document.getElementById('servicio_crear');
                var inputBuscar = document.getElementById('buscar_producto');
                if (filtroServCrear && inputBuscar) {
                    filtroServCrear.addEventListener('change', function(){
                        // Disparar input para que vuelva a listar con el nuevo servicio
                        const ev = new Event('focus');
                        inputBuscar.dispatchEvent(ev);
                    });
                }
            })();
        })();
        </script>
    </div>
</body>
</html>
