<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    {{-- Permite que cada vista hija defina su propio título --}}
    <title>PRO AUDIO - @yield('title', 'Inicio')</title> 
    
    {{-- Llamada al archivo CSS principal usando Vite --}}
    @vite('resources/css/app.css')
    {{-- CSS específico del layout (topbar, sidebar, fondo, estructura) --}}
    @vite('resources/css/layout.css')

    {{-- Stack opcional para estilos por-vista --}}
    @stack('styles')

    {{-- Enlace a la librería de Font Awesome para los íconos --}}
    {{-- Se eliminó el atributo integrity/xintegrity para asegurar la carga de los iconos --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body>
    {{-- Máscara fija para cubrir el espacio superior (0.5cm) y evitar que se vea contenido al hacer scroll --}}
    <div class="top-gap-mask" aria-hidden="true"></div>
    {{-- Contenedor principal del dashboard con la imagen de fondo --}}
    <div class="dashboard-container">
        
        {{-- Barra superior reutilizable --}}
        @include('components.topbar')

        {{-- Overlay para cerrar el menú móvil --}}
        <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleMobileMenu()"></div>

        {{-- Barra lateral izquierda (Sidebar) --}}
        @include('components.sidebar')

        {{-- Contenido principal --}}
        <main class="main-content">
            {{-- Aquí es donde se insertará el contenido único de cada vista hija --}}
            @yield('content') 
        </main>
    </div>
    {{-- Botón flotante global para abrir el Chatbot --}}
    <a href="{{ route('usuarios.chatbot') }}" class="chatbot-fab" title="Abrir chatbot" aria-label="Abrir chatbot">
        <i class="fas fa-robot"></i>
    </a>
    {{-- Ventana flotante del Chatbot (mini panel) --}}
    <div id="chatbot-widget" class="chatbot-widget" aria-hidden="true">
        <div class="chatbot-widget__drag" id="chatbot-drag"></div>
        <div class="chatbot-widget__controls">
            <button type="button" class="chatbot-ctrl" id="chatbot-expand" title="Expandir / Reducir" aria-label="Expandir o reducir">
                <i class="fas fa-up-right-and-down-left-from-center"></i>
            </button>
            <button type="button" class="chatbot-ctrl" id="chatbot-hide" title="Ocultar" aria-label="Ocultar">
                <i class="fas fa-xmark"></i>
            </button>
        </div>
        <iframe src="{{ route('usuarios.chatbot') }}" title="Chatbot" loading="lazy" class="chatbot-iframe"></iframe>
    </div>
    {{-- Modal de Perfil Compacto --}}
    <div id="profileModal" class="profile-modal" style="display:none;">
        <div class="profile-modal-content">
            <div class="profile-modal-header">
                <span class="profile-modal-title">PRO-AUDIO</span>
                <button onclick="closeProfileModal()" class="profile-modal-close" style="background:none; border:none; color:#666; font-size:18px; cursor:pointer;">&times;</button>
            </div>
            <div class="profile-modal-body">
                @php
                    $usuarioId = session('usuario_id');
                    $usuario = $usuarioId ? \App\Models\Usuario::find($usuarioId) : null;
                    $roles = (array)session('roles');
                    $isInvitado = in_array('Invitado', $roles) || !session()->has('usuario_id');
                    $fotoPerfil = null;
                    if ($usuario && $usuario->foto_perfil) {
                        $path = storage_path('app/public/perfiles/' . $usuario->foto_perfil);
                        if (file_exists($path)) {
                            $fotoPerfil = asset('storage/perfiles/' . $usuario->foto_perfil);
                        }
                    }
                    $iniciales = $usuario ? strtoupper(substr($usuario->primer_nombre ?? 'U', 0, 1) . substr($usuario->primer_apellido ?? 'S', 0, 1)) : null;
                @endphp
                
                <div class="profile-info-section">
                    <div class="profile-avatar-container">
                        @if($fotoPerfil)
                            <img src="{{ $fotoPerfil }}" alt="Foto de perfil" class="profile-avatar-img" id="profileAvatarImg" onclick="openImageEnlargementModal('{{ $fotoPerfil }}')" style="cursor:pointer; transition:transform 0.2s;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                        @elseif($usuario && $iniciales)
                            <div class="profile-avatar-placeholder" id="profileAvatarPlaceholder" style="background:linear-gradient(135deg, #e91c1c 0%, #c81a1a 100%); cursor:default;">
                                {{ $iniciales }}
                            </div>
                        @else
                            <div class="profile-avatar-placeholder" id="profileAvatarPlaceholder" style="background:#000000; cursor:default; font-size:24px;">
                                <i class="fas fa-user"></i>
                            </div>
                        @endif
                        @if(!$isInvitado)
                            <label for="profilePhotoInput" class="profile-photo-upload-btn" title="Cambiar foto">
                                <i class="fas fa-camera"></i>
                            </label>
                            <input type="file" id="profilePhotoInput" accept="image/*" style="display:none;" onchange="uploadProfilePhoto(this)">
                        @endif
                    </div>
                    
                    <div class="profile-user-info">
                        <h3 class="profile-user-name">{{ $usuario ? ($usuario->primer_nombre . ' ' . $usuario->primer_apellido) : 'Usuario' }}</h3>
                        <p class="profile-user-email">{{ $usuario ? ($usuario->correo ?? 'No disponible') : 'No disponible' }}</p>
                    </div>
                    
                    <a href="{{ route('usuarios.perfil') }}" class="profile-link-item" style="text-decoration:none; color:inherit;">
                        <span>Consultar cuenta</span>
                        <i class="fas fa-external-link-alt"></i>
                    </a>
                </div>
                
                <div class="profile-footer">
                    @if(session()->has('usuario_id'))
                        <form action="{{ route('usuarios.cerrarSesion') }}" method="POST" style="margin:0; width:100%;">
                            @csrf
                            <button type="submit" class="profile-logout-btn" style="width:100%; padding:12px; background:#e91c1c; color:white; border:none; border-radius:4px; cursor:pointer; font-size:14px; font-weight:500;">
                                Cerrar sesión
                            </button>
                        </form>
                    @else
                        <div style="display:flex; gap:8px; width:100%;">
                            <a href="{{ route('usuarios.inicioSesion') }}" class="profile-logout-btn" style="flex:1; padding:12px; background:#e91c1c; color:white; border:none; border-radius:4px; cursor:pointer; font-size:14px; font-weight:500; text-align:center; text-decoration:none;">Iniciar sesión</a>
                            <a href="{{ route('usuarios.registroUsuario') }}" class="profile-logout-btn" style="flex:1; padding:12px; background:#e91c1c; color:white; border:none; border-radius:4px; cursor:pointer; font-size:14px; font-weight:500; text-align:center; text-decoration:none;">Registrarse</a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <style>
        .profile-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 10000;
            display: flex;
            align-items: flex-start;
            justify-content: flex-end;
            padding: 70px 20px 20px;
            box-sizing: border-box;
        }
        
        .profile-modal-content {
            background: #000000;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 380px;
            max-height: calc(100vh - 90px);
            overflow-y: auto;
            display: flex;
            flex-direction: column;
        }
        
        .profile-modal-header {
            padding: 20px 24px;
            border-bottom: 2px solid #e91c1c;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #000000;
        }
        
        .profile-modal-title {
            font-size: 28px;
            font-weight: 700;
            color: #e91c1c;
            font-family: 'Segoe UI', 'Arial', sans-serif;
            letter-spacing: 2px;
            text-transform: uppercase;
            text-shadow: 1px 1px 2px rgba(233, 28, 28, 0.2);
        }
        
        .profile-modal-close {
            font-size: 24px;
            color: #ffffff;
            cursor: pointer;
            background: none;
            border: none;
            padding: 0;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: color 0.2s;
        }
        
        .profile-modal-close:hover {
            color: #e91c1c;
        }
        
        .profile-modal-body {
            padding: 0;
            display: flex;
            flex-direction: column;
        }
        
        .profile-info-section {
            padding: 24px 20px;
            background: #000000;
            color: #ffffff;
        }
        
        .profile-avatar-container {
            position: relative;
            display: inline-block;
            margin-bottom: 16px;
        }
        
        .profile-avatar-img,
        .profile-avatar-placeholder {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            object-fit: cover;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 20px;
        }
        
        .profile-photo-upload-btn {
            position: absolute;
            bottom: 0;
            right: 0;
            width: 28px;
            height: 28px;
            background: #e91c1c;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            cursor: pointer;
            border: 2px solid white;
            font-size: 12px;
        }
        
        .profile-photo-upload-btn:hover {
            background: #ff3333;
        }
        
        .profile-user-info {
            margin-bottom: 16px;
        }
        
        .profile-user-name {
            margin: 0 0 4px 0;
            font-size: 16px;
            font-weight: 600;
            color: #ffffff;
        }
        
        .profile-user-email {
            margin: 0;
            font-size: 13px;
            color: #cccccc;
        }
        
        .profile-link-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 8px 0;
            color: #ffffff;
            font-size: 14px;
        }
        
        .profile-link-item:hover {
            color: #e91c1c;
        }
        
        .profile-link-item i {
            font-size: 12px;
            color: #cccccc;
        }
        
        .profile-menu-section {
            padding: 0;
        }
        
        .profile-menu-item {
            display: flex;
            align-items: center;
            padding: 14px 20px;
            border-bottom: 1px solid #f0f0f0;
            cursor: pointer;
            transition: background 0.2s;
            font-size: 14px;
            color: #333;
        }
        
        .profile-menu-item:hover {
            background: #f8f9fa;
        }
        
        .profile-menu-item i:first-child {
            margin-right: 12px;
            color: #666;
            width: 20px;
            text-align: center;
        }
        
        .profile-menu-item span {
            flex: 1;
        }
        
        .profile-menu-item i:last-child {
            color: #999;
            font-size: 12px;
        }
        
        .profile-footer {
            padding: 20px;
            border-top: 1px solid #333333;
            background: #000000;
        }
        
        /* Modal de ampliación de imagen */
        .image-enlargement-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.9);
            z-index: 10001;
            display: none;
            justify-content: center;
            align-items: center;
            cursor: pointer;
        }
        
        .image-enlargement-modal.active {
            display: flex;
        }
        
        .image-enlargement-content {
            position: relative;
            max-width: 90%;
            max-height: 90%;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .image-enlargement-content img {
            max-width: 500px;
            max-height: 500px;
            width: auto;
            height: auto;
            object-fit: contain;
            border-radius: 8px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.5);
        }
        
        .image-enlargement-close {
            position: absolute;
            top: -40px;
            right: 0;
            background: rgba(0, 0, 0, 0.9);
            color: #ffffff;
            border: none;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            font-size: 24px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
            font-weight: bold;
        }
        
        .image-enlargement-close:hover {
            background: #e91c1c;
            color: white;
            transform: scale(1.1);
        }
    </style>

    {{-- Modal de ampliación de imagen de perfil --}}
    <div id="imageEnlargementModal" class="image-enlargement-modal" onclick="closeImageEnlargementModal()">
        <div class="image-enlargement-content" onclick="event.stopPropagation()">
            <button class="image-enlargement-close" onclick="closeImageEnlargementModal()">&times;</button>
            <img id="enlargedProfileImage" src="" alt="Foto de perfil ampliada">
        </div>
    </div>

    <script>
        function openProfileModal() {
            document.getElementById('profileModal').style.display = 'flex';
        }
        
        function closeProfileModal() {
            document.getElementById('profileModal').style.display = 'none';
        }
        
        function openImageEnlargementModal(imageUrl) {
            const modal = document.getElementById('imageEnlargementModal');
            const img = document.getElementById('enlargedProfileImage');
            img.src = imageUrl;
            modal.classList.add('active');
        }
        
        function closeImageEnlargementModal() {
            const modal = document.getElementById('imageEnlargementModal');
            modal.classList.remove('active');
        }
        
        function uploadProfilePhoto(input) {
            if (!input.files || !input.files[0]) return;
            
            const file = input.files[0];
            if (!file.type.startsWith('image/')) {
                customAlert('Por favor, selecciona una imagen válida.');
                return;
            }
            
            if (file.size > 5 * 1024 * 1024) {
                customAlert('La imagen no debe ser mayor a 5MB.');
                return;
            }
            
            const formData = new FormData();
            formData.append('foto_perfil', file);
            formData.append('_token', '{{ csrf_token() }}');
            
            fetch('{{ route("usuarios.updatePhoto") }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const img = document.getElementById('profileAvatarImg');
                    const placeholder = document.getElementById('profileAvatarPlaceholder');
                    if (img) {
                        img.src = data.foto_url + '?t=' + Date.now();
                        img.style.display = 'block';
                        if (placeholder) placeholder.style.display = 'none';
                    } else if (placeholder) {
                        placeholder.innerHTML = '<img src="' + data.foto_url + '?t=' + Date.now() + '" style="width:100%; height:100%; border-radius:50%; object-fit:cover;">';
                    }
                    // Actualizar también el avatar del header
                    location.reload();
                } else {
                    customAlert(data.message || 'Error al subir la foto.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                customAlert('Error al subir la foto. Por favor, intenta nuevamente.');
            });
        }
        
        // Cerrar modal al hacer clic fuera
        document.getElementById('profileModal')?.addEventListener('click', function(e) {
            if (e.target === this) {
                closeProfileModal();
            }
        });
        
        // Cerrar con Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeProfileModal();
            }
        });
    </script>

    {{-- Modal de Confirmación Personalizado --}}
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

    {{-- Modal de Alerta Personalizado --}}
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
        /* Modales personalizados para confirmación y alertas */
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
            padding: 10px 24px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            min-width: 100px;
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
            padding: 10px 24px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            min-width: 100px;
        }

        .custom-btn-cancel:hover {
            background-color: #3d3d3d;
            border-color: #8c959d;
            transform: translateY(-1px);
        }
    </style>

    <script>
        // Sistema de alertas y confirmaciones personalizadas
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
                
                // Cerrar al hacer clic fuera del modal
                modal.addEventListener('click', function(e) {
                    if (e.target === modal) {
                        closeModal();
                    }
                });
                
                // Cerrar con Escape
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
                
                // Cerrar al hacer clic fuera del modal
                modal.addEventListener('click', function(e) {
                    if (e.target === modal) {
                        closeModal();
                        resolve(false);
                    }
                });
                
                // Cerrar con Escape
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

        // Reemplazar confirm y alert nativos globalmente (solo en contexto de la app)
        (function() {
            // Guardar referencias originales
            window._nativeConfirm = window.confirm;
            window._nativeAlert = window.alert;
            
            // Solo reemplazar si estamos en el contexto de la aplicación
            // No afectar otras páginas que puedan depender de alert/confirm nativos
            if (document.querySelector('.dashboard-container')) {
                window.confirm = window.customConfirm;
                window.alert = window.customAlert;
            }
        })();
    </script>

    {{-- Stack opcional para scripts por-vista --}}
    @stack('scripts')
    <script>
    // Función para abrir/cerrar el menú móvil
    function toggleMobileMenu() {
        const sidebar = document.querySelector('.sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        const menuBtn = document.querySelector('.mobile-menu-btn');
        
        if (sidebar && overlay) {
            const isOpen = sidebar.classList.contains('mobile-open');
            
            sidebar.classList.toggle('mobile-open');
            overlay.classList.toggle('active');
            
            // Animación del botón hamburguesa
            if (menuBtn) {
                if (isOpen) {
                    menuBtn.classList.remove('active');
                } else {
                    menuBtn.classList.add('active');
                }
            }
            
            // Prevenir scroll del body cuando el menú está abierto
            if (!isOpen) {
                document.body.style.overflow = 'hidden';
                document.body.style.position = 'fixed';
                document.body.style.width = '100%';
            } else {
                document.body.style.overflow = '';
                document.body.style.position = '';
                document.body.style.width = '';
            }
        }
    }

    // Cerrar el menú al hacer clic en un enlace del sidebar
    document.addEventListener('DOMContentLoaded', function() {
        const sidebarLinks = document.querySelectorAll('.sidebar-btn');
        const sidebar = document.querySelector('.sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        const menuBtn = document.querySelector('.mobile-menu-btn');
        
        sidebarLinks.forEach(link => {
            link.addEventListener('click', function() {
                // Cerrar en móviles y tablets (desde 768px)
                if (window.innerWidth <= 1024 && sidebar && overlay) {
                    sidebar.classList.remove('mobile-open');
                    overlay.classList.remove('active');
                    document.body.style.overflow = '';
                    document.body.style.position = '';
                    document.body.style.width = '';
                    if (menuBtn) {
                        menuBtn.classList.remove('active');
                    }
                }
            });
        });

        // Cerrar el menú al redimensionar la ventana si pasa a desktop
        window.addEventListener('resize', function() {
            if (window.innerWidth > 1024) {
                const sidebar = document.querySelector('.sidebar');
                const overlay = document.getElementById('sidebarOverlay');
                const menuBtn = document.querySelector('.mobile-menu-btn');
                if (sidebar && overlay) {
                    sidebar.classList.remove('mobile-open');
                    overlay.classList.remove('active');
                    document.body.style.overflow = '';
                    document.body.style.position = '';
                    document.body.style.width = '';
                    if (menuBtn) {
                        menuBtn.classList.remove('active');
                    }
                }
            }
        });

        // Cerrar con tecla Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const sidebar = document.querySelector('.sidebar');
                const overlay = document.getElementById('sidebarOverlay');
                const menuBtn = document.querySelector('.mobile-menu-btn');
                if (sidebar && sidebar.classList.contains('mobile-open')) {
                    sidebar.classList.remove('mobile-open');
                    overlay.classList.remove('active');
                    document.body.style.overflow = '';
                    document.body.style.position = '';
                    document.body.style.width = '';
                    if (menuBtn) {
                        menuBtn.classList.remove('active');
                    }
                }
            }
        });

        // Header retráctil para tablets y móviles
        let lastScrollTop = 0;
        let scrollTimeout = null;
        const header = document.querySelector('.top-bar');
        const mainContent = document.querySelector('.main-content');
        const dashboardContainer = document.querySelector('.dashboard-container');
        const isMobileOrTablet = () => window.innerWidth <= 1024;
        
        function adjustContentPadding() {
            if (!mainContent || !isMobileOrTablet() || !header) return;
            
            // Pequeño delay para que el DOM se actualice
            setTimeout(() => {
                const headerHeight = header.offsetHeight;
                const headerTop = parseFloat(getComputedStyle(header).top) || 0;
                const headerMargin = parseFloat(getComputedStyle(header).marginTop) || 0;
                const totalHeaderSpace = headerHeight + headerTop + headerMargin + 20; // 20px de margen extra
                
                if (header.classList.contains('header-retracted')) {
                    // Header oculto - mínimo padding
                    mainContent.style.paddingTop = '10px';
                    if (dashboardContainer) {
                        dashboardContainer.style.paddingTop = '10px';
                    }
                } else if (header.classList.contains('header-compact')) {
                    // Header compacto - padding reducido
                    const compactHeight = headerHeight;
                    mainContent.style.paddingTop = (compactHeight + 20) + 'px';
                    if (dashboardContainer) {
                        dashboardContainer.style.paddingTop = (compactHeight + 20) + 'px';
                    }
                } else {
                    // Header normal - padding completo
                    mainContent.style.paddingTop = (totalHeaderSpace) + 'px';
                    if (dashboardContainer) {
                        dashboardContainer.style.paddingTop = (totalHeaderSpace) + 'px';
                    }
                }
            }, 50);
        }
        
        function handleHeaderScroll() {
            if (!header || !isMobileOrTablet()) {
                header?.classList.remove('header-retracted', 'header-compact');
                document.body.classList.remove('header-retracted', 'header-compact');
                if (mainContent) {
                    mainContent.style.paddingTop = '';
                }
                if (dashboardContainer) {
                    dashboardContainer.style.paddingTop = '';
                }
                return;
            }

            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            const scrollDifference = scrollTop - lastScrollTop;
            
            // Limpiar timeout anterior
            if (scrollTimeout) {
                clearTimeout(scrollTimeout);
            }

            // Si el scroll es muy pequeño, ajustar padding sin cambiar estado
            if (Math.abs(scrollDifference) < 5) {
                adjustContentPadding();
                return;
            }

            // Si está cerca del top, mostrar el header
            if (scrollTop < 50) {
                header.classList.remove('header-retracted', 'header-compact');
                document.body.classList.remove('header-retracted', 'header-compact');
                lastScrollTop = scrollTop;
                adjustContentPadding();
                return;
            }

            // Si está scrolleando hacia abajo, ocultar el header
            if (scrollDifference > 0) {
                header.classList.add('header-retracted');
                header.classList.remove('header-compact');
                document.body.classList.add('header-retracted');
                document.body.classList.remove('header-compact');
            } 
            // Si está scrolleando hacia arriba, mostrar el header compacto
            else if (scrollDifference < 0) {
                header.classList.remove('header-retracted');
                header.classList.add('header-compact');
                document.body.classList.remove('header-retracted');
                document.body.classList.add('header-compact');
            }

            lastScrollTop = scrollTop;
            adjustContentPadding();
        }
        
        // Ajustar padding inicial cuando la página carga
        setTimeout(() => {
            adjustContentPadding();
        }, 100);

        // Throttle para mejorar el rendimiento
        let ticking = false;
        window.addEventListener('scroll', function() {
            if (!ticking) {
                window.requestAnimationFrame(function() {
                    handleHeaderScroll();
                    ticking = false;
                });
                ticking = true;
            }
        }, { passive: true });

        // Ajustar el header al redimensionar la ventana
        window.addEventListener('resize', function() {
            adjustContentPadding();
            if (!isMobileOrTablet()) {
                header?.classList.remove('header-retracted', 'header-compact');
                document.body.classList.remove('header-retracted', 'header-compact');
                if (mainContent) {
                    mainContent.style.paddingTop = '';
                }
                if (dashboardContainer) {
                    dashboardContainer.style.paddingTop = '';
                }
            }
        });
    });

    // Estado de autenticación básico para controles del chatbot
    window.APP_IS_GUEST = @json(!session()->has('usuario_id') || in_array('Invitado', (array)session('roles')));
    window.LOGIN_URL = @json(route('usuarios.inicioSesion'));
    (function(){
        const fab = document.querySelector('.chatbot-fab');
        const widget = document.getElementById('chatbot-widget');
        const drag = document.getElementById('chatbot-drag');
        const btnExpand = document.getElementById('chatbot-expand');
        const btnHide = document.getElementById('chatbot-hide');
        if (fab && widget) {
            const toggle = (force) => {
                const open = typeof force === 'boolean' ? force : !widget.classList.contains('is-open');
                widget.classList.toggle('is-open', open);
                widget.setAttribute('aria-hidden', open ? 'false' : 'true');
            };
            fab.addEventListener('click', function(e){
                e.preventDefault();
                if (window.APP_IS_GUEST) { window.location.href = window.LOGIN_URL; return; }
                toggle();
            });
            // Abrir desde cualquier botón/enlace con data-open-chatbot
            document.querySelectorAll('[data-open-chatbot]').forEach(function(el){
                el.addEventListener('click', function(ev){
                    ev.preventDefault();
                    if (window.APP_IS_GUEST) { window.location.href = window.LOGIN_URL; return; }
                    toggle(true);
                });
            });
            window.addEventListener('keydown', function(e){ if (e.key === 'Escape') { widget.classList.remove('is-open'); widget.setAttribute('aria-hidden','true'); } });

            // Expandir / reducir
            if (btnExpand) btnExpand.addEventListener('click', function(){
                if (window.APP_IS_GUEST) { window.location.href = window.LOGIN_URL; return; }
                widget.classList.toggle('is-expanded');
            });
            if (btnHide) btnHide.addEventListener('click', function(){
                widget.classList.remove('is-open');
                widget.setAttribute('aria-hidden','true');
            });

            // Drag simple
            if (drag) {
                let startX = 0, startY = 0, startRight = 0, startBottom = 0, dragging = false;
                const onMove = (e) => {
                    if (!dragging) return;
                    const dx = e.clientX - startX;
                    const dy = e.clientY - startY;
                    // Convertir desplazamiento en cambios de right/bottom (invertidos)
                    widget.style.right = Math.max(8, startRight - dx) + 'px';
                    widget.style.bottom = Math.max(8, startBottom - dy) + 'px';
                };
                const onUp = () => { dragging = false; document.removeEventListener('mousemove', onMove); document.removeEventListener('mouseup', onUp); };
                drag.addEventListener('mousedown', (e) => {
                    if (!widget.classList.contains('is-open')) return;
                    dragging = true;
                    startX = e.clientX; startY = e.clientY;
                    startRight = parseInt(getComputedStyle(widget).right, 10) || 0;
                    startBottom = parseInt(getComputedStyle(widget).bottom, 10) || 0;
                    document.addEventListener('mousemove', onMove);
                    document.addEventListener('mouseup', onUp);
                });
            }
        }
    })();
    </script>
</body>
</html>