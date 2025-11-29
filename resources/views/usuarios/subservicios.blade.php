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
                <div class="alert success" data-alert>
                    <div class="alert-message">{{ session('success') }}</div>
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
                            <th>Imagen</th>
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
                                <td>
                                    @if($subServicio->imagen)
                                        <img src="{{ asset('storage/subservicios/' . $subServicio->imagen) }}" alt="{{ $subServicio->nombre }}" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">
                                    @else
                                        <span style="color: #999;">Sin imagen</span>
                                    @endif
                                </td>
                                <td>{{ $subServicio->nombre }}</td>
                                <td>{{ $subServicio->servicio->nombre_servicio ?? 'N/A' }}</td>
                                <td>{{ \Illuminate\Support\Str::limit($subServicio->descripcion ?? '', 50) }}</td>
                                <td>${{ number_format($subServicio->precio ?? 0, 0, ',', '.') }}</td>
                                <td class="actions-cell">
                                    <button onclick="openModal('edit', {{ $subServicio->id }}, '{{ addslashes($subServicio->nombre) }}', '{{ addslashes($subServicio->descripcion ?? '') }}', {{ $subServicio->precio ?? 0 }}, {{ $subServicio->servicios_id }}, '{{ $subServicio->imagen ? asset('storage/subservicios/' . $subServicio->imagen) : '' }}')" class="btn-action edit">
                                        <i class="fas fa-edit"></i> Editar
                                    </button>
                                    <form action="{{ route('subservicios.destroy', $subServicio->id) }}" method="POST" style="display:inline;" onsubmit="event.preventDefault(); customConfirm('¿Estás seguro de que deseas eliminar este subservicio?').then(result => { if(result) this.submit(); }); return false;">
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
                                <td colspan="7" class="no-services">No hay subservicios registrados.</td>
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
                        <button type="button" class="close-btn" onclick="closeModal()" aria-label="Cerrar modal">&times;</button>
                    </div>
                    <form id="subservicioForm" method="POST" enctype="multipart/form-data">
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
                        
                        <div class="form-group">
                            <label for="imagen">Imagen:</label>
                            <input type="file" id="imagen" name="imagen" accept="image/jpeg,image/png,image/jpg,image/gif">
                            <small style="display: block; margin-top: 5px; color: #666;">Formatos permitidos: JPEG, PNG, JPG, GIF. Tamaño máximo: 5MB</small>
                            <div id="imagen-preview" style="margin-top: 10px; display: none;">
                                <img id="imagen-preview-img" src="" alt="Vista previa" style="max-width: 200px; max-height: 200px; border-radius: 4px;">
                            </div>
                            <div id="imagen-actual" style="margin-top: 10px; display: none;">
                                <p style="margin: 5px 0; color: #666;">Imagen actual:</p>
                                <img id="imagen-actual-img" src="" alt="Actual" style="max-width: 200px; max-height: 200px; border-radius: 4px;">
                            </div>
                        </div>
                        
                        <button type="submit" class="btn-submit">Guardar</button>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script>
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
        });

        function openModal(action, id = null, nombre = '', descripcion = '', precio = 0, servicios_id = '', imagenUrl = '') {
            const modal = document.getElementById('subservicioModal');
            const form = document.getElementById('subservicioForm');
            const formMethod = document.getElementById('formMethod');
            const modalTitle = document.getElementById('modalTitle');
            const subservicioId = document.getElementById('subservicio_id');
            const imagenInput = document.getElementById('imagen');
            const imagenPreview = document.getElementById('imagen-preview');
            const imagenPreviewImg = document.getElementById('imagen-preview-img');
            const imagenActual = document.getElementById('imagen-actual');
            const imagenActualImg = document.getElementById('imagen-actual-img');
            
            if (action === 'create') {
                modalTitle.textContent = 'Agregar Nuevo Subservicio';
                formMethod.value = 'POST';
                form.action = '{{ route('subservicios.store') }}';
                form.reset();
                document.getElementById('servicios_id').value = '';
                subservicioId.value = '';
                imagenPreview.style.display = 'none';
                imagenActual.style.display = 'none';
            } else if (action === 'edit') {
                modalTitle.textContent = 'Editar Subservicio';
                formMethod.value = 'PUT';
                form.action = `{{ url('subservicios') }}/${id}`;
                document.getElementById('nombre').value = nombre;
                document.getElementById('descripcion').value = descripcion;
                document.getElementById('precio').value = precio;
                document.getElementById('servicios_id').value = servicios_id;
                subservicioId.value = id;
                
                // Mostrar imagen actual si existe
                if (imagenUrl) {
                    imagenActualImg.src = imagenUrl;
                    imagenActual.style.display = 'block';
                } else {
                    imagenActual.style.display = 'none';
                }
                imagenPreview.style.display = 'none';
            }
            
            modal.style.display = 'block';
        }
        
        // Vista previa de imagen al seleccionar archivo
        document.getElementById('imagen').addEventListener('change', function(e) {
            const file = e.target.files[0];
            const preview = document.getElementById('imagen-preview');
            const previewImg = document.getElementById('imagen-preview-img');
            const imagenActual = document.getElementById('imagen-actual');
            
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

