<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Calendario de Reservas</title>

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
    
    {{-- Modales personalizados --}}
    <div id="customConfirmModal" class="custom-modal" style="display: none;">
        <div class="custom-modal-content">
            <div class="custom-modal-header">
                <h3 class="custom-modal-title">PRO AUDIO</h3>
            </div>
            <div class="custom-modal-body">
                <p id="customConfirmMessage"></p>
            </div>
            <div class="custom-modal-footer">
                <button id="customConfirmAccept" class="custom-btn-accept">Aceptar</button>
                <button id="customConfirmCancel" class="custom-btn-cancel">Cancelar</button>
            </div>
        </div>
    </div>

    <div id="customAlertModal" class="custom-modal" style="display: none;">
        <div class="custom-modal-content">
            <div class="custom-modal-header">
                <h3 class="custom-modal-title">PRO AUDIO</h3>
            </div>
            <div class="custom-modal-body">
                <p id="customAlertMessage"></p>
            </div>
            <div class="custom-modal-footer">
                <button id="customAlertOk" class="custom-btn-accept">Aceptar</button>
            </div>
        </div>
    </div>

    <style>
        .custom-modal {
            position: fixed;
            z-index: 10000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(4px);
        }

        .custom-modal-content {
            background-color: #2d2d2d;
            border-radius: 12px;
            width: 90%;
            max-width: 450px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.5);
            overflow: hidden;
            animation: modalFadeIn 0.3s ease-out;
        }

        @keyframes modalFadeIn {
            from {
                opacity: 0;
                transform: scale(0.9) translateY(-20px);
            }
            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        .custom-modal-header {
            background-color: #1a1a1a;
            padding: 20px 24px;
            border-bottom: 2px solid #e91c1c;
        }

        .custom-modal-title {
            margin: 0;
            font-size: 18px;
            font-weight: 700;
            color: #e91c1c;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .custom-modal-body {
            padding: 24px;
            color: #ffffff;
            font-size: 15px;
            line-height: 1.6;
            text-align: center;
            white-space: pre-line;
        }

        .custom-modal-body p {
            margin: 0;
        }

        .custom-modal-footer {
            padding: 16px 24px;
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            background-color: #1a1a1a;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .custom-btn-accept {
            background-color: #e91c1c;
            color: white;
            border: none;
            padding: 6px 16px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            min-width: 80px;
        }

        .custom-btn-accept:hover {
            background-color: #ff3333;
            box-shadow: 0 4px 12px rgba(233, 28, 28, 0.5);
            transform: translateY(-1px);
        }

        .custom-btn-cancel {
            background-color: #2d2d2d;
            color: white;
            border: 1px solid #6c757d;
            padding: 6px 16px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            min-width: 80px;
        }

        .custom-btn-cancel:hover {
            background-color: #3d3d3d;
            border-color: #8c959d;
            transform: translateY(-1px);
        }
    </style>

    <script>
        window.customAlert = function(message) {
            return new Promise((resolve) => {
                const modal = document.getElementById('customAlertModal');
                const messageEl = document.getElementById('customAlertMessage');
                const okBtn = document.getElementById('customAlertOk');
                
                messageEl.textContent = message;
                modal.style.display = 'flex';
                
                const closeModal = () => {
                    modal.style.display = 'none';
                    resolve();
                };
                
                okBtn.onclick = closeModal;
                
                modal.addEventListener('click', function(e) {
                    if (e.target === modal) {
                        closeModal();
                    }
                });
                
                const escapeHandler = (e) => {
                    if (e.key === 'Escape') {
                        closeModal();
                        document.removeEventListener('keydown', escapeHandler);
                    }
                };
                document.addEventListener('keydown', escapeHandler);
            });
        };

        window.customConfirm = function(message) {
            return new Promise((resolve) => {
                const modal = document.getElementById('customConfirmModal');
                const messageEl = document.getElementById('customConfirmMessage');
                const acceptBtn = document.getElementById('customConfirmAccept');
                const cancelBtn = document.getElementById('customConfirmCancel');
                
                messageEl.textContent = message;
                modal.style.display = 'flex';
                
                const closeModal = () => {
                    modal.style.display = 'none';
                };
                
                acceptBtn.onclick = () => {
                    closeModal();
                    resolve(true);
                };
                
                cancelBtn.onclick = () => {
                    closeModal();
                    resolve(false);
                };
                
                modal.addEventListener('click', function(e) {
                    if (e.target === modal) {
                        closeModal();
                        resolve(false);
                    }
                });
                
                const escapeHandler = (e) => {
                    if (e.key === 'Escape') {
                        closeModal();
                        resolve(false);
                        document.removeEventListener('keydown', escapeHandler);
                    }
                };
                document.addEventListener('keydown', escapeHandler);
            });
        };
    </script>
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
                <a href="{{ route('inicio') }}" class="btn btn-volver btn-volver-page-fixed" title="Volver al inicio">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <div class="calendar-section">
                    {{-- ENCABEZADO --}}
                    <div class="calendar-header">
                        <h2 class="calendar-title">
                            <i class="fas fa-calendar-alt"></i> Calendario de Reservas
                        </h2>
                        @if($esAdmin)
                        <button class="btn btn-crear-alquiler" data-bs-toggle="modal" data-bs-target="#modalCrear">
                            <i class="fas fa-plus"></i> Nueva reserva
                        </button>
                        @endif
                    </div>

                    @if(session('ok'))
                        <div id="alertSuccessMessage" class="alert alert-success" style="animation: slideInDown 0.3s ease-out;">
                            <i class="fas fa-check-circle"></i> {{ session('ok') }}
                        </div>
                    @endif

                    @if($errors->any())
                        <div id="alertErrorMessage" class="alert alert-danger" style="animation: slideInDown 0.3s ease-out;">
                            <i class="fas fa-exclamation-circle"></i> {{ $errors->first() }}
                        </div>
                    @endif

                    {{-- CALENDARIO --}}
                    <div id="calendar" class="calendar-container"></div>

                    
                </div>
            </div>

            {{-- SIDEBAR: Listado de registros --}}
            @if($esAdmin)
            <aside class="calendario-sidebar">
                <div class="sidebar-header">
                    <h3 class="sidebar-title">
                        <i class="fas fa-clock"></i> Reservas pendientes
                        <span class="badge-resumen" id="reservas-badge-count">({{ $reservasPendientes->unique('id')->count() }})</span>
                    </h3>
                </div>

                <div class="sidebar-content" id="reservas-pendientes">
            @forelse($reservasPendientes->unique('id') as $reserva)
                        @php
                            $reservaFechaInicio = \Carbon\Carbon::parse($reserva->fecha_inicio);
                            $reservaFechaFin = \Carbon\Carbon::parse($reserva->fecha_fin);
                            $reservaDias = $reservaFechaInicio->diffInDays($reservaFechaFin) + 1;
                        @endphp
                        <div class="registro-card reserva-card" data-reserva-id="{{ $reserva->id }}">
                            <div class="registro-header">
                                <div class="registro-info">
                                    <span class="registro-fechas">
                                        <i class="fas fa-calendar-alt"></i>
                                        {{ $reservaFechaInicio->format('d/m/Y') }} - {{ $reservaFechaFin->format('d/m/Y') }}
                                    </span>
                                    <span class="registro-dias">
                                        <i class="fas fa-clock"></i> {{ $reservaDias }} {{ $reservaDias === 1 ? 'día' : 'días' }}
                                    </span>
                                </div>
                                <div class="acciones-buttons">
                                    <button class="btn-action btn-success confirm-reserva-btn" data-id="{{ $reserva->id }}">
                                        <i class="fas fa-check"></i> Confirmar
                                    </button>
                                    <button class="btn-action btn-delete cancel-reserva-btn" data-id="{{ $reserva->id }}">
                                        <i class="fas fa-times"></i> Cancelar
                                    </button>
                                </div>
                            </div>
                            <div class="registro-productos">
                                <div class="productos-header">
                                    <i class="fas fa-box"></i> Productos ({{ $reserva->items->count() }})
                                    <span class="cantidad-total">Total: {{ $reserva->cantidad_total }} unidades</span>
                                </div>
                                <div class="productos-lista">
                                    @foreach($reserva->items as $item)
                                        <div class="producto-item-sidebar">
                                            <span class="producto-nombre-sidebar">{{ optional($item->inventario)->descripcion ?? 'Sin descripción' }}</span>
                                            <span class="producto-cantidad-sidebar">x{{ $item->cantidad }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            @if($reserva->descripcion_evento)
                                <div class="registro-descripcion">
                                    <div class="descripcion-icon">
                                        <i class="fas fa-file-alt"></i>
                                    </div>
                                    <div class="descripcion-content">
                                        <span class="descripcion-title">Descripción</span>
                                        <p>{{ Str::limit($reserva->descripcion_evento, 100) }}</p>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="no-records-card">
                            <i class="fas fa-inbox"></i>
                            <p>No hay reservas pendientes</p>
                        </div>
                    @endforelse
                </div>

                <div class="sidebar-header" style="margin-top: 18px;">
                    <h3 class="sidebar-title">
                        <i class="fas fa-list"></i> Reservas confirmadas
                        <span class="badge-resumen" id="sidebar-badge-count">({{ count($registros) }})</span>
                    </h3>
                </div>
                
                <div class="sidebar-content" id="sidebar-content">
                    @forelse($registros as $r)
                        @php
                            // Si tiene items (nuevo formato), mostrar todos los productos
                            $productosLista = [];
                            $cantidadTotal = 0;
                            if ($r->items && $r->items->count() > 0) {
                                foreach ($r->items as $item) {
                                    $mov = collect($movimientos ?? [])->first(function($m) use ($item) {
                                        return $m->id == $item->movimientos_inventario_id;
                                    });
                                    if ($mov && isset($inventarios[$mov->inventario_id])) {
                                        $productosLista[] = [
                                            'nombre' => $inventarios[$mov->inventario_id]->descripcion,
                                            'cantidad' => $item->cantidad
                                        ];
                                        $cantidadTotal += $item->cantidad;
                                    }
                                }
                            } else {
                                // Formato antiguo: un solo producto
                                $movimiento = collect($movimientos ?? [])->first(function($m) use ($r) {
                                    return $m->id == $r->movimientos_inventario_id;
                                });
                                if ($movimiento && isset($inventarios[$movimiento->inventario_id])) {
                                    $cant = $r->cantidad ?? 1;
                                    $productosLista[] = [
                                        'nombre' => $inventarios[$movimiento->inventario_id]->descripcion,
                                        'cantidad' => $cant
                                    ];
                                    $cantidadTotal = $cant;
                                }
                            }
                            $fechaInicio = \Carbon\Carbon::parse($r->fecha_inicio);
                            $fechaFin = \Carbon\Carbon::parse($r->fecha_fin);
                            $diasReserva = $fechaInicio->diffInDays($fechaFin) + 1;
                        @endphp
                        <div class="registro-card">
                            <div class="registro-header">
                                <div class="registro-info">
                                    <span class="registro-fechas">
                                        <i class="fas fa-calendar-alt"></i>
                                        {{ $fechaInicio->format('d/m/Y') }} - {{ $fechaFin->format('d/m/Y') }}
                                    </span>
                                    <span class="registro-dias">
                                        <i class="fas fa-clock"></i> {{ $diasReserva }} {{ $diasReserva == 1 ? 'día' : 'días' }}
                                    </span>
                                </div>
                                <div class="acciones-buttons">
                                    <button class="btn-action btn-edit" data-bs-toggle="modal" data-bs-target="#modalEditar{{ $r->id }}" title="Editar" onclick="var modal = document.getElementById('modalEditar{{ $r->id }}'); if(!modal) { window.location.reload(); return false; }">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form action="{{ route('calendario.eliminar',$r->id) }}" method="POST" class="d-inline delete-form">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn-action btn-delete" title="Eliminar" onclick="event.preventDefault(); customConfirm('¿Está seguro de eliminar este registro?').then(result => { if(result) this.closest('form').submit(); }); return false;">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                            
                            <div class="registro-productos">
                                <div class="productos-header">
                                    <i class="fas fa-box"></i> Productos ({{ count($productosLista) }})
                                    <span class="cantidad-total">Total: {{ $cantidadTotal }} unidades</span>
                                </div>
                                <div class="productos-lista">
                                    @foreach($productosLista as $prod)
                                        <div class="producto-item-sidebar">
                                            <span class="producto-nombre-sidebar">{{ $prod['nombre'] }}</span>
                                            <span class="producto-cantidad-sidebar">x{{ $prod['cantidad'] }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            
                            @if($r->descripcion_evento)
                                <div class="registro-descripcion">
                                    <div class="descripcion-icon">
                                        <i class="fas fa-file-alt"></i>
                                    </div>
                                    <div class="descripcion-content">
                                        <span class="descripcion-title">Descripción</span>
                                        <p>{{ Str::limit($r->descripcion_evento, 100) }}</p>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="no-records-card">
                            <i class="fas fa-inbox"></i>
                            <p>No hay registros disponibles</p>
                        </div>
                    @endforelse
                </div>
            </aside>
            @endif
        </div>

        @if($esAdmin)
        {{-- MODALES DE EDICIÓN (fuera del contenedor del listado) --}}
        @foreach($registros as $r)
        @php
            $tieneItems = $r->items && $r->items->count() > 0;
        @endphp
        <div class="modal fade calendar-modal" id="modalEditar{{ $r->id }}" tabindex="-1" aria-labelledby="modalEditarLabel{{ $r->id }}" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-scrollable resizable-modal" style="max-width: 90%; width: 90%; min-width: 800px; max-height: 90vh;">
                <div class="modal-content" style="max-height: 90vh; display: flex; flex-direction: column; height: auto;">
                    <form action="{{ route('calendario.actualizar', $r->id) }}" method="POST" style="display: flex; flex-direction: column; min-height: 0; flex: 1;">
                                                    @csrf
                                                    @method('PUT')

                        <div class="modal-header" style="flex-shrink: 0;">
                                                        <h5 class="modal-title">
                                                            <i class="fas fa-edit"></i> Editar Reserva
                                                        </h5>
                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                    </div>

                        <div class="modal-body" style="overflow-y: auto; overflow-x: hidden; flex: 1 1 auto; min-height: 0; max-height: none;">
                                                        <div id="alertEditar{{ $r->id }}" class="alert alert-danger" style="display: none;"></div>
                                                        <div class="mb-3">
                                                            <label class="form-label" for="servicio_editar{{ $r->id }}">Servicio *</label>
                                                            @php
                                                                $servicioSeleccionado = optional($r->reserva)->servicio ?? '';
                                                            @endphp
                                                            <select id="servicio_editar{{ $r->id }}" name="servicio" class="form-select" required>
                                                                <option value="">Seleccione un servicio</option>
                                                                <option value="Publicidad" {{ $servicioSeleccionado === 'Publicidad' ? 'selected' : '' }}>Publicidad</option>
                                                                <option value="Alquiler" {{ $servicioSeleccionado === 'Alquiler' ? 'selected' : '' }}>Alquiler</option>
                                                                <option value="Animación" {{ $servicioSeleccionado === 'Animación' ? 'selected' : '' }}>Animación</option>
                                                            </select>
                                                        </div>
                            @if(!$tieneItems)
                                                        <div class="mb-3">
                                                            <label class="form-label" for="movimiento_editar{{ $r->id }}">Subservicio / Producto *</label>
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
                            @else
                            <div class="mb-3">
                                <label class="form-label" for="select_producto_editar{{ $r->id }}">Productos de la Reserva *</label>
                                <div id="productos_editar{{ $r->id }}" class="table-container" style="max-height:280px;overflow:auto;padding:10px;">
                                    <div style="position:relative;">
                                        <select id="select_producto_editar{{ $r->id }}" class="form-select form-control" style="width:100%;">
                                            <option value="">-- Seleccione un producto del inventario --</option>
                                            @foreach($inventarios ?? [] as $inv)
                                                <option value="{{ $inv->id }}" data-inventario-id="{{ $inv->id }}" data-stock="{{ $inv->stock ?? 0 }}" data-descripcion="{{ $inv->descripcion ?? '' }}">
                                                    {{ $inv->descripcion ?? 'Sin descripción' }} - Stock: {{ $inv->stock ?? 0 }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div style="display:flex;align-items:center;gap:10px;margin-top:10px;">
                                        <span class="badge-count" id="badge_seleccionados_editar{{ $r->id }}"><i class="fas fa-box"></i> <span id="count_sel_editar{{ $r->id }}">0</span> seleccionados</span>
                                    </div>
                                    <div id="seleccionados_contenedor_editar{{ $r->id }}" class="seleccionados-lista" style="margin-top:12px;">
                                        @foreach($r->items as $item)
                                            @php
                                                $mov = collect($movimientos ?? [])->first(function($m) use ($item) {
                                                    return $m->id == $item->movimientos_inventario_id;
                                                });
                                                if ($mov && isset($inventarios[$mov->inventario_id])) {
                                                    $invId = $mov->inventario_id;
                                                    $desc = $inventarios[$invId]->descripcion ?? '';
                                                    $stock = $inventarios[$invId]->stock ?? 0;
                                                } else {
                                                    continue;
                                                }
                                            @endphp
                                            <div class="item-row" data-inv-id="{{ $invId }}" style="display:flex;flex-direction:column;gap:10px;padding:12px;min-width:0;border:1px solid rgba(255,255,255,.15);border-radius:8px;background:rgba(0,0,0,.4);">
                                                <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;width:100%;">
                                                    <div style="flex:1;min-width:0;">
                                                        <span class="item-titulo" style="display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;font-weight:600;">{{ $desc }}</span>
                                                        <small class="disponible-actual" style="opacity:.8;font-size:.85em;">Disponible: {{ $stock }}</small>
                                                    </div>
                                                    <button type="button" class="btn btn-sm btn-secondary btn-remove-item" data-inv-id="{{ $invId }}" style="flex-shrink:0;"><i class="fas fa-times"></i></button>
                                                </div>
                                                <div style="display:flex;flex-direction:column;gap:4px;width:100%;">
                                                    <label style="font-size:.85em;opacity:.9;margin-bottom:4px;" for="cantidad_item_{{ $loop->index }}">Cantidad:</label>
                                                    <input type="number" id="cantidad_item_{{ $loop->index }}" min="1" inputmode="numeric" pattern="[0-9]*" data-only-numeric="true" class="form-control form-control-sm" data-stock="{{ $stock }}" name="items[{{ $loop->index }}][cantidad]" value="{{ $item->cantidad }}" placeholder="Cantidad (máx {{ $stock }})" required style="width:100%;">
                                                    <small class="field-error" style="display:none;"></small>
                                                </div>
                                                <input type="hidden" name="items[{{ $loop->index }}][inventario_id]" value="{{ $invId }}">
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                                <small class="text-light field-hint">Seleccione productos del menú desplegable y asigne cantidad. La cantidad no puede exceder el stock disponible.</small>
                            </div>
                            @endif

                                                        <div class="mb-3">
                                                            <label class="form-label" for="fecha_inicio_editar{{ $r->id }}">Fecha inicio *</label>
                                                            <input type="datetime-local" name="fecha_inicio" id="fecha_inicio_editar{{ $r->id }}" value="{{ $r->fecha_inicio ? \Carbon\Carbon::parse($r->fecha_inicio)->format('Y-m-d\TH:i') : '' }}" class="form-control" required>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label class="form-label" for="fecha_fin_editar{{ $r->id }}">Fecha fin *</label>
                                                            <input type="datetime-local" name="fecha_fin" id="fecha_fin_editar{{ $r->id }}" value="{{ $r->fecha_fin ? \Carbon\Carbon::parse($r->fecha_fin)->format('Y-m-d\TH:i') : '' }}" class="form-control" required>
                                                        </div>

                            @if(!$tieneItems)
                                                        <div class="mb-3">
                                                            <label class="form-label" for="cantidad_editar{{ $r->id }}">Cantidad *</label>
                                <input type="number" name="cantidad" id="cantidad_editar{{ $r->id }}" class="form-control" min="1" inputmode="numeric" pattern="[0-9]*" data-only-numeric="true" value="{{ $r->cantidad ?? 1 }}" required>
                                                        </div>
                            @endif

                                                        <div class="mb-3">
                                                            <label class="form-label" for="descripcion_editar{{ $r->id }}">Descripción *</label>
                                                            <textarea name="descripcion_evento" id="descripcion_editar{{ $r->id }}" class="form-control" rows="3" required>{{ $r->descripcion_evento }}</textarea>
                                                        </div>
                                                    </div>

                        <div class="modal-footer" style="flex-shrink: 0 !important; border-top: 1px solid rgba(255,255,255,.1) !important; display: flex !important; visibility: visible !important; opacity: 1 !important;">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="pointer-events: auto !important;">Cerrar</button>
                            <button type="submit" class="btn btn-primary" style="pointer-events: auto !important;">Actualizar</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
        @endforeach

        {{-- MODAL CREAR --}}
        <div class="modal fade calendar-modal" id="modalCrear" tabindex="-1">
            <div class="modal-dialog modal-xl modal-dialog-scrollable resizable-modal" style="max-width: 90%; width: 90%; min-width: 800px; max-height: 90vh;">
                <form class="modal-content" id="formCrear" method="POST" action="{{ route('reservas.store') }}" style="max-height: 90vh; display: flex; flex-direction: column;">
                    @csrf
                    <div class="modal-header" style="flex-shrink: 0;">
                        <h5 class="modal-title">
                            <i class="fas fa-plus-circle"></i> Nueva Reserva
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body alquiler-box" style="overflow-y: auto; overflow-x: hidden; flex: 1; min-height: 0; max-height: calc(90vh - 120px);">
                        <div id="alertCrear" class="alert alert-danger" style="display: none;"></div>
                        <div class="mb-2">
                            <label for="servicio_crear">Servicio *</label>
                            <select id="servicio_crear" name="servicio" class="form-control" required>
                                <option value="">Seleccione un servicio</option>
                                <option value="Publicidad" {{ old('servicio') === 'Publicidad' ? 'selected' : '' }}>Publicidad</option>
                                <option value="Alquiler" {{ old('servicio') === 'Alquiler' ? 'selected' : '' }}>Alquiler</option>
                                <option value="Animación" {{ old('servicio') === 'Animación' ? 'selected' : '' }}>Animación</option>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label for="select_producto">Subservicios / Productos *</label>
                            <div id="productos_crear" class="table-container" style="max-height:280px;overflow:auto;padding:10px;">
                                <div style="position:relative;">
                                    <select id="select_producto" class="form-select form-control" style="width:100%;">
                                        <option value="">-- Seleccione un producto del inventario --</option>
                                        @foreach($inventarios ?? [] as $inv)
                                            <option value="{{ $inv->id }}" data-inventario-id="{{ $inv->id }}" data-stock="{{ $inv->stock ?? 0 }}" data-descripcion="{{ $inv->descripcion ?? '' }}">
                                                {{ $inv->descripcion ?? 'Sin descripción' }} - Stock: {{ $inv->stock ?? 0 }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div style="display:flex;align-items:center;gap:10px;margin-top:10px;">
                                    <span class="badge-count" id="badge_seleccionados"><i class="fas fa-box"></i> <span id="count_sel">0</span> seleccionados</span>
                                </div>
                                <div id="seleccionados_contenedor" class="seleccionados-lista" style="margin-top:12px;"></div>
                            </div>
                            <small class="text-light field-hint">Seleccione productos del menú desplegable y asigne cantidad. La cantidad no puede exceder el stock disponible.</small>
                        </div>
                        <!-- Cantidades por producto arriba -->
                        <div class="mb-2">
                            <label for="fecha_inicio_crear">Fecha inicio *</label>
                            <input type="datetime-local" name="fecha_inicio" id="fecha_inicio_crear" class="form-control" required>
                        </div>
                        <div class="mb-2">
                            <label for="fecha_fin_crear">Fecha fin *</label>
                            <input type="datetime-local" name="fecha_fin" id="fecha_fin_crear" class="form-control" required>
                        </div>
                        <div class="mb-2">
                            <label for="descripcion_crear">Descripción *</label>
                            <textarea name="descripcion_evento" id="descripcion_crear" class="form-control" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer" style="flex-shrink: 0; border-top: 1px solid rgba(255,255,255,.1);">
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

        {{-- Script para ocultar mensajes automáticamente --}}
        <script>
            // Ocultar mensajes de alert automáticamente después de 4 segundos
            document.addEventListener('DOMContentLoaded', function() {
                const successAlert = document.getElementById('alertSuccessMessage');
                const errorAlert = document.getElementById('alertErrorMessage');
                document.querySelectorAll('[data-only-numeric]').forEach(bindNumericSanitizer);
                
                if (successAlert) {
                    setTimeout(function() {
                        successAlert.style.animation = 'slideOutUp 0.3s ease-out';
                        setTimeout(function() {
                            successAlert.style.display = 'none';
                        }, 300);
                    }, 4000);
                }
                
                if (errorAlert) {
                    setTimeout(function() {
                        errorAlert.style.animation = 'slideOutUp 0.3s ease-out';
                        setTimeout(function() {
                            errorAlert.style.display = 'none';
                        }, 300);
                    }, 5000);
                }
                if (typeof window.reloadReservas === 'function') {
                    window.reloadReservas();
                }
            });
            
            // Función helper para escapar HTML (debe estar disponible globalmente)
            window.escapeHtml = function(text) {
                if (!text) return '';
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            };
            
            // Función global para recargar el sidebar de registros (definida antes de DOMContentLoaded)
            window.reloadSidebar = async function() {
                try {
                    const response = await fetch('{{ route('calendario.registros') }}', {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    
                    if (response.ok) {
                        const data = await response.json();
                        const sidebarContent = document.getElementById('sidebar-content');
                        const badgeCount = document.getElementById('sidebar-badge-count');
                        
                        if (!sidebarContent) {
                            console.warn('No se encontró el elemento sidebar-content');
                            return Promise.resolve();
                        }
                        
                        // Actualizar el badge de conteo
                        if (badgeCount) {
                            badgeCount.textContent = '(' + data.total + ')';
                        }
                        
                        // Limpiar el contenido actual
                        sidebarContent.innerHTML = '';
                        
                        if (data.registros && data.registros.length > 0) {
                            // Generar HTML para cada registro
                            data.registros.forEach(function(registro) {
                                const card = document.createElement('div');
                                card.className = 'registro-card';
                                
                                let productosHTML = '';
                                registro.productos.forEach(function(prod) {
                                    productosHTML += `
                                        <div class="producto-item-sidebar">
                                            <span class="producto-nombre-sidebar">${window.escapeHtml(prod.nombre)}</span>
                                            <span class="producto-cantidad-sidebar">x${prod.cantidad}</span>
                                        </div>
                                    `;
                                });

                                const descripcionHTML = registro.descripcion_evento
                                    ? `<div class="registro-descripcion">
                                        <i class="fas fa-info-circle"></i>
                                        <span>${window.escapeHtml(registro.descripcion_evento.length > 100 ? registro.descripcion_evento.substring(0, 100) + '...' : registro.descripcion_evento)}</span>
                                    </div>`
                                    : '';
                                
                                const diasTexto = registro.dias_reserva == 1 ? 'día' : 'días';
                                
                                card.innerHTML = `
                                    <div class="registro-header">
                                        <div class="registro-info">
                                            <span class="registro-fechas">
                                                <i class="fas fa-calendar-alt"></i>
                                                ${registro.fecha_inicio} - ${registro.fecha_fin}
                                            </span>
                                            <span class="registro-dias">
                                                <i class="fas fa-clock"></i> ${registro.dias_reserva} ${diasTexto}
                                            </span>
                                        </div>
                                        <div class="acciones-buttons">
                                            <button class="btn-action btn-edit" data-bs-toggle="modal" data-bs-target="#modalEditar${registro.id}" title="Editar" onclick="var modal = document.getElementById('modalEditar${registro.id}'); if(!modal) { window.location.reload(); return false; }">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <form action="{{ url('calendario') }}/${registro.id}" method="POST" class="d-inline delete-form">
                                                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                                <input type="hidden" name="_method" value="DELETE">
                                                <button type="submit" class="btn-action btn-delete" title="Eliminar" onclick="event.preventDefault(); customConfirm('¿Está seguro de eliminar este registro?').then(result => { if(result) this.closest('form').submit(); }); return false;">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                    <div class="registro-productos">
                                        <div class="productos-header">
                                            <i class="fas fa-box"></i> Productos (${registro.productos.length})
                                            <span class="cantidad-total">Total: ${registro.cantidad_total} unidades</span>
                                        </div>
                                        <div class="productos-lista">
                                            ${productosHTML}
                                        </div>
                                    </div>
                                    ${descripcionHTML}
                                `;
                                
                                sidebarContent.appendChild(card);
                            });
                        } else {
                            // Mostrar mensaje de "no hay registros"
                            sidebarContent.innerHTML = `
                                <div class="no-records-card">
                                    <i class="fas fa-inbox"></i>
                                    <p>No hay registros disponibles</p>
                                </div>
                            `;
                        }
                        
                        return Promise.resolve();
                    } else {
                        console.error('Error al obtener registros:', response.status);
                        return Promise.reject(new Error('Error al obtener registros'));
                    }
                } catch (error) {
                    console.error('Error al recargar el sidebar:', error);
                    return Promise.reject(error);
                }
            };

            const reservasEndpoint = @json($esAdmin ? route('reservas.index') : null);
            const reservasBaseUrl = @json($esAdmin ? url('reservas') : null);
            const csrfToken = "{{ csrf_token() }}";

            if (reservasEndpoint && reservasBaseUrl) {
                function renderReservasPendientes(reservas) {
                    const container = document.getElementById('reservas-pendientes');
                    const badge = document.getElementById('reservas-badge-count');
                    if (!container) {
                        return;
                    }

                    const pendientes = Array.isArray(reservas)
                        ? reservas
                            .filter(function(reserva) { return reserva && reserva.estado === 'pendiente'; })
                            .filter(function(reserva, index, arr) {
                                return arr.findIndex(function(other) { return other && reserva && other.id === reserva.id; }) === index;
                            })
                        : [];

                    reservasPendientesData = pendientes.map(function(reserva) {
                        const items = Array.isArray(reserva.items) ? reserva.items : [];
                        return {
                            id: reserva.id,
                            fecha_inicio: reserva.fecha_inicio,
                            fecha_fin: reserva.fecha_fin,
                            items: items.map(function(item) {
                                const invId = item && item.inventario_id !== undefined ? item.inventario_id : item?.pivot?.inventario_id;
                                const cantidad = item && item.cantidad !== undefined ? item.cantidad : item?.pivot?.cantidad;
                                return {
                                    inventario_id: parseInt(invId ?? 0),
                                    cantidad: parseInt(cantidad ?? 0),
                                };
                            }),
                        };
                    });

                    if (badge) {
                        badge.textContent = '(' + pendientes.length + ')';
                    }

                    if (pendientes.length === 0) {
                        container.innerHTML = `
                            <div class="no-records-card">
                                <i class="fas fa-inbox"></i>
                                <p>No hay reservas pendientes</p>
                            </div>
                        `;
                        return;
                    }

                    const formatter = new Intl.DateTimeFormat('es-ES');
                    container.innerHTML = pendientes.map(function(reserva) {
                        const fechaInicio = reserva && reserva.fecha_inicio ? formatter.format(new Date(reserva.fecha_inicio)) : '';
                        const fechaFin = reserva && reserva.fecha_fin ? formatter.format(new Date(reserva.fecha_fin)) : '';
                        const createdItems = (reserva && Array.isArray(reserva.items)) ? reserva.items : [];
                        const descripcion = reserva && reserva.descripcion_evento ? reserva.descripcion_evento : '';
                        const diasReserva = (reserva && reserva.fecha_inicio && reserva.fecha_fin)
                            ? Math.round((new Date(reserva.fecha_fin) - new Date(reserva.fecha_inicio)) / (1000 * 60 * 60 * 24)) + 1
                            : 1;

                        const productosHTML = createdItems.map(function(item) {
                            const inventario = item && item.inventario ? item.inventario : null;
                            const nombre = inventario && inventario.descripcion ? inventario.descripcion : 'Sin descripción';
                            const cantidad = item && item.cantidad ? item.cantidad : 0;
                            return `
                                <div class="producto-item-sidebar">
                                    <span class="producto-nombre-sidebar">${window.escapeHtml(nombre)}</span>
                                    <span class="producto-cantidad-sidebar">x${cantidad}</span>
                                </div>
                            `;
                        }).join('');

                        return `
                            <div class="registro-card reserva-card" data-reserva-id="${reserva.id}">
                                <div class="registro-header">
                                    <div class="registro-info">
                                        <span class="registro-fechas">
                                            <i class="fas fa-calendar-alt"></i>
                                            ${window.escapeHtml(fechaInicio)} - ${window.escapeHtml(fechaFin)}
                                        </span>
                                        <span class="registro-dias">
                                            <i class="fas fa-clock"></i> ${diasReserva} ${diasReserva === 1 ? 'día' : 'días'}
                                        </span>
                                    </div>
                                    <div class="acciones-buttons">
                                        <button class="btn-action btn-success confirm-reserva-btn" data-id="${reserva.id}">
                                            <i class="fas fa-check"></i> Confirmar
                                        </button>
                                        <button class="btn-action btn-delete cancel-reserva-btn" data-id="${reserva.id}">
                                            <i class="fas fa-times"></i> Cancelar
                                        </button>
                                    </div>
                                </div>
                                <div class="registro-productos">
                                    <div class="productos-header">
                                        <i class="fas fa-box"></i> Productos (${createdItems.length})
                                        <span class="cantidad-total">Total: ${(reserva && reserva.cantidad_total) ? reserva.cantidad_total : 0} unidades</span>
                                    </div>
                                    <div class="productos-lista">
                                        ${productosHTML}
                                    </div>
                                </div>
                                ${descripcion
                                    ? `
                                        <div class="registro-descripcion">
                                            <div class="descripcion-icon">
                                                <i class="fas fa-file-alt"></i>
                                            </div>
                                            <div class="descripcion-content">
                                                <span class="descripcion-title">Descripción</span>
                                                <p>${window.escapeHtml(descripcion.length > 100 ? descripcion.substring(0, 100) + '...' : descripcion)}</p>
                                            </div>
                                        </div>
                                    `
                                    : ''}
                            </div>
                        `;
                    }).join('');
                }

                window.reloadReservas = async function() {
                    try {
                    const response = await fetch(reservasEndpoint, {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });
                        if (!response.ok) {
                            throw new Error('No se pudieron cargar las reservas pendientes');
                        }
                        const data = await response.json();
                        renderReservasPendientes(Array.isArray(data) ? data : []);
                    } catch (error) {
                        console.error('Error al recargar reservas:', error);
                    }
                };

                async function confirmarReservaRequest(id) {
                const response = await fetch(reservasBaseUrl + '/' + id + '/confirmar', {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                    });
                    const data = await response.json().catch(function() { return {}; });
                    if (!response.ok) {
                        throw new Error(data.error || data.message || 'No fue posible confirmar la reserva');
                    }
                    return data;
                }

                async function cancelarReservaRequest(id) {
                const response = await fetch(reservasBaseUrl + '/' + id, {
                        method: 'DELETE',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                    });
                    const data = await response.json().catch(function() { return {}; });
                    if (!response.ok) {
                        throw new Error(data.error || data.message || 'No fue posible cancelar la reserva');
                    }
                    return data;
                }

                document.addEventListener('click', async function(event) {
                    const confirmBtn = event.target.closest('.confirm-reserva-btn');
                    const cancelBtn = event.target.closest('.cancel-reserva-btn');
                    if (!confirmBtn && !cancelBtn) {
                        return;
                    }
                    event.preventDefault();

                    const id = confirmBtn ? confirmBtn.dataset.id : (cancelBtn ? cancelBtn.dataset.id : null);
                    if (!id) {
                        return;
                    }

                    const actionBtn = confirmBtn || cancelBtn;
                    let originalBtnHtml = '';
                    if (actionBtn) {
                        originalBtnHtml = actionBtn.innerHTML;
                        actionBtn.disabled = true;
                        actionBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                    }

                    try {
                        if (confirmBtn) {
                            const data = await confirmarReservaRequest(id);
                            showToast(data.message || 'Reserva confirmada correctamente.', 'success');
                            await reloadReservas();
                            if (typeof window.refreshInventarioData === 'function') {
                                await window.refreshInventarioData();
                            }
                            if (typeof window.reloadSidebar === 'function') {
                                await window.reloadSidebar();
                            }
                            if (typeof window.reloadCalendar === 'function') {
                                window.reloadCalendar();
                            }
                        } else if (cancelBtn) {
                            const data = await cancelarReservaRequest(id);
                            showToast(data.message || 'Reserva cancelada correctamente.', 'info');
                            await reloadReservas();
                            if (typeof window.refreshInventarioData === 'function') {
                                await window.refreshInventarioData();
                            }
                            if (typeof window.reloadCalendar === 'function') {
                                window.reloadCalendar();
                            }
                        }
                    } catch (error) {
                        console.error(error);
                        showToast(error.message || 'No fue posible procesar la reserva.', 'error');
                    } finally {
                        if (actionBtn) {
                            actionBtn.disabled = false;
                            actionBtn.innerHTML = originalBtnHtml || '<i class="fas fa-check"></i>';
                        }
                    }
                });
            } else {
                window.reloadReservas = function() {
                    return Promise.resolve();
                };
            }
        </script>

        <style>
            @keyframes slideInDown {
                from {
                    opacity: 0;
                    transform: translateY(-20px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
            
            @keyframes slideOutUp {
                from {
                    opacity: 1;
                    transform: translateY(0);
                }
                to {
                    opacity: 0;
                    transform: translateY(-20px);
                }
            }
            
            .alert {
                transition: all 0.3s ease;
            }
        </style>

        {{-- FULLCALENDAR SCRIPT --}}
        <script>
            // Variables globales para el calendario
            var eventos = @json($eventos);
            var calendarInstance = null;
            var reservedDatesGlobal = null;
            var dayColorStyles = {};

            function ymd(dateObj) {
                var y = dateObj.getFullYear();
                var m = String(dateObj.getMonth() + 1).padStart(2, '0');
                var d = String(dateObj.getDate()).padStart(2, '0');
                return y + '-' + m + '-' + d;
            }

            function clearDayHighlight(cellEl) {
                if (!cellEl) return;
                
                cellEl.classList.remove('day-reserved');
                cellEl.style.removeProperty('--reserved-border');
                delete cellEl.dataset.reservationsCount;
                
                var frame = cellEl.querySelector('.fc-daygrid-day-frame');
                if (frame) {
                    var dotsContainer = frame.querySelector('.reservation-dots');
                    if (dotsContainer) {
                        dotsContainer.remove();
                    }
                }
            }

            function applyDayHighlight(cellEl, styles) {
                if (!cellEl || !styles || styles.length === 0) {
                    clearDayHighlight(cellEl);
                    return;
                }

                var frame = cellEl.querySelector('.fc-daygrid-day-frame');
                if (!frame) {
                    return;
                }

                var dotsContainer = frame.querySelector('.reservation-dots');
                if (!dotsContainer) {
                    dotsContainer = document.createElement('div');
                    dotsContainer.className = 'reservation-dots';
                    frame.appendChild(dotsContainer);
                } else {
                    dotsContainer.innerHTML = '';
                }

                var maxDots = 6;
                styles.slice(0, maxDots).forEach(function(styleInfo) {
                    var dot = document.createElement('span');
                    dot.className = 'reservation-dot';
                    var bgColor = styleInfo.bg || styleInfo.color || '#e91c1c';
                    var borderColor = styleInfo.border || bgColor;
                    dot.style.setProperty('--dot-color', bgColor);
                    dot.style.setProperty('--dot-border', borderColor);
                    dotsContainer.appendChild(dot);
                });

                cellEl.style.setProperty('--reserved-border', styles[0].border || styles[0].bg || '#e91c1c');
                cellEl.dataset.reservationsCount = styles.length;
                cellEl.classList.add('day-reserved');
            }

            function recalculateReservedData(listaEventos) {
                reservedDatesGlobal = new Set();
                dayColorStyles = {};

                (listaEventos || []).forEach(function(e) {
                    try {
                        var startDate = new Date(e.start);
                        var endDate = e.end ? new Date(e.end) : new Date(e.start);
                        var start = new Date(startDate.getFullYear(), startDate.getMonth(), startDate.getDate());
                        var end = new Date(endDate.getFullYear(), endDate.getMonth(), endDate.getDate());

                        var cursor = new Date(start.getTime());
                        while (cursor <= end) {
                            var key = ymd(cursor);
                            reservedDatesGlobal.add(key);
                            if (!dayColorStyles[key]) {
                                dayColorStyles[key] = [];
                            }
                            dayColorStyles[key].push({
                                bg: e.backgroundColor || e.color || '#e91c1c',
                                border: e.borderColor || e.backgroundColor || e.color || '#e91c1c',
                                text: e.textColor || '#ffffff'
                            });
                            cursor.setDate(cursor.getDate() + 1);
                        }
                    } catch (err) {
                        // noop
                    }
                });
            }
            
            document.addEventListener('DOMContentLoaded', function() {
                var calendarEl = document.getElementById('calendar');
                var isAdmin = @json($esAdmin);
                
                recalculateReservedData(eventos);

                calendarInstance = new FullCalendar.Calendar(calendarEl, {
                    initialView: 'dayGridMonth',
                    locale: 'es',
                    firstDay: 1,
                    events: eventos,
                    eventDisplay: 'none',
                    displayEventTime: false,
                    displayEventEnd: false,
                    height: 'auto',
                    contentHeight: 'auto',
                    aspectRatio: 1.6,
                    dayCellDidMount: function(arg) {
                        var d = arg.date;
                        var key = ymd(d);
                        clearDayHighlight(arg.el);
                        if (dayColorStyles[key] && dayColorStyles[key].length) {
                            applyDayHighlight(arg.el, dayColorStyles[key]);
                        } else if (reservedDatesGlobal && reservedDatesGlobal.has(key)) {
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
                        const mensaje = "Producto: " + info.event.title + "\nDescripción: " + descripcion + "\nFecha de inicio: " + inicio + "\nFecha de fin: " + fin;
                        customAlert(mensaje);
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

                calendarInstance.render();
                
                // Función global para recargar el calendario sin refrescar la página
                window.reloadCalendar = async function() {
                    try {
                        const response = await fetch('{{ route('calendario.eventos') }}', {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });
                        
                        if (response.ok) {
                            const nuevosEventos = await response.json();
                            
                            // Actualizar la variable global de eventos
                            eventos = nuevosEventos;
                            
                            // Recalcular días reservados y colores
                            recalculateReservedData(nuevosEventos);
                            
                            // Remover todos los eventos actuales y agregar los nuevos
                            if (calendarInstance) {
                                calendarInstance.removeAllEvents();
                                if (nuevosEventos && nuevosEventos.length > 0) {
                                    calendarInstance.addEventSource(nuevosEventos);
                                }
                                // Forzar re-renderizado completo del calendario
                                calendarInstance.render();
                            }
                        }
                    } catch (error) {
                        console.error('Error al recargar el calendario:', error);
                        showToast('Error al recargar el calendario', 'error');
                    }
                };
                
                // La función reloadSidebar ya está definida globalmente arriba
                // Solo la función escapeHtml local (si es necesaria para otros usos)
            });

            // Hacer modales redimensionables
            function makeModalResizable(modalId) {
                const modal = document.getElementById(modalId);
                if (!modal) return;
                
                const modalDialog = modal.querySelector('.modal-dialog.resizable-modal');
                if (!modalDialog) return;
                
                let isResizing = false;
                let startX, startY, startWidth, startHeight;
                
                // Crear handle de redimensionamiento
                let resizeHandle = modalDialog.querySelector('.resize-handle');
                if (!resizeHandle) {
                    resizeHandle = document.createElement('div');
                    resizeHandle.className = 'resize-handle';
                    modalDialog.appendChild(resizeHandle);
                }
                
                resizeHandle.addEventListener('mousedown', function(e) {
                    isResizing = true;
                    startX = e.clientX;
                    startY = e.clientY;
                    startWidth = parseInt(document.defaultView.getComputedStyle(modalDialog).width, 10);
                    startHeight = parseInt(document.defaultView.getComputedStyle(modalDialog).height, 10);
                    e.preventDefault();
                });
                
                document.addEventListener('mousemove', function(e) {
                    if (!isResizing) return;
                    
                    const width = startWidth + e.clientX - startX;
                    const height = startHeight + e.clientY - startY;
                    
                    modalDialog.style.width = Math.max(800, width) + 'px';
                    modalDialog.style.height = Math.max(400, height) + 'px';
                    modalDialog.style.maxWidth = 'none';
                    modalDialog.style.maxHeight = 'none';
                });
                
                document.addEventListener('mouseup', function() {
                    isResizing = false;
                });
                
                // Resetear cuando se cierra
                modal.addEventListener('hidden.bs.modal', function() {
                    modalDialog.style.width = '';
                    modalDialog.style.height = '';
                    modalDialog.style.maxWidth = '90%';
                    modalDialog.style.maxHeight = '90vh';
                });
            }
            
            // Hacer redimensionable el modal de crear
            document.addEventListener('DOMContentLoaded', function() {
                makeModalResizable('modalCrear');
                
                @if(session('role') === 'Administrador')
                @foreach($registros as $r)
                makeModalResizable('modalEditar{{ $r->id }}');
                @endforeach
                @endif
            });
            
            // También hacer redimensionable cuando se abre el modal
            document.addEventListener('shown.bs.modal', function(e) {
                const modalId = e.target.id;
                if (modalId.startsWith('modalEditar') || modalId === 'modalCrear') {
                    makeModalResizable(modalId);
                }
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

            // Validar formulario de crear - SOLO VALIDACIÓN DE FECHAS EN TIEMPO REAL
            var formCrear = document.getElementById('formCrear');
            if (formCrear) {
                // Validar en tiempo real (NO prevenir submit aquí, se hace en el listener principal)
                document.getElementById('fecha_inicio_crear').addEventListener('change', function() {
                    validarFechas('fecha_inicio_crear', 'fecha_fin_crear', 'alertCrear', 'formCrear');
                });

                document.getElementById('fecha_fin_crear').addEventListener('change', function() {
                    validarFechas('fecha_inicio_crear', 'fecha_fin_crear', 'alertCrear', 'formCrear');
                });
            }

            // Función showToast global para usar en todos los modales
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

            const numericInputsBound = new WeakSet();
            function bindNumericSanitizer(input) {
                if (!input || numericInputsBound.has(input)) return;
                const handler = () => {
                    if (!input.value) {
                        input.value = '';
                        return;
                    }
                    const digits = input.value.replace(/\D/g, '');
                    if (input.value !== digits) {
                        input.value = digits;
                    }
                    if (digits) {
                        const minAttr = input.getAttribute('min');
                        if (minAttr !== null && minAttr !== '') {
                            const minVal = parseInt(minAttr, 10);
                            if (!Number.isNaN(minVal) && parseInt(input.value || '0', 10) < minVal) {
                                input.value = String(minVal);
                            }
                        }
                    }
                };
                input.addEventListener('input', handler);
                input.addEventListener('blur', handler);
                numericInputsBound.add(input);
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
                    var isSubmitting = false;
                    
                    form.addEventListener('submit', async function(e) {
                            e.preventDefault();
                        e.stopPropagation();
                        
                        // Prevenir doble envío
                        if (isSubmitting) {
                            showToast('Ya se está procesando la actualización, por favor espere...', 'warning');
                            return false;
                        }
                        
                        // Validar fechas
                        if (!validarFechas(fechaInicioId, fechaFinId, alertId, formId)) {
                            return false;
                        }
                        
                        // Validar que haya productos si tiene items
                        @php
                            $tieneItems = $r->items && $r->items->count() > 0;
                        @endphp
                        @if($tieneItems)
                        var itemsCont = document.getElementById('seleccionados_contenedor_editar{{ $r->id }}');
                        if (itemsCont && itemsCont.querySelectorAll('.item-row').length === 0) {
                            showToast('Debe tener al menos un producto en la lista', 'error');
                            var alertEl = document.getElementById(alertId);
                            if (alertEl) {
                                alertEl.textContent = 'Debe tener al menos un producto en la lista.';
                                alertEl.style.display = 'block';
                            }
                            return false;
                        }
                        @endif
                        
                        isSubmitting = true;
                        var submitBtn = form.querySelector('button[type="submit"]');
                        var originalText = submitBtn ? submitBtn.innerHTML : '';
                        if (submitBtn) {
                            submitBtn.disabled = true;
                            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Actualizando...';
                        }
                        
                        var formData = new FormData(form);
                        var url = form.getAttribute('action');
                        var token = form.querySelector('input[name="_token"]').value;
                        
                        try {
                            var resp = await fetch(url, {
                                method: 'POST',
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': token
                                },
                                body: formData
                            });
                            
                            var data;
                            var contentType = resp.headers.get('content-type');
                            if (contentType && contentType.includes('application/json')) {
                                data = await resp.json();
                            } else {
                                var text = await resp.text();
                                try {
                                    data = JSON.parse(text);
                                } catch {
                                    // Si es HTML (redirect), considerarlo éxito
                                    if (resp.ok || resp.redirected || resp.status === 200) {
                                        showToast('Reserva actualizada correctamente', 'success');
                                        // Cerrar el modal de edición
                                        var modalEditarEl = document.getElementById('modalEditar{{ $r->id }}');
                                        if (modalEditarEl) {
                                            const inst = bootstrap.Modal.getInstance(modalEditarEl);
                                            if (inst) inst.hide();
                                        }
                                        // Recargar solo el calendario sin refrescar la página
                                        if (typeof window.reloadCalendar === 'function') {
                                            setTimeout(() => {
                                                window.reloadCalendar();
                                            }, 500);
                                        } else {
                                            // Fallback: recargar página si la función no está disponible
                                            setTimeout(() => {
                                                window.location.reload();
                                            }, 1000);
                                        }
                                        return;
                                    }
                                    data = { message: text || 'Error desconocido al procesar la solicitud' };
                                }
                            }
                            
                            if (resp.ok || resp.redirected || resp.status === 200) {
                                showToast('Reserva actualizada correctamente', 'success');
                                // Cerrar el modal de edición
                                var modalEditarEl = document.getElementById('modalEditar{{ $r->id }}');
                                if (modalEditarEl) {
                                    const inst = bootstrap.Modal.getInstance(modalEditarEl);
                                    if (inst) inst.hide();
                                }
                                // Recargar el calendario y el sidebar sin refrescar la página
                                if (typeof window.reloadCalendar === 'function') {
                                    setTimeout(() => {
                                        window.reloadCalendar();
                                        // También recargar el sidebar
                                        if (typeof window.reloadSidebar === 'function') {
                                            window.reloadSidebar();
                                        }
                                        if (typeof window.refreshInventarioData === 'function') {
                                            window.refreshInventarioData();
                                        }
                                    }, 500);
                                } else {
                                    // Fallback: recargar página si la función no está disponible
                                    setTimeout(() => {
                                        window.location.reload();
                                    }, 1000);
                                }
                            } else if (resp.status === 422) {
                                // Error de validación
                                var errorMessages = [];
                                if (data?.errors) {
                                    Object.keys(data.errors).forEach(function(campo) {
                                        var erroresCampo = data.errors[campo];
                                        if (Array.isArray(erroresCampo)) {
                                            erroresCampo.forEach(function(error) {
                                                errorMessages.push(error);
                                            });
                                        } else {
                                            errorMessages.push(erroresCampo);
                                        }
                                    });
                                }
                                if (errorMessages.length === 0 && data?.message) {
                                    errorMessages.push(data.message);
                                }
                                showToast(errorMessages.join('. ') || 'Error de validación', 'error');
                                var alertEl = document.getElementById(alertId);
                                if (alertEl) {
                                    alertEl.innerHTML = errorMessages.join('<br>');
                                    alertEl.style.display = 'block';
                                }
                            } else {
                                var errorMsg = data?.message || data?.error || 'Error al actualizar el alquiler';
                                showToast(errorMsg, 'error');
                                var alertEl = document.getElementById(alertId);
                                if (alertEl) {
                                    alertEl.textContent = errorMsg;
                                    alertEl.style.display = 'block';
                                }
                            }
                        } catch (error) {
                            console.error('Error:', error);
                            showToast('Error al conectar con el servidor', 'error');
                            var alertEl = document.getElementById(alertId);
                            if (alertEl) {
                                alertEl.textContent = 'Error al conectar con el servidor.';
                                alertEl.style.display = 'block';
                            }
                        } finally {
                            isSubmitting = false;
                            if (submitBtn) {
                                submitBtn.disabled = false;
                                submitBtn.innerHTML = originalText || 'Actualizar';
                            }
                        }
                        
                        return false;
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
                
                // Asegurar que el modal sea interactivo
                var modalEditar = document.getElementById('modalEditar{{ $r->id }}');
                if (modalEditar) {
                    modalEditar.addEventListener('shown.bs.modal', function() {
                        var modal = this;
                        
                        // Forzar que el modal sea interactivo - CRÍTICO: modal no bloquea, solo dialog
                        modal.style.pointerEvents = 'none'; // El modal no bloquea clicks
                        modal.style.zIndex = '9999';
                        modal.style.display = 'block';
                        
                        var dialog = modal.querySelector('.modal-dialog');
                        if (dialog) {
                            dialog.style.pointerEvents = 'auto'; // El dialog SÍ recibe clicks
                            dialog.style.zIndex = '10000';
                            dialog.style.position = 'relative';
                        }
                        
                        var content = modal.querySelector('.modal-content');
                        if (content) {
                            content.style.pointerEvents = 'auto';
                            content.style.zIndex = '10001';
                            content.style.position = 'relative';
                        }
                        
                        // Asegurar que todos los inputs sean interactivos y habilitados
                        var inputs = modal.querySelectorAll('input, select, textarea, button');
                        inputs.forEach(function(input) {
                            input.style.pointerEvents = 'auto';
                            input.style.zIndex = '10002';
                            input.style.position = 'relative';
                            input.removeAttribute('disabled');
                            input.removeAttribute('readonly');
                            input.style.opacity = '1';
                            input.style.cursor = 'pointer';
                            if (input.tagName === 'INPUT' || input.tagName === 'TEXTAREA') {
                                input.style.cursor = 'text';
                            }
                        });
                        
                        // Asegurar que el formulario sea funcional
                        var form = modal.querySelector('form');
                        if (form) {
                            form.style.pointerEvents = 'auto';
                            form.style.zIndex = '10003';
                            // Remover cualquier atributo que bloquee
                            form.removeAttribute('disabled');
                            form.removeAttribute('readonly');
                        }
                        
                        // Asegurar que el backdrop NO bloquee - SOLUCIÓN DEFINITIVA
                        setTimeout(function() {
                            var backdrops = document.querySelectorAll('.modal-backdrop');
                            backdrops.forEach(function(backdrop) {
                                backdrop.style.pointerEvents = 'none'; // NO BLOQUEA NADA
                                backdrop.style.zIndex = '9998';
                            });
                            limpiarBackdrops();
                        }, 50);
                        
                        @php
                            $tieneItems = $r->items && $r->items->count() > 0;
                        @endphp
                        @if($tieneItems)
                        initEditarUI{{ $r->id }}();
                        @endif
                    });
                    
                    modalEditar.addEventListener('hide.bs.modal', function() {
                        // Limpiar al cerrar
                        limpiarBackdrops();
                    });
                }
                
                // Inicializar funcionalidad de edición de productos si tiene items
                @php
                    $tieneItems = $r->items && $r->items->count() > 0;
                @endphp
                @if($tieneItems)
                function initEditarUI{{ $r->id }}() {
                    var selectProducto = document.getElementById('select_producto_editar{{ $r->id }}');
                    var contSeleccionados = document.getElementById('seleccionados_contenedor_editar{{ $r->id }}');
                    var badgeCount = document.getElementById('count_sel_editar{{ $r->id }}');
                    var seleccionadosMap = new Map();
                    
                    // Cargar productos existentes en el mapa
                    contSeleccionados.querySelectorAll('.item-row').forEach(function(row) {
                        var invId = parseInt(row.getAttribute('data-inv-id'));
                    if (invId) {
                        seleccionadosMap.set(invId, true);
                    }
                    var qtyExisting = row.querySelector('input[type="number"]');
                    if (qtyExisting) {
                        bindNumericSanitizer(qtyExisting);
                    }
                    });
                    
                    function actualizarBadgeEditar{{ $r->id }}() {
                        var total = contSeleccionados.querySelectorAll('.item-row').length;
                        if (badgeCount) badgeCount.textContent = String(total);
                        
                        // Recalcular índices
                        contSeleccionados.querySelectorAll('.item-row').forEach(function(row, index) {
                            var cantidadInput = row.querySelector('input[type="number"]');
                            var inventarioInput = row.querySelector('input[type="hidden"]');
                            if (cantidadInput) cantidadInput.name = `items[${index}][cantidad]`;
                            if (inventarioInput) inventarioInput.name = `items[${index}][inventario_id]`;
                        });
                    }
                    
                    // Manejar selección de producto
                    if (selectProducto) {
                        selectProducto.onchange = function() {
                            var selectedOption = this.options[this.selectedIndex];
                            
                            if (!selectedOption || !selectedOption.value || selectedOption.value === '') {
                                return;
                            }
                            
                            var inventarioId = parseInt(selectedOption.value);
                            var stock = parseInt(selectedOption.getAttribute('data-stock') || '0');
                            var descripcion = selectedOption.getAttribute('data-descripcion') || '';
                            
                            if (seleccionadosMap.has(inventarioId)) {
                                showToast('Este producto ya está en la lista', 'warning');
                                this.selectedIndex = 0;
                                return;
                            }
                            
                            seleccionadosMap.set(inventarioId, true);
                            
                            var itemIndex = contSeleccionados.querySelectorAll('.item-row').length;
                            var row = document.createElement('div');
                            row.className = 'item-row';
                            row.setAttribute('data-inv-id', String(inventarioId));
                            row.style.display = 'flex';
                            row.style.flexDirection = 'column';
                            row.style.gap = '10px';
                            row.style.marginBottom = '8px';
                            row.style.padding = '12px';
                            row.style.border = '1px solid rgba(255,255,255,.15)';
                            row.style.borderRadius = '8px';
                            row.style.background = 'rgba(0,0,0,.4)';
                            row.style.minWidth = '0';
                            
                            row.innerHTML = `
                                <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;width:100%;">
                                    <div style="flex:1;min-width:0;">
                                        <span class="item-titulo" style="display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;font-weight:600;">${descripcion}</span>
                                        <small class="disponible-actual" style="opacity:.8;font-size:.85em;">Disponible: ${stock}</small>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-secondary btn-remove-item" data-inv-id="${inventarioId}" style="flex-shrink:0;"><i class="fas fa-times"></i></button>
                                </div>
                                <div style="display:flex;flex-direction:column;gap:4px;width:100%;">
                                    <label style="font-size:.85em;opacity:.9;margin-bottom:4px;" for="cantidad_item_crear_${itemIndex}">Cantidad:</label>
                                    <input type="number" id="cantidad_item_crear_${itemIndex}" min="1" inputmode="numeric" pattern="[0-9]*" data-only-numeric="true" class="form-control form-control-sm" data-stock="${stock}" name="items[${itemIndex}][cantidad]" placeholder="Cantidad (máx ${stock})" required style="width:100%;">
                                    <small class="field-error" style="display:none;"></small>
                                </div>
                                <input type="hidden" name="items[${itemIndex}][inventario_id]" value="${inventarioId}">
                            `;
                            
                            contSeleccionados.appendChild(row);
                            var qtyInput = row.querySelector('input[type="number"]');
                            if (qtyInput) {
                                bindNumericSanitizer(qtyInput);
                            }
                            
                            // Agregar listener al botón de eliminar
                            var removeBtn = row.querySelector('.btn-remove-item');
                            if (removeBtn) {
                                removeBtn.addEventListener('click', function() {
                                    var invId = parseInt(this.getAttribute('data-inv-id'));
                                    seleccionadosMap.delete(invId);
                                    this.closest('.item-row').remove();
                                    actualizarBadgeEditar{{ $r->id }}();
                                });
                            }
                            
                            actualizarBadgeEditar{{ $r->id }}();
                            this.selectedIndex = 0;
                        };
                    }
                    
                    // Agregar listeners a botones de eliminar existentes
                    contSeleccionados.querySelectorAll('.btn-remove-item').forEach(function(btn) {
                        btn.addEventListener('click', function() {
                            var invId = parseInt(this.getAttribute('data-inv-id'));
                            seleccionadosMap.delete(invId);
                            this.closest('.item-row').remove();
                            actualizarBadgeEditar{{ $r->id }}();
                        });
                    });
                    
                    // Inicializar badge
                    actualizarBadgeEditar{{ $r->id }}();
                }
                @endif
            })();
            @endforeach
            @endif

            // Confirmación estilizada para eliminar con AJAX
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
                    confirmBtn.addEventListener('click', async function() {
                        if (formToDelete) {
                            const form = formToDelete;
                            const url = form.getAttribute('action');
                            const token = form.querySelector('input[name="_token"]').value;
                            const method = form.querySelector('input[name="_method"]')?.value || 'DELETE';
                            
                            // Cerrar el modal de confirmación
                            if (bsModal) { bsModal.hide(); }
                            
                            // Deshabilitar el botón mientras se procesa
                            confirmBtn.disabled = true;
                            const originalText = confirmBtn.innerHTML;
                            confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Eliminando...';
                            
                            try {
                                const formData = new FormData();
                                formData.append('_token', token);
                                formData.append('_method', method);
                                
                                const response = await fetch(url, {
                                    method: 'POST',
                                    headers: {
                                        'X-Requested-With': 'XMLHttpRequest',
                                        'Accept': 'application/json',
                                        'X-CSRF-TOKEN': token
                                    },
                                    body: formData
                                });
                                
                                if (response.ok || response.status === 200 || response.redirected) {
                                    showToast('Evento eliminado', 'success');
                                    // Recargar el calendario y el sidebar sin refrescar la página
                                    if (typeof window.reloadCalendar === 'function') {
                                        setTimeout(() => {
                                            window.reloadCalendar();
                                            // También recargar el sidebar
                                            if (typeof window.reloadSidebar === 'function') {
                                                window.reloadSidebar();
                                            }
                                            if (typeof window.refreshInventarioData === 'function') {
                                                window.refreshInventarioData();
                                            }
                                        }, 500);
                                    } else {
                                        // Fallback: recargar página si la función no está disponible
                                        setTimeout(() => {
                                            window.location.reload();
                                        }, 1000);
                                    }
                                } else {
                                    const data = await response.json().catch(() => ({}));
                                    const errorMsg = data?.message || data?.error || 'Error al eliminar el evento';
                                    showToast(errorMsg, 'error');
                                }
                            } catch (error) {
                                console.error('Error:', error);
                                showToast('Error al conectar con el servidor', 'error');
                            } finally {
                                confirmBtn.disabled = false;
                                confirmBtn.innerHTML = originalText;
                                formToDelete = null;
                            }
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
            @php
                $reservasPendientesJs = ($reservasPendientes ?? collect())->map(function ($reserva) {
                    return [
                        'id' => $reserva->id,
                        'fecha_inicio' => optional($reserva->fecha_inicio)->format('Y-m-d H:i:s'),
                        'fecha_fin' => optional($reserva->fecha_fin)->format('Y-m-d H:i:s'),
                        'items' => $reserva->items->map(function ($item) {
                            return [
                                'inventario_id' => $item->inventario_id,
                                'cantidad' => (int) ($item->cantidad ?? 0),
                            ];
                        })->values()->all(),
                    ];
                })->values()->all();
            @endphp
            let reservasPendientesData = @json($reservasPendientesJs);
            
            // Variable global para prevenir múltiples envíos
            let isSubmittingCrear = false;

            function initCrearUI() {
                function inferirServicio(descripcion) {
                    if (!descripcion) return 'Alquiler';
                    const lower = descripcion.toLowerCase();
                    if (lower.includes('publi')) return 'Publicidad';
                    if (lower.includes('anim')) return 'Animación';
                    return 'Alquiler';
                }

                let inventarioItems = [
                    @foreach($inventarios ?? [] as $inv)
                        @php
                            $descI = $inv->descripcion ?? '';
                            $servI = (stripos($descI,'publi')!==false) ? 'Publicidad' : ((stripos($descI,'anim')!==false) ? 'Animación' : 'Alquiler');
                        @endphp
                        { inventario_id: {{ $inv->id }}, descripcion: @json($descI ?: 'Sin descripción'), stock: {{ (int)($inv->stock ?? 0) }}, servicio: @json($servI) },
                    @endforeach
                ];

                // Items detallados de eventos para calcular disponibilidad correcta
                const eventosItems = @json($eventosItems ?? []);
                const movimientosPorInventario = {
                    @foreach($movimientos ?? [] as $mov)
                        {{ $mov->inventario_id }}: {{ $mov->id }},
                    @endforeach
                };

                const servicioCrear = document.getElementById('servicio_crear');
                const selectProducto = document.getElementById('select_producto');
                const contSeleccionados = document.getElementById('seleccionados_contenedor');
                const badgeCount = document.getElementById('count_sel');
                const saveBtn = document.getElementById('btn_guardar_crear');
                // Usaremos el DOM para contar seleccionados y una Map local para validación rápida
                const seleccionadosMap = new Map();

                function renderProductoOptions() {
                    if (!selectProducto) return;
                    const previousValue = selectProducto.value;
                    selectProducto.innerHTML = '<option value=\"\">-- Seleccione un producto del inventario --</option>';
                    const rango = rangoActual();
                    inventarioItems.forEach(it => {
                        const reservado = reservadoInventarioEnRango(it.inventario_id, rango.s, rango.f);
                        const disponible = Math.max(0, (it.stock || 0) - reservado);
                        const option = document.createElement('option');
                        option.value = String(it.inventario_id);
                        option.setAttribute('data-stock', String(disponible));
                        option.setAttribute('data-descripcion', it.descripcion);
                        option.textContent = `${it.descripcion} - Stock: ${disponible}`;
                        if (seleccionadosMap.has(it.inventario_id)) {
                            option.disabled = true;
                            option.textContent += ' (seleccionado)';
                        }
                        selectProducto.appendChild(option);
                    });
                    if (previousValue && selectProducto.querySelector(`option[value=\"${previousValue}\"]`)) {
                        selectProducto.value = previousValue;
                    } else {
                        selectProducto.selectedIndex = 0;
                    }
                }

                function updateSelectedRowsStock() {
                    if (!contSeleccionados) return;
                    const rango = rangoActual();
                    contSeleccionados.querySelectorAll('.item-row').forEach(function(row){
                        const invId = parseInt(row.getAttribute('data-inv-id') || '0');
                        const item = inventarioItems.find(x => x.inventario_id === invId);
                        if (!item) return;
                        const yaInv = reservadoInventarioEnRango(invId, rango.s, rango.f);
                        const disponible = Math.max(0, (item.stock || 0) - yaInv);
                        const label = row.querySelector('.disponible-actual');
                        if (label) {
                            label.textContent = 'Disponible: ' + disponible;
                        }
                        const qty = row.querySelector('input[type=number]');
                        if (qty) {
                            bindNumericSanitizer(qty);
                            qty.setAttribute('data-stock', String(disponible));
                            qty.setAttribute('data-stock-original', String(item.stock));
                            qty.setAttribute('placeholder', 'Cantidad (máx ' + disponible + ')');
                            const currentVal = parseInt(qty.value || '0');
                            if (currentVal > disponible) {
                                qty.value = disponible > 0 ? disponible : '';
                            }
                        }
                    });
                    validarTodo();
                }

                async function refreshInventarioData() {
                    try {
                        const response = await fetch('{{ route('inventario.index') }}', {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });
                        if (!response.ok) {
                            throw new Error('No se pudo actualizar el inventario');
                        }
                        const data = await response.json();
                        inventarioItems = Array.isArray(data) ? data.map(item => ({
                            inventario_id: item.id,
                            descripcion: item.descripcion || 'Sin descripción',
                            stock: parseInt(item.stock ?? 0),
                            servicio: inferirServicio(item.descripcion || '')
                        })) : inventarioItems;
                        renderProductoOptions();
                        updateSelectedRowsStock();
                        recomputeAvailability();
                    } catch (error) {
                        console.error('Error al refrescar inventario:', error);
                    }
                }

                window.refreshInventarioData = refreshInventarioData;
                renderProductoOptions();
                updateSelectedRowsStock();

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
                    return {s: s || '', f: f || ''};
                }

                // Parsear cantidad desde descripción de eventos o desde items
                function parseCant(desc, inventarioId) {
                    if (!desc) return 0;
                    // Buscar patrones como "Producto (x2)" o "Cantidad solicitada: 3"
                    const mCantidad = desc.match(/Cantidad solicitada:\s*(\d+)/i);
                    if (mCantidad) return parseInt(mCantidad[1] || '0');
                    
                    // Buscar patrones como "Producto (x2)" en la descripción que coincida con el inventario
                    // Si hay múltiples productos, buscar el que corresponde al inventarioId
                    const regex = new RegExp(`([^(]*)\\(x(\\d+)\\)`, 'g');
                    let match;
                    let total = 0;
                    while ((match = regex.exec(desc)) !== null) {
                        total += parseInt(match[2] || '0');
                    }
                    // Si no se encuentra patrón específico, usar 1 por defecto
                    return total > 0 ? total : 1;
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
                    let total = (eventos || []).reduce((acc, ev) => {
                        try {
                            // Verificar si el inventario está en el array de inventarioIds o en el campo único (formato antiguo)
                            const inventarioIds = ev.inventarioIds || [];
                            const inventarioIdUnico = ev.inventarioId || ev.inventarioid;
                            const tieneInventario = Array.isArray(inventarioIds)
                                ? inventarioIds.includes(inventarioId)
                                : (inventarioIdUnico == inventarioId);

                            if (tieneInventario) {
                                const es = new Date(ev.start); const ef = ev.end ? new Date(ev.end) : new Date(ev.start);
                                // Lógica de solapamiento coherente con backend:
                                // La reserva existente empieza ANTES de que termine la nueva (es < fi)
                                // Y la reserva existente termina DESPUÉS de que empiece la nueva (ef > si)
                                // Esto significa que si una reserva termina exactamente cuando otra empieza, NO se solapan
                                const solapa = es < fi && ef > si;
                                if (solapa) {
                                    // Buscar la cantidad exacta desde eventosItems
                                    const calId = ev.calendarioId || ev.id;
                                    if (eventosItems && eventosItems[calId]) {
                                        const item = eventosItems[calId].find(it => it.inventario_id == inventarioId);
                                        if (item) {
                                            acc += parseInt(item.cantidad || 1);
                        } else {
                                            // Fallback: parsear desde descripción
                                            acc += parseCant(ev.description, inventarioId) || 1;
                                        }
                            } else {
                                        // Formato antiguo: parsear desde descripción
                                        acc += parseCant(ev.description, inventarioId) || 1;
                                    }
                                }
                            }
                        } catch {}
                        return acc;
                    }, 0);
                    if (Array.isArray(reservasPendientesData)) {
                        reservasPendientesData.forEach(function(reserva) {
                            try {
                                if (!reserva) return;
                                const resInicio = reserva.fecha_inicio ? new Date(reserva.fecha_inicio) : null;
                                const resFin = reserva.fecha_fin ? new Date(reserva.fecha_fin) : resInicio;
                                if (!resInicio || !resFin || isNaN(resInicio.getTime()) || isNaN(resFin.getTime())) return;
                                const solapa = resInicio < fi && resFin > si;
                                if (!solapa) return;
                                const items = Array.isArray(reserva.items) ? reserva.items : [];
                                items.forEach(function(item) {
                                    const invId = item && item.inventario_id !== undefined ? parseInt(item.inventario_id) : NaN;
                                    if (!Number.isNaN(invId) && invId === inventarioId) {
                                        total += parseInt(item.cantidad || 0);
                                    }
                                });
                            } catch {}
                        });
                    }
                    return total;
                }


                function agregarSeleccionado(it) {
                    const row = document.createElement('div');
                    row.className = 'item-row';
                    row.style.display = 'flex';
                    row.style.flexDirection = 'column';
                    row.style.gap = '10px';
                    row.style.border = '1px solid rgba(255,255,255,.15)';
                    row.style.borderRadius = '8px';
                    row.style.padding = '12px';
                    row.style.background = 'rgba(0,0,0,.4)';
                    row.style.minWidth = '0';
                    row.setAttribute('data-inv-id', String(it.inventario_id));

                    const {s, f} = rangoActual();
                    const yaInv = reservadoInventarioEnRango(it.inventario_id, s, f);
                    const disp = Math.max(0, (it.stock || 0) - yaInv);
                    // Generar un índice único para este item
                    const itemIndex = contSeleccionados ? contSeleccionados.querySelectorAll('.item-row').length : 0;
                    row.innerHTML = `
                        <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;width:100%;">
                            <div style="flex:1;min-width:0;">
                                <span class="item-titulo" style="display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;font-weight:600;">${it.descripcion}</span>
                                <small class="disponible-actual" style="opacity:.8;font-size:.85em;">Disponible: ${disp}</small>
                            </div>
                            <button type="button" class="btn btn-sm btn-secondary" data-remove="${it.inventario_id}" style="flex-shrink:0;"><i class="fas fa-times"></i></button>
                        </div>
                        <div style="display:flex;flex-direction:column;gap:4px;width:100%;">
                            <label style="font-size:.85em;opacity:.9;margin-bottom:4px;" for="cantidad_item_editar_${itemIndex}">Cantidad:</label>
                            <input type="number" id="cantidad_item_editar_${itemIndex}" min="1" inputmode="numeric" pattern="[0-9]*" data-only-numeric="true" class="form-control form-control-sm" data-stock="${disp}" name="items[${itemIndex}][cantidad]" placeholder="Cantidad (máx ${disp})" required style="width:100%;">
                            <small class="field-error" style="display:none;"></small>
                        </div>
                        <input type="hidden" name="items[${itemIndex}][inventario_id]" value="${it.inventario_id}">
                    `;

                    row.querySelector('button[data-remove]')?.addEventListener('click', function(){
                        const rid = parseInt(this.getAttribute('data-remove'));
                        seleccionadosMap.delete(rid);
                        row.remove();
                        // Recalcular índices después de eliminar
                        recalcularIndicesItems();
                        renderProductoOptions();
                        updateSelectedRowsStock();
                        validarTodo();
                    });

                    contSeleccionados.appendChild(row);
                    actualizarBadge();
                    showToast('Producto agregado a la reserva', 'success');
                    const qty = row.querySelector('input[type=number]');
                    bindNumericSanitizer(qty);
                    qty.addEventListener('input', validarTodo);
                }

                // Manejar selección de producto desde el select - USAR INVENTARIO DIRECTAMENTE
                if (selectProducto) {
                    selectProducto.onchange = function(){
                        const selectedOption = this.options[this.selectedIndex];
                        
                        // Si es la opción por defecto o no hay valor, no hacer nada
                        if (!selectedOption || !selectedOption.value || selectedOption.value === '') {
                            return;
                        }
                        
                        // El value ahora es directamente el inventario_id
                        const inventarioId = parseInt(selectedOption.value);
                        const stock = parseInt(selectedOption.getAttribute('data-stock') || '0');
                        const descripcion = selectedOption.getAttribute('data-descripcion') || '';
                        
                        // Verificar si ya está seleccionado
                        if (seleccionadosMap.has(inventarioId)) {
                            showToast('Este producto ya está en la lista', 'warning');
                            this.selectedIndex = 0;
                            return;
                        }
                        
                        // Buscar o crear el item
                        let it = inventarioItems.find(x => x.inventario_id === inventarioId);
                        if (!it) {
                            it = {
                                inventario_id: inventarioId,
                                descripcion: descripcion,
                                stock: stock,
                                servicio: 'Alquiler'
                            };
                            inventarioItems.push(it);
                        }
                        
                        // NO necesitamos movId aquí - se creará en el backend
                        
                        // AGREGAR A LA LISTA INMEDIATAMENTE
                        seleccionadosMap.set(inventarioId, it);
                        agregarSeleccionado(it);
                    renderProductoOptions();
                    updateSelectedRowsStock();
                        
                        // Resetear select
                        this.selectedIndex = 0;
                    };
                }
                
                // Función para recalcular los índices de los items después de eliminar uno
                function recalcularIndicesItems() {
                    if (!contSeleccionados) return;
                    const rows = contSeleccionados.querySelectorAll('.item-row');
                    rows.forEach((row, index) => {
                        const cantidadInput = row.querySelector('input[type="number"][name^="items"]');
                        const inventarioInput = row.querySelector('input[type="hidden"][name^="items"]');
                        if (cantidadInput) cantidadInput.name = `items[${index}][cantidad]`;
                        if (inventarioInput) inventarioInput.name = `items[${index}][inventario_id]`;
                    });
                }

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
                    bindNumericSanitizer(qty);
                    bindNumericSanitizer(qty);
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
                        qty.setAttribute('placeholder', 'Cantidad (máx ' + disp + ')');
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

                // Validar antes de enviar - PREVENIR DOBLE ENVÍO
                // Remover listener anterior si existe para evitar múltiples listeners
                const formCrearNew = document.getElementById('formCrear');
                if (formCrearNew) {
                    // Remover listener anterior si existe
                    if (formCrearNew._submitHandler) {
                        formCrearNew.removeEventListener('submit', formCrearNew._submitHandler);
                    }
                    
                    // Crear handler nombrado
                    formCrearNew._submitHandler = async function(e){
                        e.preventDefault();
                        e.stopImmediatePropagation(); // Prevenir que otros listeners se ejecuten
                        e.stopPropagation();
                        
                        // Prevenir doble envío
                        if (isSubmittingCrear) {
                            showToast('Ya se está procesando la solicitud, por favor espere...', 'warning');
                            return false;
                        }
                    
                    const alertBox = document.getElementById('alertCrear');
                    if (alertBox) { alertBox.style.display = 'none'; alertBox.textContent = ''; }
                    
                    // Validar fechas
                    if (!validarFechas('fecha_inicio_crear', 'fecha_fin_crear', 'alertCrear', 'formCrear')) {
                        return false;
                    }
                    
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
                    
                    // Marcar como enviando y deshabilitar botón
                    isSubmittingCrear = true;
                    let originalText = '';
                    const saveBtnNew = document.getElementById('btn_guardar_crear');
                    if (saveBtnNew) {
                        originalText = saveBtnNew.innerHTML;
                        saveBtnNew.disabled = true;
                        saveBtnNew.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
                        
                        // Rehabilitar después de un tiempo por si acaso
                        setTimeout(() => {
                            if (isSubmittingCrear) {
                                isSubmittingCrear = false;
                                saveBtnNew.disabled = false;
                                saveBtnNew.innerHTML = originalText;
                            }
                        }, 10000);
                    }
                    
                    const fd = new FormData(formCrearNew);
                    // Señalamos ajax/json
                    const url = formCrearNew.getAttribute('action');
                    const token = formCrearNew.querySelector('input[name="_token"]').value;
                    try {
                        const resp = await fetch(url, {
                            method: 'POST',
                            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json', 'X-CSRF-TOKEN': token },
                            body: fd
                        });
                        
                        // Intentar parsear la respuesta como JSON
                        let data;
                        const contentType = resp.headers.get('content-type');
                        if (contentType && contentType.includes('application/json')) {
                            data = await resp.json();
                        } else {
                            const text = await resp.text();
                            try {
                                data = JSON.parse(text);
                            } catch {
                                data = { message: text || 'Error desconocido al procesar la solicitud' };
                            }
                        }
                        
                        if (resp.ok) {
                            const duplicated = data && data.duplicated;
                            const successMessage = duplicated
                                ? (data?.message || 'La reserva ya estaba registrada y se reutilizó.')
                                : 'Reserva registrada correctamente';
                            const toastType = duplicated ? 'warning' : 'success';
                            showToast(successMessage, toastType);
                            // Cerrar modal y limpiar
                            const modalEl = document.getElementById('modalCrear');
                            if (modalEl) {
                                const inst = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                                inst.hide();
                            }
                            const formCrearReset = document.getElementById('formCrear');
                            if (formCrearReset) formCrearReset.reset();
                            contSeleccionados.innerHTML = '';
                            seleccionadosMap.clear();
                            if (badgeCount) badgeCount.textContent = '0';
                            await refreshInventarioData();
                            // Recargar el calendario y el sidebar inmediatamente sin refrescar la página
                            // Esperar un momento para asegurar que el servidor haya guardado el registro
                            setTimeout(() => {
                                if (typeof window.reloadReservas === 'function') {
                                    window.reloadReservas();
                                }
                                // Primero recargar el sidebar para que aparezca el nuevo registro inmediatamente
                                if (typeof window.reloadSidebar === 'function') {
                                    // Intentar recargar el sidebar con un pequeño retry si falla la primera vez
                                    let retryCount = 0;
                                    const maxRetries = 2;
                                    
                                    const tryReloadSidebar = () => {
                                        window.reloadSidebar().then(() => {
                                            console.log('Sidebar recargado exitosamente');
                                            // Después recargar el calendario
                                            if (typeof window.reloadCalendar === 'function') {
                                                window.reloadCalendar();
                                            }
                                        }).catch(error => {
                                            console.error('Error al recargar sidebar (intento ' + (retryCount + 1) + '):', error);
                                            retryCount++;
                                            if (retryCount < maxRetries) {
                                                // Reintentar después de un pequeño delay
                                                setTimeout(() => {
                                                    tryReloadSidebar();
                                                }, 500);
                                            } else {
                                                // Si falla después de varios intentos, recargar página
                                                console.warn('Falló al recargar sidebar después de varios intentos, recargando página');
                                                window.location.reload();
                                            }
                                        });
                                    };
                                    
                                    tryReloadSidebar();
                                } else {
                                    console.warn('reloadSidebar no está disponible, recargando página');
                                    // Si no está disponible, recargar página
                                    setTimeout(() => {
                                        window.location.reload();
                                    }, 500);
                                }
                            }, 800);
                        } else if (resp.status === 422) {
                            // Error de validación - mostrar todos los mensajes específicos
                            let errorMessages = [];
                            
                            if (data?.errors) {
                                // Si hay errores por campo, mostrar cada uno
                                Object.keys(data.errors).forEach(campo => {
                                    const erroresCampo = data.errors[campo];
                                    if (Array.isArray(erroresCampo)) {
                                        erroresCampo.forEach(error => {
                                            // Traducir nombres de campos a español
                                            let campoNombre = campo;
                                            if (campo.includes('fecha_inicio')) campoNombre = 'Fecha de inicio';
                                            else if (campo.includes('fecha_fin')) campoNombre = 'Fecha de fin';
                                            else if (campo.includes('descripcion_evento')) campoNombre = 'Descripción';
                                            else if (campo.includes('items')) campoNombre = 'Productos';
                                            else if (campo.includes('cantidad')) campoNombre = 'Cantidad';
                                            else if (campo.includes('movimientos_inventario_id')) campoNombre = 'Producto';
                                            
                                            errorMessages.push(`${campoNombre}: ${error}`);
                                        });
                        } else {
                                        errorMessages.push(erroresCampo);
                                    }
                                });
                            }
                            
                            // Si no hay errores en el formato errors, buscar en message o error
                            if (errorMessages.length === 0) {
                                if (data?.message) {
                                    errorMessages.push(data.message);
                                } else if (data?.error) {
                                    errorMessages.push(data.error);
                                } else {
                                    errorMessages.push('Error de validación. Por favor, revise los datos ingresados.');
                                }
                            }

                            const mensajeCompleto = errorMessages.length > 0
                                ? errorMessages.join('\n')
                                : 'Error de validación. Por favor, revise los datos ingresados.';

                            // Mostrar el primer error en el toast y todos en el alert
                            showToast(errorMessages[0] || 'Error de validación', 'error');
                            if (alertBox) {
                                alertBox.innerHTML = mensajeCompleto.replace(/\n/g, '<br>');
                                alertBox.style.display = 'block';
                            }
                        } else if (resp.status === 400 || resp.status === 500) {
                            // Error del servidor
                            const errorMsg = data?.message || data?.error || 'Error del servidor al procesar la solicitud';
                            showToast(errorMsg, 'error');
                            if (alertBox) {
                                alertBox.textContent = errorMsg;
                                alertBox.style.display = 'block';
                            }
                        } else {
                            // Otros errores
                            const errorMsg = data?.message || data?.error || `Error al guardar (código: ${resp.status})`;
                            showToast(errorMsg, 'error');
                            if (alertBox) {
                                alertBox.textContent = errorMsg;
                                alertBox.style.display = 'block';
                            }
                        }
                    } catch (err) {
                        console.error('Error completo:', err);
                        const errorMsg = err.message || 'Ocurrió un error de conexión. Por favor, intente nuevamente';
                        showToast(errorMsg, 'error');
                        if (alertBox) {
                            alertBox.textContent = errorMsg;
                            alertBox.style.display = 'block';
                        }
                    } finally {
                        isSubmittingCrear = false;
                        const saveBtnFinal = document.getElementById('btn_guardar_crear');
                        if (saveBtnFinal) {
                            saveBtnFinal.disabled = false;
                            saveBtnFinal.innerHTML = originalText || 'Guardar';
                        }
                    }
                    return false;
                    };
                    
                    // Agregar el listener
                    formCrearNew.addEventListener('submit', formCrearNew._submitHandler);
                }

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
                    // Limpiar backdrops duplicados
                    limpiarBackdrops();
                });
                modalCrearEl.addEventListener('hidden.bs.modal', function(){
                    limpiarBackdrops();
                    // Resetear flag de envío al cerrar el modal
                    isSubmittingCrear = false;
                    const saveBtnReset = document.getElementById('btn_guardar_crear');
                    if (saveBtnReset) {
                        saveBtnReset.disabled = false;
                    }
                });
            }
            
            // Función para limpiar backdrops duplicados y asegurar interactividad
            function limpiarBackdrops() {
                var backdrops = document.querySelectorAll('.modal-backdrop');
                if (backdrops.length > 1) {
                    // Mantener solo el último
                    for (var i = 0; i < backdrops.length - 1; i++) {
                        backdrops[i].remove();
                    }
                }
                
                // Si hay modales abiertos, asegurar que el backdrop NO bloquee
                var modalesAbiertos = document.querySelectorAll('.modal.show');
                if (modalesAbiertos.length > 0) {
                    // El backdrop NO debe bloquear NADA
                    backdrops.forEach(function(backdrop) {
                        backdrop.style.pointerEvents = 'none'; // NO BLOQUEA
                        backdrop.style.zIndex = '9998';
                    });
                    // Asegurar que el modal sea interactivo - CRÍTICO
                    modalesAbiertos.forEach(function(modal) {
                        modal.style.pointerEvents = 'none'; // El modal no bloquea
                        modal.style.zIndex = '9999';
                        
                        var dialog = modal.querySelector('.modal-dialog');
                        if (dialog) {
                            dialog.style.pointerEvents = 'auto'; // El dialog SÍ recibe clicks
                            dialog.style.zIndex = '10000';
                            dialog.style.position = 'relative';
                        }
                        
                        var content = modal.querySelector('.modal-content');
                        if (content) {
                            content.style.pointerEvents = 'auto';
                            content.style.zIndex = '10001';
                        }
                        
                        // Todos los inputs deben ser interactivos
                        var inputs = modal.querySelectorAll('input, select, textarea, button');
                        inputs.forEach(function(input) {
                            input.style.pointerEvents = 'auto';
                            input.style.zIndex = '10002';
                            input.removeAttribute('disabled');
                            input.removeAttribute('readonly');
                        });
                    });
                } else {
                    // Si no hay modales abiertos, remover todos los backdrops
                    backdrops.forEach(function(backdrop) {
                        backdrop.remove();
                    });
                    document.body.classList.remove('modal-open');
                    document.body.style.overflow = '';
                    document.body.style.paddingRight = '';
                }
            }
            
            // Limpiar backdrops cuando se cierra cualquier modal
            document.addEventListener('hidden.bs.modal', function() {
                setTimeout(limpiarBackdrops, 100);
            });
            
            // SOLUCIÓN AGRESIVA: Forzar que el backdrop no bloquee al abrir cualquier modal
            document.addEventListener('shown.bs.modal', function(e) {
                var modal = e.target;
                if (modal) {
                    // Forzar que el modal-dialog sea interactivo
                    var dialog = modal.querySelector('.modal-dialog');
                    if (dialog) {
                        dialog.style.pointerEvents = 'auto';
                        dialog.style.zIndex = '10000';
                    }
                    
                    // Hacer que todos los inputs sean interactivos
                    var inputs = modal.querySelectorAll('input, select, textarea, button');
                    inputs.forEach(function(input) {
                        input.style.pointerEvents = 'auto';
                        input.style.zIndex = '10002';
                        input.removeAttribute('disabled');
                        input.removeAttribute('readonly');
                    });
                    
                    // Asegurar que el backdrop no bloquee - moverlo si es necesario
                    setTimeout(function() {
                            var backdrops = document.querySelectorAll('.modal-backdrop');
                            backdrops.forEach(function(backdrop) {
                                // El backdrop NO debe bloquear NADA
                                backdrop.style.pointerEvents = 'none'; // NO BLOQUEA
                                backdrop.style.zIndex = '9998';
                                // Asegurar que el modal-dialog esté por encima
                                if (dialog) {
                                    dialog.style.zIndex = '10000';
                                    dialog.style.pointerEvents = 'auto';
                                }
                            });
                    }, 50);
                }
            });
            
            // Limpiar backdrops al cargar la página
            window.addEventListener('load', function() {
                limpiarBackdrops();
            });
            
            // SOLUCIÓN AGRESIVA: Forzar que el backdrop NO bloquee cada 100ms
            setInterval(function() {
                // EL BACKDROP NO DEBE BLOQUEAR NADA
                var backdrops = document.querySelectorAll('.modal-backdrop');
                backdrops.forEach(function(backdrop) {
                    backdrop.style.pointerEvents = 'none'; // NO BLOQUEA
                    backdrop.style.zIndex = '9998';
                });
                
                var modalesAbiertos = document.querySelectorAll('.modal.show');
                if (modalesAbiertos.length > 0) {
                    modalesAbiertos.forEach(function(modal) {
                        modal.style.pointerEvents = 'none';
                        modal.style.zIndex = '9999';
                        modal.style.display = 'block';
                        
                        var dialog = modal.querySelector('.modal-dialog');
                        if (dialog) {
                            dialog.style.pointerEvents = 'auto';
                            dialog.style.zIndex = '10000';
                            dialog.style.position = 'relative';
                        }
                        
                        var content = modal.querySelector('.modal-content');
                        if (content) {
                            content.style.pointerEvents = 'auto';
                            content.style.zIndex = '10001';
                        }
                        
                        var inputs = modal.querySelectorAll('input, select, textarea, button, form');
                        inputs.forEach(function(input) {
                            input.style.pointerEvents = 'auto';
                            input.style.zIndex = '10002';
                            input.removeAttribute('disabled');
                            input.removeAttribute('readonly');
                            input.style.opacity = '1';
                        });
                    });
                }
            }, 100);

            // El filtrado por servicio ya se maneja en initCrearUI
        })();
        </script>
    </div>
</body>
</html>
