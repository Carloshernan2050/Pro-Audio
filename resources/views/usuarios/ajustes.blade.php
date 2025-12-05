@extends('layouts.app')

@section('title', 'Ajustes de Servicios')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/ajustes.css') }}">
@endpush

@section('content')
        {{-- Contenido principal --}}
        <main class="main-content">
            <h2 class="page-title">Ajustes de Servicios</h2>
            <p class="page-subtitle">Administra los servicios de la plataforma. Puedes crear, editar y eliminar.</p>

            {{-- Mensajes de estado (éxito o error) --}}
            @if(session('success'))
                <div class="alert success" data-alert>
                    <div class="alert-message">{{ session('success') }}</div>
                    <button type="button" class="alert-hide" aria-label="Ocultar mensaje">Ocultar</button>
                </div>
            @endif
            @if(session('warning'))
                <div class="alert warning" data-alert>
                    <div class="alert-message">{{ session('warning') }}</div>
                    <button type="button" class="alert-hide" aria-label="Ocultar mensaje">Ocultar</button>
                </div>
            @endif
            @if(session('error'))
                <div class="alert error" data-alert>
                    <div class="alert-message">{{ session('error') }}</div>
                    <button type="button" class="alert-hide" aria-label="Ocultar mensaje">Ocultar</button>
                </div>
            @endif
            @if($errors->any())
                <div class="alert error" data-alert>
                    <div class="alert-message">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                    <button type="button" class="alert-hide" aria-label="Ocultar mensaje">Ocultar</button>
                </div>
            @endif

            {{-- Botones de navegación --}}
            <div class="button-container">
                <button onclick="showTab('servicios')" class="btn-tab active" id="btn-servicios">
                    <i class="fas fa-cogs"></i> Servicios
                </button>
                <button onclick="showTab('subservicios')" class="btn-tab" id="btn-subservicios">
                    <i class="fas fa-list-alt"></i> Subservicios
                </button>
                <button onclick="showTab('inventario')" class="btn-tab" id="btn-inventario">
                    <i class="fas fa-boxes"></i> Inventario
                </button>
                <button onclick="showTab('movimientos')" class="btn-tab" id="btn-movimientos">
                    <i class="fas fa-exchange-alt"></i> Movimientos
                </button>
                <button onclick="showTab('historial')" class="btn-tab" id="btn-historial">
                    <i class="fas fa-history"></i> Historial
                </button>
            </div>

            <div id="page-meta"
                data-initial-tab="{{ $activeTab ?? 'servicios' }}"
                data-subservicios-url="{{ route('usuarios.ajustes.subservicios') }}"
                style="display:none;"></div>

            @php
                $iconOptions = [
                    'fas fa-microphone' => 'Micrófono (Audio en vivo)',
                    'fas fa-volume-up' => 'Bocina (Sonido)',
                    'fas fa-headphones' => 'Auriculares (Monitoreo)',
                    'fas fa-music' => 'Nota musical (Producción musical)',
                    'fas fa-guitar' => 'Guitarra (Instrumental)',
                    'fas fa-drum' => 'Tambor (Percusión)',
                    'fas fa-theater-masks' => 'Máscaras (Animación y shows)',
                    'fas fa-magic' => 'Varita mágica (Efectos y animación)',
                    'fas fa-film' => 'Cinta de video (Producción audiovisual)',
                    'fas fa-video' => 'Cámara de video (Grabación)',
                    'fas fa-camera-retro' => 'Cámara retro (Publicidad visual)',
                    'fas fa-bullhorn' => 'Megáfono (Publicidad)',
                    'fas fa-ad' => 'Anuncio (Campañas publicitarias)',
                    'fas fa-mail-bulk' => 'Mailing (Promociones y difusión)',
                    'fas fa-lightbulb' => 'Bombilla (Ideas creativas)',
                    'fas fa-glass-cheers' => 'Brindis (Fiestas y celebraciones)',
                    'fas fa-cocktail' => 'Cóctel (Eventos sociales)',
                    'fas fa-birthday-cake' => 'Pastel (Cumpleaños y fiestas)',
                    'fas fa-star' => 'Estrella (Eventos especiales)',
                    'fas fa-users' => 'Equipo humano (Staff)',
                ];
                $defaultIconClass = 'fas fa-tag';
            @endphp

            {{-- Tab de Servicios --}}
            <div id="tab-servicios" class="tab-content active">
                {{-- Botón para abrir el modal de creación --}}
                <div class="button-container">
                    <button onclick="openModal('create')" class="btn-primary">
                        <i class="fas fa-plus-circle"></i> Agregar Nuevo Servicio
                    </button>
                </div>

                {{-- Tabla de servicios --}}
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre del Servicio</th>
                                <th>Ícono</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $serviciosList = isset($servicios) ? $servicios : collect([]);
                            @endphp
                            @forelse ($serviciosList as $servicio)
                                <tr>
                                    <td>{{ $loop->iteration }}</td> {{-- Número consecutivo limpio --}}
                                    <td>{{ $servicio->nombre_servicio }}</td>
                                    <td>
                                        @php
                                            $iconClass = $servicio->icono ?: $defaultIconClass;
                                        @endphp
                                        <span class="service-icon-cell">
                                            <i class="{{ $iconClass }}"></i>
                                            <small>{{ $servicio->icono ? 'Personalizado' : 'Predeterminado' }}</small>
                                        </span>
                                    </td>
                                    <td class="actions-cell">
                                        <button
                                            class="btn-action edit"
                                            data-id="{{ $servicio->id }}"
                                            data-nombre="{{ e($servicio->nombre_servicio) }}"
                                            data-descripcion="{{ e($servicio->descripcion ?? '') }}"
                                            data-icono="{{ e($servicio->icono ?? '') }}"
                                            onclick="handleServiceEdit(this)"
                                        >
                                            <i class="fas fa-edit"></i> Editar
                                        </button>
                                        <form action="{{ route('servicios.destroy', $servicio->id) }}" method="POST" style="display:inline;" onsubmit="event.preventDefault(); customConfirm('¿Estás seguro de que deseas eliminar este servicio?').then(result => { if(result) this.submit(); }); return false;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-action delete">
                                                <i class="fas fa-trash-alt"></i> Eliminar
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="no-services">No hay servicios registrados.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Tab de Subservicios dentro de Ajustes (sin navegar) --}}
            <div id="tab-subservicios" class="tab-content">
                <div class="button-container">
                    <button onclick="openSubservicioModal('create')" class="btn-primary">
                        <i class="fas fa-plus-circle"></i> Agregar Nuevo Subservicio
                    </button>
                </div>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Imagen</th>
                                <th>Nombre</th>
                                <th>Servicio</th>
                                <th>Descripción</th>
                                <th>Precio</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="subservicios-table-body">
                            @php $subServiciosList = isset($subServicios) ? $subServicios : collect([]); @endphp
                            @forelse ($subServiciosList as $sub)
                                <tr>
                                    <td>{{ $sub->id }}</td>
                                    <td>
                                        @if($sub->imagen)
                                            <img src="{{ asset('storage/subservicios/' . $sub->imagen) }}" alt="{{ $sub->nombre }}" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">
                                        @else
                                            <span style="color: #999;">Sin imagen</span>
                                        @endif
                                    </td>
                                    <td>{{ $sub->nombre }}</td>
                                    <td>{{ $sub->servicio->nombre_servicio ?? 'N/A' }}</td>
                                    <td>{{ \Illuminate\Support\Str::limit($sub->descripcion ?? '', 50) }}</td>
                                    <td>${{ number_format($sub->precio ?? 0, 0, ',', '.') }}</td>
                                    <td class="actions-cell">
                                        <button
                                            class="btn-action edit"
                                            data-id="{{ $sub->id }}"
                                            data-nombre="{{ e($sub->nombre) }}"
                                            data-descripcion="{{ e($sub->descripcion ?? '') }}"
                                            data-precio="{{ $sub->precio ?? 0 }}"
                                            data-servicios-id="{{ $sub->servicios_id }}"
                                            data-imagen="{{ $sub->imagen ? asset('storage/subservicios/' . $sub->imagen) : '' }}"
                                            onclick="handleSubservicioEdit(this)"
                                        >
                                            <i class="fas fa-edit"></i> Editar
                                        </button>
                                        <button
                                            class="btn-action delete"
                                            data-id="{{ $sub->id }}"
                                            onclick="handleSubservicioDelete(this)"
                                        >
                                            <i class="fas fa-trash-alt"></i> Eliminar
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="no-services">No hay subservicios registrados.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Tab de Inventario --}}
            <div id="tab-inventario" class="tab-content">
                <div class="button-container">
                    <button onclick="openInventarioModal('create')" class="btn-primary">
                        <i class="fas fa-plus-circle"></i> Agregar Nuevo Item
                    </button>
                </div>

                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Descripción</th>
                                <th>Stock</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="inventario-table-body">
                            {{-- Los datos se cargarán dinámicamente --}}
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Tab de Movimientos --}}
            <div id="tab-movimientos" class="tab-content">
                <div class="button-container">
                    <button onclick="openMovimientoModal('create')" class="btn-primary">
                        <i class="fas fa-plus-circle"></i> Nuevo Movimiento
                    </button>
                </div>

                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Item</th>
                                <th>Tipo</th>
                                <th>Cantidad</th>
                                <th>Fecha</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="movimientos-table-body">
                            {{-- Los datos se cargarán dinámicamente --}}
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Tab de Historial de Cotizaciones --}}
            <div id="tab-historial" class="tab-content">
                <div class="button-container" style="margin-bottom: 12px; gap: 8px; display: flex; align-items: center;">
                    <a href="{{ route('usuarios.ajustes.historial.pdf', ['group_by' => $groupBy ?? null]) }}" class="btn-primary">
                        <i class="fas fa-file-pdf"></i> Descargar PDF
                    </a>
                    <form method="GET" action="{{ route('usuarios.ajustes') }}" class="historial-filter-form">
                        <input type="hidden" name="tab" value="historial">
                        <label for="group_by">Agrupar por:</label>
                        <select id="group_by" name="group_by" onchange="this.form.submit()">
                            <option value="" {{ empty($groupBy) ? 'selected' : '' }}>Sin agrupación</option>
                            <option value="consulta" {{ ($groupBy ?? '') === 'consulta' ? 'selected' : '' }}>Consulta</option>
                            <option value="dia" {{ ($groupBy ?? '') === 'dia' ? 'selected' : '' }}>Día</option>
                        </select>
                    </form>
                </div>

                @php
                    $cotizacionesList = isset($cotizaciones) ? $cotizaciones : collect([]);
                    $groupedData = isset($groupedCotizaciones) ? $groupedCotizaciones : null;
                @endphp

                @if(empty($groupBy))
                    <div class="table-container">
                        <table>
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
                                @forelse ($cotizacionesList as $cotizacion)
                                    <tr>
                                        <td>{{ $cotizacion->id }}</td>
                                        <td>{{ $cotizacion->fecha_cotizacion ? $cotizacion->fecha_cotizacion->format('d/m/Y H:i:s') : 'N/A' }}</td>
                                        <td>
                                            @if($cotizacion->persona)
                                                {{ $cotizacion->persona->primer_nombre }} {{ $cotizacion->persona->primer_apellido }}
                                                <br>
                                                <small style="color: #999;">{{ $cotizacion->persona->correo ?? '' }}</small>
                                            @else
                                                N/A
                                            @endif
                                        </td>
                                        <td>{{ $cotizacion->subServicio->nombre ?? 'N/A' }}</td>
                                        <td>{{ optional(optional($cotizacion->subServicio)->servicio)->nombre_servicio ?? 'N/A' }}</td>
                                        <td>${{ number_format($cotizacion->monto ?? 0, 0, ',', '.') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="no-services">No hay cotizaciones en el historial.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                @elseif(($groupBy ?? '') === 'dia')
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th style="width: 25%;">Día</th>
                                    <th style="width: 25%;">Total del día</th>
                                    <th style="width: 50%;">Cantidad de cotizaciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($groupedData as $day => $data)
                                    <tr class="group-header">
                                        <td colspan="3">{{ \Carbon\Carbon::parse($day)->format('d/m/Y') }} — Total: ${{ number_format($data['total'] ?? 0, 0, ',', '.') }} ({{ $data['count'] }} items)</td>
                                    </tr>
                                    @foreach($data['items'] as $cotizacion)
                                        <tr>
                                            <td>{{ $cotizacion->fecha_cotizacion?->format('d/m/Y H:i') }}</td>
                                            <td>
                                                @if($cotizacion->persona)
                                                    {{ $cotizacion->persona->primer_nombre }} {{ $cotizacion->persona->primer_apellido }}
                                                @else
                                                    N/A
                                                @endif
                                            </td>
                                            <td>${{ number_format($cotizacion->monto ?? 0, 0, ',', '.') }} — {{ $cotizacion->subServicio->nombre ?? 'N/A' }} / {{ optional(optional($cotizacion->subServicio)->servicio)->nombre_servicio ?? 'N/A' }}</td>
                                        </tr>
                                    @endforeach
                                @empty
                                    <tr>
                                        <td colspan="3" class="no-services">No hay cotizaciones en el historial.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                @elseif(($groupBy ?? '') === 'consulta')
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th style="width: 40%;">Consulta</th>
                                    <th style="width: 30%;">Cliente</th>
                                    <th style="width: 30%;">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($groupedData as $key => $data)
                                    <tr class="group-header">
                                        <td colspan="3">{{ optional($data['timestamp'])->format('d/m/Y H:i:s') }} — {{ $data['count'] }} items</td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <ul class="ul-compact">
                                                @foreach($data['items'] as $cotizacion)
                                                    <li>
                                                        {{ $cotizacion->subServicio->nombre ?? 'N/A' }} ({{ optional(optional($cotizacion->subServicio)->servicio)->nombre_servicio ?? 'N/A' }}) — ${{ number_format($cotizacion->monto ?? 0, 0, ',', '.') }}
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </td>
                                        <td>
                                            @if($data['persona'])
                                                {{ $data['persona']->primer_nombre }} {{ $data['persona']->primer_apellido }}
                                                <br><small style="color:#999;">{{ $data['persona']->correo ?? '' }}</small>
                                            @else
                                                N/A
                                            @endif
                                        </td>
                                        <td>${{ number_format($data['total'] ?? 0, 0, ',', '.') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="no-services">No hay cotizaciones en el historial.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            {{-- Modal para crear y editar servicios --}}
            <div id="serviceModal" class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3 id="modalTitle" aria-label="Detalles del servicio">Servicio</h3>
                        <button type="button" class="close-btn" onclick="closeModal()" aria-label="Cerrar">&times;</button>
                    </div>
                    <form id="serviceForm" method="POST">
                        @csrf
                        <input type="hidden" name="_method" id="formMethod">
                        <div class="form-group">
                            <label for="nombre_servicio">Nombre del Servicio:</label>
                            <input type="text" id="nombre_servicio" name="nombre_servicio" required>
                        </div>
                        <div class="form-group">
                            <label for="icono">Ícono representativo:</label>
                            <div class="icon-picker">
                                <select id="icono" name="icono" data-default-icon="{{ $defaultIconClass }}">
                                    <option value="">Sin ícono</option>
                                    @foreach($iconOptions as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                <span class="icon-preview" id="iconPreview">
                                    <i class="{{ $defaultIconClass }}"></i>
                                </span>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="descripcion">Descripción:</label>
                            <textarea id="descripcion" name="descripcion" rows="3"></textarea>
                        </div>
                        <button type="submit" class="btn-submit">Guardar</button>
                    </form>
                </div>
            </div>

            {{-- Modal para inventario --}}
            <div id="inventarioModal" class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3 id="inventarioModalTitle" aria-label="Detalles del inventario">Inventario</h3>
                        <button type="button" class="close-btn" onclick="closeInventarioModal()" aria-label="Cerrar">&times;</button>
                    </div>
                    <form id="inventarioForm" method="POST">
                        @csrf
                        <input type="hidden" name="_method" id="inventarioFormMethod">
                        <div class="form-group">
                            <label for="descripcion_inventario">Descripción:</label>
                            <input type="text" id="descripcion_inventario" name="descripcion" required>
                        </div>
                        <div class="form-group">
                            <label for="stock">Stock:</label>
                            <input type="number" id="stock" name="stock" min="0" required>
                        </div>
                        <button type="submit" class="btn-submit">Guardar</button>
                    </form>
                </div>
            </div>

            {{-- Modal para movimientos --}}
            <div id="movimientoModal" class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3 id="movimientoModalTitle" aria-label="Detalles del movimiento">Movimiento</h3>
                        <button type="button" class="close-btn" onclick="closeMovimientoModal()" aria-label="Cerrar">&times;</button>
                    </div>
                    <form id="movimientoForm" method="POST">
                        @csrf
                        <input type="hidden" name="_method" id="movimientoFormMethod">
                        <div class="form-group">
                            <label for="inventario_id">Item del Inventario:</label>
                            <select id="inventario_id" name="inventario_id" required>
                                <option value="">Seleccionar item</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="tipo_movimiento">Tipo de Movimiento:</label>
                            <select id="tipo_movimiento" name="tipo_movimiento" required>
                                <option value="">Seleccionar tipo</option>
                                <option value="entrada">Entrada</option>
                                <option value="salida">Salida</option>
                                <option value="alquilado">Alquilado</option>
                                <option value="devuelto">Devuelto</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="cantidad">Cantidad:</label>
                            <input type="number" id="cantidad" name="cantidad" min="1" required>
                        </div>
                        <button type="submit" class="btn-submit">Guardar</button>
                    </form>
                </div>
            </div>

            {{-- Modal para subservicios --}}
            <div id="subservicioModal" class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3 id="subservicioModalTitle" aria-label="Detalles del subservicio">Subservicio</h3>
                        <button type="button" class="close-btn" onclick="closeSubservicioModal()" aria-label="Cerrar">&times;</button>
                    </div>
                    <form id="subservicioForm" method="POST" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="_method" id="subservicioFormMethod">
                        <div class="form-group">
                            <label for="servicios_id_subservicio">Servicio:</label>
                            <select id="servicios_id_subservicio" name="servicios_id" required>
                                <option value="">Seleccione un servicio</option>
                                @foreach($servicios ?? [] as $servicio)
                                    <option value="{{ $servicio->id }}">{{ $servicio->nombre_servicio }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="nombre_subservicio">Nombre del Subservicio:</label>
                            <input type="text" id="nombre_subservicio" name="nombre" required maxlength="100">
                        </div>
                        <div class="form-group">
                            <label for="descripcion_subservicio">Descripción:</label>
                            <textarea id="descripcion_subservicio" name="descripcion" rows="4"></textarea>
                        </div>
                        <div class="form-group">
                            <label for="precio_subservicio">Precio:</label>
                            <input type="number" id="precio_subservicio" name="precio" step="0.01" min="0" required>
                        </div>
                        <div class="form-group">
                            <label for="imagen_subservicio">Imagen:</label>
                            <input type="file" id="imagen_subservicio" name="imagen" accept="image/jpeg,image/png,image/jpg,image/gif,image/webp">
                            <small style="display: block; margin-top: 5px; color: #666;">Formatos permitidos: JPEG, PNG, JPG, GIF, WEBP. Tamaño máximo: 5MB</small>
                            <div id="imagen-preview-subservicio" style="margin-top: 10px; display: none;">
                                <img id="imagen-preview-img-subservicio" src="" alt="Vista previa" style="max-width: 200px; max-height: 200px; border-radius: 4px;">
                            </div>
                            <div id="imagen-actual-subservicio" style="margin-top: 10px; display: none;">
                                <p style="margin: 5px 0; color: #666;">Imagen actual:</p>
                                <img id="imagen-actual-img-subservicio" src="" alt="Actual" style="max-width: 200px; max-height: 200px; border-radius: 4px;">
                            </div>
                        </div>
                        <button type="submit" class="btn-submit">Guardar</button>
                    </form>
                </div>
            </div>

        </main>

    <script>
        // Variables globales
        const serviceModal = document.getElementById('serviceModal');
        const inventarioModal = document.getElementById('inventarioModal');
        const movimientoModal = document.getElementById('movimientoModal');
        const subservicioModal = document.getElementById('subservicioModal');
        const pageMetaEl = document.getElementById('page-meta');
        const subserviciosEndpoint = pageMetaEl ? pageMetaEl.getAttribute('data-subservicios-url') : '';

        // Función para mostrar pestañas
        function showTab(tabName) {
            // Ocultar todas las pestañas
            const tabs = document.querySelectorAll('.tab-content');
            tabs.forEach(tab => tab.classList.remove('active'));
            
            // Remover clase active de todos los botones
            const buttons = document.querySelectorAll('.btn-tab');
            buttons.forEach(btn => btn.classList.remove('active'));
            
            // Mostrar la pestaña seleccionada
            document.getElementById('tab-' + tabName).classList.add('active');
            document.getElementById('btn-' + tabName).classList.add('active');
            
            // Cargar datos según la pestaña
            if (tabName === 'inventario') {
                loadInventario();
            } else if (tabName === 'movimientos') {
                loadMovimientos();
                loadInventarioOptions();
            } else if (tabName === 'subservicios') {
                // Los subservicios se cargan inicialmente desde el servidor
                // Solo se recargan cuando hay cambios
            }
        }

        // Activar pestaña según el servidor (si hay agrupación, ir a historial)
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('[data-alert]');
            alerts.forEach(alert => {
                const hideAlert = () => {
                    if (!alert.classList.contains('hidden')) {
                        alert.classList.add('hidden');
                        setTimeout(() => {
                            if (alert.parentNode) {
                                alert.parentNode.removeChild(alert);
                            }
                        }, 300);
                    }
                };

                const closeBtn = alert.querySelector('.alert-hide');
                if (closeBtn) {
                    closeBtn.addEventListener('click', hideAlert);
                }

                setTimeout(hideAlert, 10000);
            });

            const initialTab = pageMetaEl ? pageMetaEl.getAttribute('data-initial-tab') : 'servicios';
            showTab(initialTab);

            const oldDataEl = document.getElementById('service-old-data');
            if (oldDataEl) {
                openModal(
                    'edit',
                    oldDataEl.dataset.id,
                    oldDataEl.dataset.nombre || '',
                    oldDataEl.dataset.descripcion || '',
                    oldDataEl.dataset.icono || ''
                );
                oldDataEl.remove();
            }
        });

        // Funciones para servicios (existentes)
        const modalTitle = document.getElementById('modalTitle');
        const serviceForm = document.getElementById('serviceForm');
        const formMethod = document.getElementById('formMethod');
        const nombreInput = document.getElementById('nombre_servicio');
        const descripcionInput = document.getElementById('descripcion');
        const iconoSelect = document.getElementById('icono');
        const iconPreview = document.getElementById('iconPreview');
        const iconDefaultClass = iconoSelect ? (iconoSelect.dataset.defaultIcon || 'fas fa-tag') : 'fas fa-tag';

        function updateIconPreview(className) {
            if (!iconPreview) return;
            const iconClass = className && className.trim() !== '' ? className : iconDefaultClass;
            iconPreview.innerHTML = `<i class="${iconClass}"></i>`;
        }

        if (iconoSelect) {
            iconoSelect.addEventListener('change', () => {
                updateIconPreview(iconoSelect.value);
            });
        }

        function setIconSelectValue(iconClass) {
            if (!iconoSelect) return;
            const options = Array.from(iconoSelect.options).map(opt => opt.value);
            if (!iconClass) {
                iconoSelect.value = '';
                updateIconPreview('');
                return;
            }
            if (options.includes(iconClass)) {
                iconoSelect.value = iconClass;
            } else if (options.includes(iconDefaultClass)) {
                iconoSelect.value = iconDefaultClass;
            } else {
                iconoSelect.value = '';
            }
            updateIconPreview(iconoSelect.value);
        }

        function openModal(mode, id = null, nombre = '', descripcion = '', icono = '') {
            if (mode === 'create') {
                modalTitle.textContent = 'Crear Nuevo Servicio';
                serviceForm.action = "{{ route('servicios.store') }}";
                formMethod.name = '_method';
                formMethod.value = 'POST';
                nombreInput.value = '';
                descripcionInput.value = '';
                setIconSelectValue(iconDefaultClass);
            } else if (mode === 'edit') {
                modalTitle.textContent = 'Editar Servicio';
                serviceForm.action = `{{ url('servicios') }}/${id}`;
                formMethod.name = '_method';
                formMethod.value = 'PUT';
                nombreInput.value = nombre;
                descripcionInput.value = descripcion || '';
                setIconSelectValue(icono);
            }
            serviceModal.style.display = 'flex';
        }

        window.handleServiceEdit = function(button) {
            if (!button) return;
            const { id, nombre, descripcion, icono } = button.dataset;
            openModal('edit', id, nombre || '', descripcion || '', icono || '');
        };

        window.handleSubservicioEdit = function(button) {
            if (!button) return;
            const { id, nombre, descripcion, precio, serviciosId, imagen } = button.dataset;
            openSubservicioModal(
                'edit',
                id,
                nombre || '',
                descripcion || '',
                precio || 0,
                serviciosId || '',
                imagen || ''
            );
        };

        window.handleSubservicioDelete = function(button) {
            if (!button) {
                console.error('handleSubservicioDelete: button es null o undefined');
                return;
            }
            const id = button.dataset.id;
            console.log('handleSubservicioDelete llamado con id:', id);
            if (id) {
                if (typeof window.deleteSubservicio === 'function') {
                    window.deleteSubservicio(id);
                } else {
                    console.error('deleteSubservicio no está disponible');
                    alert('Error: La función de eliminación no está disponible. Por favor, recarga la página.');
                }
            } else {
                console.error('handleSubservicioDelete: No se encontró el id en dataset');
            }
        };

        function closeModal() {
            serviceModal.style.display = 'none';
        }

        // Funciones para subservicios
        function openSubservicioModal(mode, id = null, nombre = '', descripcion = '', precio = 0, servicios_id = '', imagenUrl = '') {
            const subservicioModalTitle = document.getElementById('subservicioModalTitle');
            const subservicioForm = document.getElementById('subservicioForm');
            const subservicioFormMethod = document.getElementById('subservicioFormMethod');
            const imagenInput = document.getElementById('imagen_subservicio');
            const imagenPreview = document.getElementById('imagen-preview-subservicio');
            const imagenPreviewImg = document.getElementById('imagen-preview-img-subservicio');
            const imagenActual = document.getElementById('imagen-actual-subservicio');
            const imagenActualImg = document.getElementById('imagen-actual-img-subservicio');
            
            if (mode === 'create') {
                subservicioModalTitle.textContent = 'Crear Nuevo Subservicio';
                subservicioForm.action = "{{ route('subservicios.store') }}";
                subservicioFormMethod.name = '_method';
                subservicioFormMethod.value = 'POST';
                // Limpiar campos sin resetear el formulario completo (para mantener el token CSRF)
                document.getElementById('nombre_subservicio').value = '';
                document.getElementById('descripcion_subservicio').value = '';
                document.getElementById('precio_subservicio').value = '';
                document.getElementById('servicios_id_subservicio').value = '';
                imagenInput.value = '';
                imagenPreview.style.display = 'none';
                imagenActual.style.display = 'none';
            } else if (mode === 'edit') {
                subservicioModalTitle.textContent = 'Editar Subservicio';
                subservicioForm.action = `{{ url('subservicios') }}/${id}`;
                subservicioFormMethod.name = '_method';
                subservicioFormMethod.value = 'PUT';
                document.getElementById('nombre_subservicio').value = nombre;
                document.getElementById('descripcion_subservicio').value = descripcion;
                document.getElementById('precio_subservicio').value = precio;
                document.getElementById('servicios_id_subservicio').value = servicios_id;
                imagenInput.value = '';
                
                // Mostrar imagen actual si existe
                if (imagenUrl) {
                    imagenActualImg.src = imagenUrl;
                    imagenActual.style.display = 'block';
                } else {
                    imagenActual.style.display = 'none';
                }
                imagenPreview.style.display = 'none';
            }
            subservicioModal.style.display = 'flex';
        }
        
        // Vista previa de imagen al seleccionar archivo en ajustes
        document.addEventListener('DOMContentLoaded', function() {
            const imagenInputSubservicio = document.getElementById('imagen_subservicio');
            if (imagenInputSubservicio) {
                imagenInputSubservicio.addEventListener('change', function(e) {
                    const file = e.target.files[0];
                    const preview = document.getElementById('imagen-preview-subservicio');
                    const previewImg = document.getElementById('imagen-preview-img-subservicio');
                    const imagenActual = document.getElementById('imagen-actual-subservicio');
                    
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            previewImg.src = e.target.result;
                            preview.style.display = 'block';
                            imagenActual.style.display = 'none';
                        };
                        reader.readAsDataURL(file);
                    } else {
                        preview.style.display = 'none';
                    }
                });
            }
        });

        function closeSubservicioModal() {
            subservicioModal.style.display = 'none';
        }

        // Función para cargar los subservicios dinámicamente
        function loadSubservicios() {
            if (!subserviciosEndpoint) return;
            fetch(subserviciosEndpoint, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                const tbody = document.getElementById('subservicios-table-body');
                if (!tbody) return;
                
                tbody.innerHTML = '';
                
                if (data.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="7" class="no-services">No hay subservicios registrados.</td></tr>';
                    return;
                }
                
                data.forEach(sub => {
                    // Escapar caracteres especiales para JavaScript
                    const escapeHtml = (str) => {
                        if (!str) return '';
                        return String(str)
                            .replace(/&/g, '&amp;')
                            .replace(/</g, '&lt;')
                            .replace(/>/g, '&gt;')
                            .replace(/"/g, '&quot;')
                            .replace(/'/g, '&#039;');
                    };
                    
                    const servicioNombre = sub.servicio ? escapeHtml(sub.servicio.nombre_servicio || 'N/A') : 'N/A';
                    const nombreHtml = escapeHtml(sub.nombre || '');
                    const nombreAttr = escapeHtml(sub.nombre || '');
                    const descripcionHtml = sub.descripcion ? (sub.descripcion.length > 50 ? escapeHtml(sub.descripcion.substring(0, 50)) + '...' : escapeHtml(sub.descripcion)) : '';
                    const descripcionAttr = escapeHtml(sub.descripcion || '');
                    const precio = new Intl.NumberFormat('es-CO', { minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(sub.precio || 0);
                    const imagenUrl = sub.imagen ? `/storage/subservicios/${sub.imagen}` : '';
                    const imagenHtml = sub.imagen
                        ? `<img src="${imagenUrl}" alt="${nombreAttr}" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">`
                        : '<span style="color: #999;">Sin imagen</span>';
                    
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${sub.id}</td>
                        <td>${imagenHtml}</td>
                        <td>${nombreHtml}</td>
                        <td>${servicioNombre}</td>
                        <td>${descripcionHtml}</td>
                        <td>$${precio}</td>
                        <td class="actions-cell">
                            <button
                                class="btn-action edit"
                                data-id="${sub.id}"
                                data-nombre="${nombreAttr}"
                                data-descripcion="${descripcionAttr}"
                                data-precio="${sub.precio || 0}"
                                data-servicios-id="${sub.servicios_id}"
                                data-imagen="${imagenUrl}"
                                onclick="handleSubservicioEdit(this)"
                            >
                                <i class="fas fa-edit"></i> Editar
                            </button>
                            <button
                                class="btn-action delete"
                                data-id="${sub.id}"
                                onclick="handleSubservicioDelete(this)"
                            >
                                <i class="fas fa-trash-alt"></i> Eliminar
                            </button>
                        </td>
                    `;
                    tbody.appendChild(row);
                });
            })
            .catch(error => {
                console.error('Error al cargar subservicios:', error);
            });
        }

        window.deleteSubservicio = async function(id) {
            console.log('deleteSubservicio llamado con id:', id);
            
            // Verificar que customConfirm esté disponible
            console.log('Verificando customConfirm...');
            console.log('window.customConfirm:', typeof window.customConfirm);
            console.log('customConfirm (global):', typeof customConfirm);
            
            const confirmFn = window.customConfirm || customConfirm;
            if (typeof confirmFn !== 'function') {
                console.error('customConfirm no está disponible');
                // Intentar usar confirm nativo como fallback
                const useNative = confirm('¿Estás seguro de que deseas eliminar este subservicio?');
                if (!useNative) {
                    console.log('Usuario canceló la eliminación (usando confirm nativo)');
                    return;
                }
                console.log('Usuario confirmó la eliminación (usando confirm nativo), procediendo...');
            } else {
                try {
                    console.log('Llamando a customConfirm...');
                    const confirmed = await confirmFn('¿Estás seguro de que deseas eliminar este subservicio?');
                    console.log('Resultado de customConfirm:', confirmed);
                    if (!confirmed) {
                        console.log('Usuario canceló la eliminación');
                        return;
                    }
                    console.log('Usuario confirmó la eliminación, procediendo...');
                } catch (confirmError) {
                    console.error('Error al llamar customConfirm:', confirmError);
                    // Fallback a confirm nativo
                    const useNative = confirm('¿Estás seguro de que deseas eliminar este subservicio?');
                    if (!useNative) {
                        console.log('Usuario canceló la eliminación (fallback)');
                        return;
                    }
                    console.log('Usuario confirmó la eliminación (fallback), procediendo...');
                }
            }
            
            try {
                
                // Usar la ruta de Laravel en lugar de URL hardcodeada
                const url = `{{ url('subservicios') }}/${id}`;
                console.log('URL de eliminación:', url);
                
                const formData = new FormData();
                formData.append('_method', 'DELETE');

                const csrfMeta = document.querySelector('meta[name="csrf-token"]');
                const csrfInput = document.querySelector('input[name="_token"]');
                const csrfToken = csrfMeta?.getAttribute('content') ||
                                 csrfInput?.value ||
                                 '{{ csrf_token() }}';

                console.log('CSRF Token obtenido:', csrfToken ? 'Sí' : 'No');

                if (!csrfToken) {
                    showAlert('Error: No se pudo obtener el token de seguridad. Por favor, recarga la página.', 'error');
                    return;
                }

                try {
                    console.log('Enviando petición DELETE a:', url);
                    const response = await fetch(url, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        credentials: 'same-origin'
                    });

                    console.log('Respuesta recibida - Status:', response.status, 'OK:', response.ok);
                    console.log('Content-Type:', response.headers.get('content-type'));

                    const contentType = response.headers.get('content-type') || '';
                    let data = {};

                    if (contentType.includes('application/json')) {
                        data = await response.json();
                        console.log('Datos JSON recibidos:', data);
                    } else {
                        const text = await response.text();
                        console.log('Respuesta texto recibida:', text);
                        if (text) {
                            try {
                                data = JSON.parse(text);
                            } catch (e) {
                                data = { message: text };
                            }
                        }
                    }

                    if (!response.ok) {
                        console.error('Error en respuesta:', data);
                        throw new Error(data.error || data.message || `Error ${response.status}: ${response.statusText}`);
                    }

                    if (data.success) {
                        console.log('Eliminación exitosa:', data.success);
                        showAlert(data.success);
                        showTab('subservicios');
                        loadSubservicios();
                    } else if (data.error) {
                        console.error('Error en datos:', data.error);
                        showAlert(data.error, 'error');
                    } else {
                        console.warn('Respuesta sin success ni error:', data);
                        showAlert('La eliminación se completó, pero no se recibió confirmación del servidor.', 'error');
                    }
                } catch (error) {
                    console.error('Error al eliminar subservicio:', error);
                    console.error('Stack trace:', error.stack);
                    const errorMsg = error.message || 'Error al eliminar el subservicio. Por favor, intenta nuevamente.';
                    showAlert(errorMsg, 'error');
                }
            } catch (error) {
                console.error('Error en deleteSubservicio (nivel superior):', error);
                console.error('Stack trace:', error.stack);
                const errorMsg = 'Error inesperado al intentar eliminar el subservicio: ' + (error.message || error);
                console.error('Mensaje de error completo:', errorMsg);
                showAlert(errorMsg, 'error');
            }
        };

        function openInventarioModal(mode, id = null, descripcion = '', stock = '') {
            const inventarioModalTitle = document.getElementById('inventarioModalTitle');
            const inventarioForm = document.getElementById('inventarioForm');
            const inventarioFormMethod = document.getElementById('inventarioFormMethod');
            
            if (mode === 'create') {
                inventarioModalTitle.textContent = 'Crear Nuevo Item';
                inventarioForm.action = "{{ route('inventario.store') }}";
                inventarioFormMethod.name = '_method';
                inventarioFormMethod.value = 'POST';
                document.getElementById('descripcion_inventario').value = '';
                document.getElementById('stock').value = '';
            } else if (mode === 'edit') {
                inventarioModalTitle.textContent = 'Editar Item';
                inventarioForm.action = `{{ url('inventario') }}/${id}`;
                inventarioFormMethod.name = '_method';
                inventarioFormMethod.value = 'PUT';
                document.getElementById('descripcion_inventario').value = descripcion;
                document.getElementById('stock').value = stock;
            }
            inventarioModal.style.display = 'flex';
        }

        function closeInventarioModal() {
            inventarioModal.style.display = 'none';
        }

        // Funciones para movimientos
        function openMovimientoModal(mode, id = null, inventarioId = '', tipoMovimiento = '', cantidad = '') {
            const movimientoModalTitle = document.getElementById('movimientoModalTitle');
            const movimientoForm = document.getElementById('movimientoForm');
            const movimientoFormMethod = document.getElementById('movimientoFormMethod');
            
            if (mode === 'create') {
                movimientoModalTitle.textContent = 'Nuevo Movimiento';
                movimientoForm.action = "{{ route('movimientos.store') }}";
                movimientoFormMethod.name = '_method';
                movimientoFormMethod.value = 'POST';
                document.getElementById('cantidad').value = '';
                document.getElementById('tipo_movimiento').value = '';
                document.getElementById('inventario_id').value = '';
            } else if (mode === 'edit') {
                movimientoModalTitle.textContent = 'Editar Movimiento';
                movimientoForm.action = `{{ url('movimientos') }}/${id}`;
                movimientoFormMethod.name = '_method';
                movimientoFormMethod.value = 'PUT';
                document.getElementById('cantidad').value = cantidad;
                document.getElementById('tipo_movimiento').value = tipoMovimiento;
                document.getElementById('inventario_id').value = inventarioId;
            }
            movimientoModal.style.display = 'flex';
        }

        function closeMovimientoModal() {
            movimientoModal.style.display = 'none';
        }

        // Cargar datos de inventario
        function loadInventario() {
            fetch('{{ route("inventario.index") }}')
                .then(response => response.json())
                .then(data => {
                    const tbody = document.getElementById('inventario-table-body');
                    tbody.innerHTML = '';
                    
                    data.forEach(item => {
                        const row = `
                            <tr>
                                <td>${item.id}</td>
                                <td>${item.descripcion}</td>
                                <td>${item.stock}</td>
                                <td class="actions-cell">
                                    <button onclick="openInventarioModal('edit', ${item.id}, '${item.descripcion}', ${item.stock})" class="btn-action edit">
                                        <i class="fas fa-edit"></i> Editar
                                    </button>
                                    <button onclick="if(typeof window.deleteInventario === 'function') { window.deleteInventario(${item.id}); } else { console.error('deleteInventario no disponible'); alert('Error: Función no disponible'); }" class="btn-action delete">
                                        <i class="fas fa-trash-alt"></i> Eliminar
                                    </button>
                                </td>
                            </tr>
                        `;
                        tbody.innerHTML += row;
                    });
                })
                .catch(error => console.error('Error:', error));
        }

        // Cargar datos de movimientos
        function loadMovimientos() {
            fetch('{{ route("movimientos.index") }}')
                .then(response => response.json())
                .then(data => {
                    const tbody = document.getElementById('movimientos-table-body');
                    tbody.innerHTML = '';
                    const tipoLabels = {
                        entrada: 'Entrada',
                        salida: 'Salida',
                        alquilado: 'Alquilado',
                        devuelto: 'Devuelto'
                    };
                    
                    data.forEach(movimiento => {
                        const fecha = new Date(movimiento.fecha_movimiento).toLocaleDateString();
                        const tipoTexto = tipoLabels[movimiento.tipo_movimiento] || movimiento.tipo_movimiento;
                        const row = `
                            <tr>
                                <td>${movimiento.id}</td>
                                <td>${movimiento.inventario ? movimiento.inventario.descripcion : 'N/A'}</td>
                                <td>${tipoTexto}</td>
                                <td>${movimiento.cantidad}</td>
                                <td>${fecha}</td>
                                <td class="actions-cell">
                                    <button onclick="openMovimientoModal('edit', ${movimiento.id}, '${movimiento.inventario_id}', '${movimiento.tipo_movimiento}', '${movimiento.cantidad}')" class="btn-action edit">
                                        <i class="fas fa-edit"></i> Editar
                                    </button>
                                    <button onclick="if(typeof window.deleteMovimiento === 'function') { window.deleteMovimiento(${movimiento.id}); } else { console.error('deleteMovimiento no disponible'); alert('Error: Función no disponible'); }" class="btn-action delete">
                                        <i class="fas fa-trash-alt"></i> Eliminar
                                    </button>
                                </td>
                            </tr>
                        `;
                        tbody.innerHTML += row;
                    });
                })
                .catch(error => console.error('Error:', error));
        }

        // Cargar opciones de inventario para el select
        function loadInventarioOptions() {
            fetch('{{ route("inventario.index") }}')
                .then(response => response.json())
                .then(data => {
                    const select = document.getElementById('inventario_id');
                    select.innerHTML = '<option value="">Seleccionar item</option>';
                    
                    data.forEach(item => {
                        const option = document.createElement('option');
                        option.value = item.id;
                        option.textContent = item.descripcion;
                        select.appendChild(option);
                    });
                })
                .catch(error => console.error('Error:', error));
        }

        // Función para mostrar alertas tipo toast (notificaciones temporales)
        function showAlert(message, type = 'success') {
            const alertDiv = document.createElement('div');
            alertDiv.className = `custom-toast custom-toast-${type}`;
            alertDiv.textContent = message;
            alertDiv.style.position = 'fixed';
            alertDiv.style.top = '20px';
            alertDiv.style.right = '20px';
            alertDiv.style.zIndex = '99999';
            alertDiv.style.padding = '16px 24px';
            alertDiv.style.borderRadius = '8px';
            alertDiv.style.color = 'white';
            alertDiv.style.fontWeight = '600';
            alertDiv.style.fontSize = '14px';
            alertDiv.style.boxShadow = '0 4px 12px rgba(0, 0, 0, 0.3)';
            alertDiv.style.minWidth = '300px';
            alertDiv.style.maxWidth = '400px';
            alertDiv.style.animation = 'toastSlideIn 0.3s ease-out';
            alertDiv.style.display = 'flex';
            alertDiv.style.alignItems = 'center';
            alertDiv.style.gap = '12px';
            
            if (type === 'success') {
                alertDiv.style.backgroundColor = '#2d2d2d';
                alertDiv.style.borderLeft = '4px solid #4CAF50';
                alertDiv.innerHTML = '<i class="fas fa-check-circle" style="color: #4CAF50; font-size: 18px;"></i> ' + message;
            } else {
                alertDiv.style.backgroundColor = '#2d2d2d';
                alertDiv.style.borderLeft = '4px solid #e91c1c';
                alertDiv.innerHTML = '<i class="fas fa-exclamation-circle" style="color: #e91c1c; font-size: 18px;"></i> ' + message;
            }
            
            document.body.appendChild(alertDiv);
            
            setTimeout(() => {
                alertDiv.style.animation = 'toastSlideOut 0.3s ease-out';
                setTimeout(() => {
                    alertDiv.remove();
                }, 300);
            }, 3000);
        }
        
        // Agregar animaciones CSS para los toasts
        if (!document.getElementById('toast-animations')) {
            const style = document.createElement('style');
            style.id = 'toast-animations';
            style.textContent = `
                @keyframes toastSlideIn {
                    from {
                        opacity: 0;
                        transform: translateX(100%);
                    }
                    to {
                        opacity: 1;
                        transform: translateX(0);
                    }
                }
                @keyframes toastSlideOut {
                    from {
                        opacity: 1;
                        transform: translateX(0);
                    }
                    to {
                        opacity: 0;
                        transform: translateX(100%);
                    }
                }
            `;
            document.head.appendChild(style);
        }

        // Funciones de eliminación
        window.deleteInventario = async function(id) {
            console.log('deleteInventario llamado con id:', id);
            
            // Verificar que customConfirm esté disponible
            const confirmFn = window.customConfirm || customConfirm;
            if (typeof confirmFn !== 'function') {
                console.error('customConfirm no está disponible');
                alert('Error: La función de confirmación no está disponible. Por favor, recarga la página.');
                return;
            }
            
            try {
                const confirmed = await confirmFn('¿Estás seguro de que deseas eliminar este item?');
                if (!confirmed) {
                    console.log('Usuario canceló la eliminación');
                    return;
                }
                const url = `{{ url('inventario') }}/${id}`;
                const formData = new FormData();
                formData.append('_method', 'DELETE');

                const csrfMeta = document.querySelector('meta[name="csrf-token"]');
                const csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '{{ csrf_token() }}';

                if (!csrfToken) {
                    showAlert('Error: No se pudo obtener el token de seguridad. Por favor, recarga la página.', 'error');
                    return;
                }

                try {
                    const response = await fetch(url, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        credentials: 'same-origin'
                    });

                    const contentType = response.headers.get('content-type') || '';
                    let data = {};

                    if (contentType.includes('application/json')) {
                        data = await response.json();
                    } else {
                        const text = await response.text();
                        if (text) {
                            try {
                                data = JSON.parse(text);
                            } catch (e) {
                                data = { message: text };
                            }
                        }
                    }

                    if (!response.ok) {
                        throw new Error(data.error || data.message || `Error ${response.status}: ${response.statusText}`);
                    }

                    if (data.success) {
                        showAlert(data.success);
                        loadInventario();
                    } else if (data.error) {
                        showAlert(data.error, 'error');
                    }
                } catch (error) {
                    console.error('Error al eliminar inventario:', error);
                    const errorMsg = error.message || 'Error al eliminar el artículo del inventario. Por favor, intenta nuevamente.';
                    showAlert(errorMsg, 'error');
                }
            } catch (error) {
                console.error('Error en deleteInventario:', error);
                showAlert('Error inesperado al intentar eliminar el artículo', 'error');
            }
        };

        window.deleteMovimiento = async function(id) {
            console.log('deleteMovimiento llamado con id:', id);
            
            // Verificar que customConfirm esté disponible
            const confirmFn = window.customConfirm || customConfirm;
            if (typeof confirmFn !== 'function') {
                console.error('customConfirm no está disponible');
                alert('Error: La función de confirmación no está disponible. Por favor, recarga la página.');
                return;
            }
            
            try {
                const confirmed = await confirmFn('¿Estás seguro de que deseas eliminar este movimiento?');
                if (!confirmed) {
                    console.log('Usuario canceló la eliminación');
                    return;
                }
                const url = `{{ url('movimientos') }}/${id}`;
                const formData = new FormData();
                formData.append('_method', 'DELETE');

                const csrfMeta = document.querySelector('meta[name="csrf-token"]');
                const csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '{{ csrf_token() }}';

                if (!csrfToken) {
                    showAlert('Error: No se pudo obtener el token de seguridad. Por favor, recarga la página.', 'error');
                    return;
                }

                try {
                    const response = await fetch(url, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        credentials: 'same-origin'
                    });

                    const contentType = response.headers.get('content-type') || '';
                    let data = {};

                    if (contentType.includes('application/json')) {
                        data = await response.json();
                    } else {
                        const text = await response.text();
                        if (text) {
                            try {
                                data = JSON.parse(text);
                            } catch (e) {
                                data = { message: text };
                            }
                        }
                    }

                    if (!response.ok) {
                        throw new Error(data.error || data.message || `Error ${response.status}: ${response.statusText}`);
                    }

                    if (data.success) {
                        showAlert(data.success);
                        loadMovimientos();
                    } else if (data.error) {
                        showAlert(data.error, 'error');
                    }
                } catch (error) {
                    console.error('Error al eliminar movimiento:', error);
                    const errorMsg = error.message || 'Error al eliminar el movimiento. Por favor, intenta nuevamente.';
                    showAlert(errorMsg, 'error');
                }
            } catch (error) {
                console.error('Error en deleteMovimiento:', error);
                showAlert('Error inesperado al intentar eliminar el movimiento', 'error');
            }
        };
        
        // Cierra los modales si se hace clic fuera de ellos
        window.onclick = function(event) {
            if (event.target == serviceModal) {
                closeModal();
            }
            if (event.target == inventarioModal) {
                closeInventarioModal();
            }
            if (event.target == movimientoModal) {
                closeMovimientoModal();
            }
            if (event.target == subservicioModal) {
                closeSubservicioModal();
            }
        }

        
        
        // Manejadores de eventos para formularios
        document.addEventListener('DOMContentLoaded', function() {
            // Formulario de inventario
            const inventarioForm = document.getElementById('inventarioForm');
            if (inventarioForm) {
                inventarioForm.addEventListener('submit', function(e) {
                    e.preventDefault();

                    const formData = new FormData(this);
                    const url = this.action;
                    const methodValue = document.getElementById('inventarioFormMethod').value;
                    // usar POST y spoofear si el método es PUT/DELETE (compatibilidad con multipart)
                    const fetchMethod = (methodValue === 'PUT' || methodValue === 'DELETE') ? 'POST' : methodValue;

                    // si no existe _method en formData, añadirlo (por si acaso)
                    if ((methodValue === 'PUT' || methodValue === 'DELETE') && !formData.has('_method')) {
                        formData.append('_method', methodValue);
                    }

                    fetch(url, {
                        method: fetchMethod,
                        body: formData,
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    })
                    .then(response => {
                        if (!response.ok) {
                            return response.json().then(err => {
                                if (err.errors) {
                                    // Manejar errores de validación de Laravel
                                    const errorMessages = Object.values(err.errors).flat();
                                    showAlert(errorMessages.join(', '), 'error');
                                } else if (err.error) {
                                    showAlert(err.error, 'error');
                                } else {
                                    showAlert('Error al procesar la solicitud del inventario', 'error');
                                }
                                throw new Error(err.error || 'Error');
                            });
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            showAlert(data.success);
                            closeInventarioModal();
                            loadInventario();
                        } else if (data.error) {
                            showAlert(data.error, 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        // No mostrar error aquí si ya se mostró arriba
                    });
                });
            }
            
            // Formulario de movimientos
            const movimientoForm = document.getElementById('movimientoForm');
            if (movimientoForm) {
                movimientoForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const formData = new FormData(this);
                    const url = this.action;
                    const methodValue = document.getElementById('movimientoFormMethod').value;
                    // usar POST y spoofear si el método es PUT/DELETE (compatibilidad con multipart)
                    const fetchMethod = (methodValue === 'PUT' || methodValue === 'DELETE') ? 'POST' : methodValue;

                    // si no existe _method en formData, añadirlo (por si acaso)
                    if ((methodValue === 'PUT' || methodValue === 'DELETE') && !formData.has('_method')) {
                        formData.append('_method', methodValue);
                    }

                    fetch(url, {
                        method: fetchMethod,
                        body: formData,
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    })
                    .then(response => {
                        if (!response.ok) {
                            return response.json().then(err => {
                                if (err.errors) {
                                    // Manejar errores de validación de Laravel
                                    const errorMessages = Object.values(err.errors).flat();
                                    showAlert(errorMessages.join(', '), 'error');
                                } else if (err.error) {
                                    showAlert(err.error, 'error');
                                } else {
                                    showAlert('Error al procesar la solicitud del movimiento', 'error');
                                }
                                throw new Error(err.error || 'Error');
                            });
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            showAlert(data.success);
                            closeMovimientoModal();
                            loadMovimientos();
                        } else if (data.error) {
                            showAlert(data.error, 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        // No mostrar error aquí si ya se mostró arriba
                    });
                });
            }
            
            // Formulario de subservicios
            const subservicioForm = document.getElementById('subservicioForm');
            if (subservicioForm) {
                subservicioForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const formData = new FormData(this);
                    const url = this.action;
                    const methodValue = document.getElementById('subservicioFormMethod').value;
                    const fetchMethod = (methodValue === 'PUT' || methodValue === 'DELETE') ? 'POST' : methodValue;

                    // Asegurar que _method esté presente
                    if ((methodValue === 'PUT' || methodValue === 'DELETE') && !formData.has('_method')) {
                        formData.append('_method', methodValue);
                    }
                    
                    // Asegurar que el token CSRF esté presente
                    const csrfInput = this.querySelector('input[name="_token"]');
                    if (!formData.has('_token') && csrfInput) {
                        formData.append('_token', csrfInput.value);
                    }

                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ||
                                     csrfInput?.value ||
                                     '{{ csrf_token() }}';
                    
                    fetch(url, {
                        method: fetchMethod,
                        body: formData,
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => {
                        if (!response.ok) {
                            return response.json().then(err => {
                                if (err.errors) {
                                    const errorMessages = Object.values(err.errors).flat();
                                    showAlert(errorMessages.join(', '), 'error');
                                } else if (err.error) {
                                    showAlert(err.error, 'error');
                                } else {
                                    showAlert('Error al procesar la solicitud del subservicio', 'error');
                                }
                                throw new Error(err.error || 'Error');
                            });
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            showAlert(data.success);
                            closeSubservicioModal();
                            // Asegurar que la pestaña de subservicios esté activa
                            showTab('subservicios');
                            // Recargar solo los subservicios
                            loadSubservicios();
                        } else if (data.error) {
                            showAlert(data.error, 'error');
                        } else {
                            // Si no hay JSON, puede ser un redirect, recargar solo subservicios
                            showTab('subservicios');
                            loadSubservicios();
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        // Si hay un error de parseo, intentar recargar solo subservicios
                        showTab('subservicios');
                        loadSubservicios();
                    });
                });
            }
        });
        
        // Verificación de funciones de eliminación al cargar la página
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Verificando funciones de eliminación...');
            console.log('window.deleteSubservicio:', typeof window.deleteSubservicio);
            console.log('window.deleteInventario:', typeof window.deleteInventario);
            console.log('window.deleteMovimiento:', typeof window.deleteMovimiento);
            console.log('window.customConfirm:', typeof window.customConfirm);
            console.log('window.handleSubservicioDelete:', typeof window.handleSubservicioDelete);
            
            if (typeof window.deleteSubservicio !== 'function') {
                console.error('ERROR: window.deleteSubservicio no está definida');
            }
            if (typeof window.deleteInventario !== 'function') {
                console.error('ERROR: window.deleteInventario no está definida');
            }
            if (typeof window.deleteMovimiento !== 'function') {
                console.error('ERROR: window.deleteMovimiento no está definida');
            }
            if (typeof window.customConfirm !== 'function') {
                console.error('ERROR: window.customConfirm no está definida');
            }
        });
        
    </script>

    @if ($errors->any() && old('id'))
        <div id="service-old-data"
            data-id="{{ old('id') }}"
            data-nombre="{{ e(old('nombre_servicio', '')) }}"
            data-descripcion="{{ e(old('descripcion', '')) }}"
            data-icono="{{ e(old('icono', '')) }}"
            style="display: none;"></div>
    @endif
@endsection
