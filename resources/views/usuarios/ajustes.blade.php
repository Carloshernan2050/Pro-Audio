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
                <a href="{{ route('subservicios.index') }}" class="btn-tab" style="text-decoration: none; display: inline-block;">
                    <i class="fas fa-list-alt"></i> Subservicios
                </a>
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
                                        <form action="{{ route('servicios.destroy', $servicio->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('¿Estás seguro de que deseas eliminar este servicio?');">
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
                            @php
                                $cotizacionesList = isset($cotizaciones) ? $cotizaciones : collect([]);
                            @endphp
                            @forelse ($cotizacionesList as $cotizacion)
                                <tr>
                                    <td>{{ $cotizacion->id }}</td>
                                    <td>
                                        {{ $cotizacion->fecha_cotizacion ? $cotizacion->fecha_cotizacion->format('d/m/Y H:i:s') : 'N/A' }}
                                    </td>
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
                                    <td>
                                        @if($cotizacion->subServicio && $cotizacion->subServicio->servicio)
                                            {{ $cotizacion->subServicio->servicio->nombre_servicio }}
                                        @else
                                            N/A
                                        @endif
                                    </td>
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
                            <textarea id="descripcion" name="descripcion" rows="3" placeholder="Descripción del servicio (opcional)"></textarea>
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

        </main>

    <script>
        // Variables globales
        const serviceModal = document.getElementById('serviceModal');
        const inventarioModal = document.getElementById('inventarioModal');
        const movimientoModal = document.getElementById('movimientoModal');

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
            }
        }

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

        // Funciones para inventario
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

        // Función para mostrar alertas
        function showAlert(message, type = 'success') {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert ${type}`;
            alertDiv.textContent = message;
            alertDiv.style.position = 'fixed';
            alertDiv.style.top = '20px';
            alertDiv.style.right = '20px';
            alertDiv.style.zIndex = '9999';
            alertDiv.style.padding = '15px 20px';
            alertDiv.style.borderRadius = '5px';
            alertDiv.style.color = 'white';
            alertDiv.style.fontWeight = 'bold';
            
            if (type === 'success') {
                alertDiv.style.backgroundColor = '#4CAF50';
            } else {
                alertDiv.style.backgroundColor = '#f44336';
            }
            
            document.body.appendChild(alertDiv);
            
            setTimeout(() => {
                alertDiv.remove();
            }, 3000);
        }

        // Funciones de eliminación
        function deleteInventario(id) {
            if (confirm('¿Estás seguro de que deseas eliminar este item?')) {
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
                    showAlert('Error al eliminar el elemento', 'error');
                });
            }
        }

        function deleteMovimiento(id) {
            if (confirm('¿Estás seguro de que deseas eliminar este movimiento?')) {
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
                    showAlert('Error al eliminar el movimiento', 'error');
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
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showAlert(data.success);
                            closeInventarioModal();
                            loadInventario();
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showAlert('Error al procesar la solicitud', 'error');
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
                    .then(response => response.json())
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
                        showAlert('Error al procesar la solicitud', 'error');
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
