<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>PRO AUDIO - Gestión de Subservicios</title>
    
    {{-- Llamada al archivo CSS principal usando Vite --}}
    @vite('resources/css/app.css')

    {{-- Enlace a Font Awesome para los íconos --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    
    {{-- Enlace al archivo de estilos para los ajustes --}}
    <link rel="stylesheet" href="{{ asset('css/ajustes.css') }}">
</head>
<body>
    <div class="dashboard-container">
        @include('components.topbar')
        
        {{-- Barra lateral izquierda --}}
        @include('components.sidebar')
        
        {{-- Contenido principal --}}
        <main class="main-content">
            <h2 class="page-title">Gestión de Subservicios</h2>
            <p class="page-subtitle">Administra los subservicios de cada servicio. Puedes crear, editar y eliminar.</p>

            {{-- Mensajes de estado (éxito o error) --}}
            @if(session('success'))
                <div class="alert success">{{ session('success') }}</div>
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

            {{-- Botón para abrir el modal de creación --}}
            <div class="button-container">
                <button onclick="openModal('create')" class="btn-primary">
                    <i class="fas fa-plus-circle"></i> Agregar Nuevo Subservicio
                </button>
            </div>

            {{-- Tabla de subservicios --}}
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
                    <tbody>
                        @php
                            $subServiciosList = isset($subServicios) ? $subServicios : collect([]);
                        @endphp
                        @forelse ($subServiciosList as $subServicio)
                            <tr>
                                <td>{{ $subServicio->id }}</td>
                                <td>{{ $subServicio->nombre }}</td>
                                <td>{{ $subServicio->servicio->nombre_servicio ?? 'N/A' }}</td>
                                <td>{{ \Illuminate\Support\Str::limit($subServicio->descripcion ?? '', 50) }}</td>
                                <td>${{ number_format($subServicio->precio ?? 0, 0, ',', '.') }}</td>
                                <td class="actions-cell">
                                    <button onclick="openModal('edit', {{ $subServicio->id }}, '{{ addslashes($subServicio->nombre) }}', '{{ addslashes($subServicio->descripcion ?? '') }}', {{ $subServicio->precio ?? 0 }}, {{ $subServicio->servicios_id }})" class="btn-action edit">
                                        <i class="fas fa-edit"></i> Editar
                                    </button>
                                    <form action="{{ route('subservicios.destroy', $subServicio->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('¿Estás seguro de que deseas eliminar este subservicio?');">
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
                                <td colspan="6" class="no-services">No hay subservicios registrados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Modal para crear y editar subservicios --}}
            <div id="subservicioModal" class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3 id="modalTitle">Agregar Nuevo Subservicio</h3>
                        <span class="close-btn" onclick="closeModal()">&times;</span>
                    </div>
                    <form id="subservicioForm" method="POST">
                        @csrf
                        <input type="hidden" name="_method" id="formMethod" value="POST">
                        <input type="hidden" name="subservicio_id" id="subservicio_id">
                        
                        <div class="form-group">
                            <label for="servicios_id">Servicio:</label>
                            <select id="servicios_id" name="servicios_id" required>
                                <option value="">Seleccione un servicio</option>
                                @foreach($servicios ?? [] as $servicio)
                                    <option value="{{ $servicio->id }}">{{ $servicio->nombre_servicio }}</option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="nombre">Nombre del Subservicio:</label>
                            <input type="text" id="nombre" name="nombre" required maxlength="100">
                        </div>
                        
                        <div class="form-group">
                            <label for="descripcion">Descripción:</label>
                            <textarea id="descripcion" name="descripcion" rows="4" placeholder="Descripción del subservicio (opcional)"></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="precio">Precio:</label>
                            <input type="number" id="precio" name="precio" step="0.01" min="0" required>
                        </div>
                        
                        <button type="submit" class="btn-submit">Guardar</button>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script>
        function openModal(action, id = null, nombre = '', descripcion = '', precio = 0, servicios_id = '') {
            const modal = document.getElementById('subservicioModal');
            const form = document.getElementById('subservicioForm');
            const formMethod = document.getElementById('formMethod');
            const modalTitle = document.getElementById('modalTitle');
            const subservicioId = document.getElementById('subservicio_id');
            
            if (action === 'create') {
                modalTitle.textContent = 'Agregar Nuevo Subservicio';
                formMethod.value = 'POST';
                form.action = '{{ route('subservicios.store') }}';
                form.reset();
                document.getElementById('servicios_id').value = '';
                subservicioId.value = '';
            } else if (action === 'edit') {
                modalTitle.textContent = 'Editar Subservicio';
                formMethod.value = 'PUT';
                form.action = `{{ url('subservicios') }}/${id}`;
                document.getElementById('nombre').value = nombre;
                document.getElementById('descripcion').value = descripcion;
                document.getElementById('precio').value = precio;
                document.getElementById('servicios_id').value = servicios_id;
                subservicioId.value = id;
            }
            
            modal.style.display = 'block';
        }

        function closeModal() {
            document.getElementById('subservicioModal').style.display = 'none';
        }

        // Cerrar modal al hacer clic fuera de él
        window.onclick = function(event) {
            const modal = document.getElementById('subservicioModal');
            if (event.target === modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>

