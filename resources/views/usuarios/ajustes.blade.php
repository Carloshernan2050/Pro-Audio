<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
                <button type="submit" class="search-btn">🔍</button>
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
                                <td>{{ $servicio->id }}</td>
                                <td>{{ $servicio->nombre_servicio }}</td>
                                <td class="actions-cell">
                                    <button onclick="openModal('edit', {{ $servicio->id }}, '{{ $servicio->nombre_servicio }}')" class="btn-action edit">
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

        </main>
    </div>

    <script>
        const serviceModal = document.getElementById('serviceModal');
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
        
        // Cierra el modal si se hace clic fuera de él
        window.onclick = function(event) {
            if (event.target == serviceModal) {
                closeModal();
            }
        }
        
        // Verifica si hay errores de validación para mostrar el modal de edición
        @if ($errors->any() && old('id'))
            // Nota: Laravel no incluye `id` en `old()`. Necesitarás pasar el `id` a la vista
            // de alguna manera si el formulario falla, por ejemplo, en la redirección.
            // Para este ejemplo, asumiremos que si hay errores, queremos mostrar el modal.
            // Una implementación robusta requeriría enviar el id del servicio editado de vuelta.
            openModal('edit', '{{ old('id') }}', '{{ old('nombre_servicio') }}');
        @endif
    </script>
</body>
</html>
