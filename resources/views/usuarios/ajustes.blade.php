@extends('layouts.app')

@section('title', 'Ajustes de Servicios')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/ajustes.css') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
        {{-- Contenido principal --}}
        <main class="main-content">
            <h2 class="page-title">Ajustes de Servicios</h2>
            <p class="page-subtitle">Administra los servicios de la plataforma. Puedes crear, editar y eliminar.</p>

            {{-- Mensajes de estado (éxito o error) --}}
            @if(session('success'))
                <div class="alert success">{{ session('success') }}</div>
            @endif
            @if(session('warning'))
                <div class="alert warning" style="background-color: #ffa500; color: #fff;">{{ session('warning') }}</div>
            @endif
            @if(session('error'))
                <div class="alert error">{{ session('error') }}</div>
            @endif
            @if($errors->any())
                <div class="alert error">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
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

            <div id="page-meta" data-initial-tab="{{ $activeTab ?? 'servicios' }}" style="display:none;"></div>

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
                                    <td class="actions-cell">
                                        <button onclick="openModal('edit', {{ $servicio->id }}, '{{ addslashes($servicio->nombre_servicio) }}', '{{ addslashes($servicio->descripcion ?? '') }}')" class="btn-action edit">
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
                                    <td colspan="3" class="no-services">No hay servicios registrados.</td>
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
                                    <td>{{ $sub->nombre }}</td>
                                    <td>{{ $sub->servicio->nombre_servicio ?? 'N/A' }}</td>
                                    <td>{{ \Illuminate\Support\Str::limit($sub->descripcion ?? '', 50) }}</td>
                                    <td>${{ number_format($sub->precio ?? 0, 0, ',', '.') }}</td>
                                    <td class="actions-cell">
                                        <button onclick="openSubservicioModal('edit', {{ $sub->id }}, '{{ addslashes($sub->nombre) }}', '{{ addslashes($sub->descripcion ?? '') }}', {{ $sub->precio ?? 0 }}, {{ $sub->servicios_id }})" class="btn-action edit">
                                            <i class="fas fa-edit"></i> Editar
                                        </button>
                                        <button onclick="deleteSubservicio({{ $sub->id }})" class="btn-action delete">
                                            <i class="fas fa-trash-alt"></i> Eliminar
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="no-services">No hay subservicios registrados.</td>
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
                        <h3 id="modalTitle"></h3>
                        <span class="close-btn" onclick="closeModal()">&times;</span>
                    </div>
                    <form id="serviceForm" method="POST">
                        @csrf
                        <input type="hidden" name="_method" id="formMethod">
                        <div class="form-group">
                            <label for="nombre_servicio">Nombre del Servicio:</label>
                            <input type="text" id="nombre_servicio" name="nombre_servicio" required>
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
                        <h3 id="inventarioModalTitle"></h3>
                        <span class="close-btn" onclick="closeInventarioModal()">&times;</span>
                    </div>
                    <form id="inventarioForm" method="POST">
                        @csrf
                        <input type="hidden" name="_method" id="inventarioFormMethod">
                        <div class="form-group">
                            <label for="descripcion">Descripción:</label>
                            <input type="text" id="descripcion" name="descripcion" required>
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
                        <h3 id="movimientoModalTitle"></h3>
                        <span class="close-btn" onclick="closeMovimientoModal()">&times;</span>
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
                        <h3 id="subservicioModalTitle"></h3>
                        <span class="close-btn" onclick="closeSubservicioModal()">&times;</span>
                    </div>
                    <form id="subservicioForm" method="POST">
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
            const metaEl = document.getElementById('page-meta');
            const initialTab = metaEl ? metaEl.getAttribute('data-initial-tab') : 'servicios';
            showTab(initialTab);
        });

        // Funciones para servicios (existentes)
        const modalTitle = document.getElementById('modalTitle');
        const serviceForm = document.getElementById('serviceForm');
        const formMethod = document.getElementById('formMethod');
        const nombreInput = document.getElementById('nombre_servicio');
        const descripcionInput = document.getElementById('descripcion');

        function openModal(mode, id = null, nombre = '', descripcion = '') {
            if (mode === 'create') {
                modalTitle.textContent = 'Crear Nuevo Servicio';
                serviceForm.action = "{{ route('servicios.store') }}";
                formMethod.name = '_method';
                formMethod.value = 'POST';
                nombreInput.value = '';
                descripcionInput.value = '';
            } else if (mode === 'edit') {
                modalTitle.textContent = 'Editar Servicio';
                serviceForm.action = `{{ url('servicios') }}/${id}`;
                formMethod.name = '_method';
                formMethod.value = 'PUT';
                nombreInput.value = nombre;
                descripcionInput.value = descripcion || '';
            }
            serviceModal.style.display = 'flex';
        }

        function closeModal() {
            serviceModal.style.display = 'none';
        }

        // Funciones para subservicios
        function openSubservicioModal(mode, id = null, nombre = '', descripcion = '', precio = 0, servicios_id = '') {
            const subservicioModalTitle = document.getElementById('subservicioModalTitle');
            const subservicioForm = document.getElementById('subservicioForm');
            const subservicioFormMethod = document.getElementById('subservicioFormMethod');
            
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
            } else if (mode === 'edit') {
                subservicioModalTitle.textContent = 'Editar Subservicio';
                subservicioForm.action = `{{ url('subservicios') }}/${id}`;
                subservicioFormMethod.name = '_method';
                subservicioFormMethod.value = 'PUT';
                document.getElementById('nombre_subservicio').value = nombre;
                document.getElementById('descripcion_subservicio').value = descripcion;
                document.getElementById('precio_subservicio').value = precio;
                document.getElementById('servicios_id_subservicio').value = servicios_id;
            }
            subservicioModal.style.display = 'flex';
        }

        function closeSubservicioModal() {
            subservicioModal.style.display = 'none';
        }

        // Función para cargar los subservicios dinámicamente
        function loadSubservicios() {
            fetch('{{ route('usuarios.ajustes.subservicios') }}', {
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
                    tbody.innerHTML = '<tr><td colspan="6" class="no-services">No hay subservicios registrados.</td></tr>';
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
                    
                    const escapeJs = (str) => {
                        if (!str) return '';
                        return String(str)
                            .replace(/\\/g, '\\\\')
                            .replace(/'/g, "\\'")
                            .replace(/"/g, '\\"')
                            .replace(/\n/g, '\\n')
                            .replace(/\r/g, '\\r');
                    };
                    
                    const nombre = escapeJs(sub.nombre || '');
                    const descripcion = escapeJs(sub.descripcion || '');
                    const servicioNombre = sub.servicio ? escapeHtml(sub.servicio.nombre_servicio || 'N/A') : 'N/A';
                    const nombreHtml = escapeHtml(sub.nombre || '');
                    const descripcionHtml = sub.descripcion ? (sub.descripcion.length > 50 ? escapeHtml(sub.descripcion.substring(0, 50)) + '...' : escapeHtml(sub.descripcion)) : '';
                    const precio = new Intl.NumberFormat('es-CO', { minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(sub.precio || 0);
                    
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${sub.id}</td>
                        <td>${nombreHtml}</td>
                        <td>${servicioNombre}</td>
                        <td>${descripcionHtml}</td>
                        <td>$${precio}</td>
                        <td class="actions-cell">
                            <button onclick="openSubservicioModal('edit', ${sub.id}, '${nombre}', '${descripcion}', ${sub.precio || 0}, ${sub.servicios_id})" class="btn-action edit">
                                <i class="fas fa-edit"></i> Editar
                            </button>
                            <button onclick="deleteSubservicio(${sub.id})" class="btn-action delete">
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

        async function deleteSubservicio(id) {
            const confirmed = await customConfirm('¿Estás seguro de que deseas eliminar este subservicio?');
            if (confirmed) {
                const url = `{{ url('subservicios') }}/${id}`;
                const formData = new FormData();
                formData.append('_method', 'DELETE');

                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || 
                                 document.querySelector('input[name="_token"]')?.value || 
                                 '{{ csrf_token() }}';

                fetch(url, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => {
                    const ct = response.headers.get('content-type') || '';
                    if (!response.ok) {
                        if (ct.includes('application/json')) return response.json().then(err => Promise.reject(err));
                        return response.text().then(text => Promise.reject({ message: text }));
                    }
                    return ct.includes('application/json') ? response.json() : {};
                })
                .then(data => {
                    if (data.success) {
                        showAlert(data.success);
                        // Asegurar que la pestaña de subservicios esté activa
                        showTab('subservicios');
                        // Recargar solo los subservicios
                        loadSubservicios();
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    const errorMsg = error.error || error.message || 'Error al eliminar el subservicio';
                    showAlert(errorMsg, 'error');
                });
            }
        }
        function openInventarioModal(mode, id = null, descripcion = '', stock = '') {
            const inventarioModalTitle = document.getElementById('inventarioModalTitle');
            const inventarioForm = document.getElementById('inventarioForm');
            const inventarioFormMethod = document.getElementById('inventarioFormMethod');
            
            if (mode === 'create') {
                inventarioModalTitle.textContent = 'Crear Nuevo Item';
                inventarioForm.action = "{{ route('inventario.store') }}";
                inventarioFormMethod.name = '_method';
                inventarioFormMethod.value = 'POST';
                document.getElementById('descripcion').value = '';
                document.getElementById('stock').value = '';
            } else if (mode === 'edit') {
                inventarioModalTitle.textContent = 'Editar Item';
                inventarioForm.action = `{{ url('inventario') }}/${id}`;
                inventarioFormMethod.name = '_method';
                inventarioFormMethod.value = 'PUT';
                document.getElementById('descripcion').value = descripcion;
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
                                    <button onclick="deleteInventario(${item.id})" class="btn-action delete">
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
                    
                    data.forEach(movimiento => {
                        const fecha = new Date(movimiento.fecha_movimiento).toLocaleDateString();
                        const row = `
                            <tr>
                                <td>${movimiento.id}</td>
                                <td>${movimiento.inventario ? movimiento.inventario.descripcion : 'N/A'}</td>
                                <td>${movimiento.tipo_movimiento}</td>
                                <td>${movimiento.cantidad}</td>
                                <td>${fecha}</td>
                                <td class="actions-cell">
                                    <button onclick="openMovimientoModal('edit', ${movimiento.id}, '${movimiento.inventario_id}', '${movimiento.tipo_movimiento}', '${movimiento.cantidad}')" class="btn-action edit">
                                        <i class="fas fa-edit"></i> Editar
                                    </button>
                                    <button onclick="deleteMovimiento(${movimiento.id})" class="btn-action delete">
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
        async function deleteInventario(id) {
            const confirmed = await customConfirm('¿Estás seguro de que deseas eliminar este item?');
            if (confirmed) {
                const url = `{{ url('inventario') }}/${id}`;
                const formData = new FormData();
                formData.append('_method', 'DELETE');

                fetch(url, {
                    method: 'POST', // usar POST y spoofear _method para compatibilidad con form-data
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    }
                })
                .then(response => {
                    const ct = response.headers.get('content-type') || '';
                    if (!response.ok) {
                        if (ct.includes('application/json')) return response.json().then(err => Promise.reject(err));
                        return response.text().then(text => Promise.reject({ message: text }));
                    }
                    return ct.includes('application/json') ? response.json() : {};
                })
                .then(data => {
                    if (data.success) {
                        showAlert(data.success);
                        loadInventario();
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    const errorMsg = error.error || error.message || 'Error al eliminar el artículo del inventario';
                    showAlert(errorMsg, 'error');
                });
            }
        }

        async function deleteMovimiento(id) {
            const confirmed = await customConfirm('¿Estás seguro de que deseas eliminar este movimiento?');
            if (confirmed) {
                const url = `{{ url('movimientos') }}/${id}`;
                const formData = new FormData();
                formData.append('_method', 'DELETE');

                fetch(url, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    }
                })
                .then(response => {
                    const ct = response.headers.get('content-type') || '';
                    if (!response.ok) {
                        if (ct.includes('application/json')) return response.json().then(err => Promise.reject(err));
                        return response.text().then(text => Promise.reject({ message: text }));
                    }
                    return ct.includes('application/json') ? response.json() : {};
                })
                .then(data => {
                    if (data.success) {
                        showAlert(data.success);
                        loadMovimientos();
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    const errorMsg = error.error || error.message || 'Error al eliminar el movimiento';
                    showAlert(errorMsg, 'error');
                });
            }
        }
        
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
        
    </script>

    @if ($errors->any() && old('id'))
        <script>
            openModal('edit', {{ old('id') }}, '{{ old('nombre_servicio') }}');
        </script>
    @endif
@endsection
