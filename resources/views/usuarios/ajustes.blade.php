<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>PRO AUDIO - Ajustes de Servicios</title>
    
    {{-- Llamada al archivo CSS principal usando Vite --}}
    @vite('resources/css/app.css')

    {{-- Enlace a Font Awesome para los íconos --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" xintegrity="sha512-SnH5WK+bZxgPHs44uWpU6lJ9Xl3QO4K8y9Rk5vLqB34+Jk81f7qFk43Qk5p8G4eGk3k9Vb/qH6r/jB5sD5k6w==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    
    {{-- Enlace al nuevo archivo de estilos para los ajustes --}}
    <link rel="stylesheet" href="{{ asset('css/ajustes.css') }}">
</head>
<body>
    <div class="dashboard-container">
        {{-- Barra superior --}}
        <header class="top-bar">
            <h1>PRO AUDIO</h1>
            <form class="search-form" action="#" method="GET">
                <input type="text" name="buscar" class="search-input" placeholder="Buscar...">
                <button type="submit" class="search-btn">
                    <i class="fas fa-search"></i>
                </button>
            </form>
        </header>

        {{-- Barra lateral izquierda --}}
        <aside class="sidebar">
            <h5 class="menu-title">Menú</h5>
            <a href="{{ route('usuarios.perfil') }}" class="sidebar-btn"><i class="fas fa-user-circle"></i> Perfil</a>
            <a href="{{ route('usuarios.dashboard') }}" class="sidebar-btn"><i class="fas fa-home"></i> Inicio</a>
            <a href="{{ route('usuarios.animacion') }}" class="sidebar-btn"><i class="fas fa-laugh-beam"></i> Animación</a>
            <a href="{{ route('usuarios.publicidad') }}" class="sidebar-btn"><i class="fas fa-bullhorn"></i> Publicidad</a>
            <a href="{{ route('usuarios.alquiler') }}" class="sidebar-btn"><i class="fas fa-box"></i> Alquiler</a>
            <a href="{{ route('usuarios.calendario') }}" class="sidebar-btn"><i class="fas fa-calendar-alt"></i> Calendario</a>
            <a href="{{ route('usuarios.ajustes') }}" class="sidebar-btn"><i class="fas fa-cog"></i> Ajustes</a>
            <a href="{{ route('usuarios.chatbot') }}" class="sidebar-btn"><i class="fas fa-robot"></i> Chatbot</a>
        </aside>

        {{-- Contenido principal --}}
        <main class="main-content">
            <h2 class="page-title">Ajustes de Servicios</h2>
            <p class="page-subtitle">Administra los servicios de la plataforma. Puedes crear, editar y eliminar.</p>

            {{-- Mensajes de estado (éxito o error) --}}
            @if(session('success'))
                <div class="alert success">{{ session('success') }}</div>
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
                <button onclick="showTab('inventario')" class="btn-tab" id="btn-inventario">
                    <i class="fas fa-boxes"></i> Inventario
                </button>
                <button onclick="showTab('movimientos')" class="btn-tab" id="btn-movimientos">
                    <i class="fas fa-exchange-alt"></i> Movimientos
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
                            @forelse ($servicios as $servicio)
                                <tr>
                                    <td>{{ $loop->iteration }}</td> {{-- Número consecutivo limpio --}}
                                    <td>{{ $servicio->nombre_servicio }}</td>
                                    <td class="actions-cell">
                                        <button onclick="openModal('edit', {{ $servicio->id }}, '{{ addslashes($servicio->nombre_servicio) }}')" class="btn-action edit">
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
    </div>

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

        function openModal(mode, id = null, nombre = '') {
            if (mode === 'create') {
                modalTitle.textContent = 'Crear Nuevo Servicio';
                serviceForm.action = "{{ route('servicios.store') }}";
                formMethod.name = '_method';
                formMethod.value = 'POST';
                nombreInput.value = '';
            } else if (mode === 'edit') {
                modalTitle.textContent = 'Editar Servicio';
                serviceForm.action = `{{ url('servicios') }}/${id}`;
                formMethod.name = '_method';
                formMethod.value = 'PUT';
                nombreInput.value = nombre;
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
                fetch(`{{ url('inventario') }}/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert(data.success);
                        loadInventario();
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('Error al eliminar el item', 'error');
                });
            }
        }

        function deleteMovimiento(id) {
            if (confirm('¿Estás seguro de que deseas eliminar este movimiento?')) {
                fetch(`{{ url('movimientos') }}/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                })
                .then(response => response.json())
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
                    const method = document.getElementById('inventarioFormMethod').value;
                    
                    fetch(url, {
                        method: method,
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
                    const method = document.getElementById('movimientoFormMethod').value;
                    
                    fetch(url, {
                        method: method,
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
</body>
</html>
