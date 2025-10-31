<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PRO AUDIO - Perfil de Usuario</title>
    {{-- Llamada al archivo CSS principal usando Vite, que contiene la estructura del dashboard --}}
    @vite('resources/css/app.css')

    {{-- Enlace a la librería de Font Awesome para los íconos --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" xintegrity="sha512-SnH5WK+bZxgPHs44uWpU6lJ9Xl3QO4K8y9Rk5vLqB34+Jk81f7qFk43Qk5p8G4eGk3k9Vb/qH6r/jB5sD5k6w==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    
    {{-- Enlace al nuevo archivo de estilos específico para el perfil --}}
    <link rel="stylesheet" href="{{ asset('css/perfil.css') }}">
</head>
<body>
    {{-- Contenedor principal del dashboard con la imagen de fondo --}}
    <div class="dashboard-container">
        {{-- Barra superior (estilizada en app.css) --}}
        <header class="top-bar">
            <h1>PRO AUDIO</h1>
            <form class="search-form" action="{{ route('buscar') }}" method="GET">
                <input type="text" name="buscar" class="search-input" placeholder="Buscar servicios..." value="{{ request('buscar') ?? '' }}">
                <button type="submit" class="search-btn">
                    <i class="fas fa-search"></i>
                </button>
            </form>
            <a href="{{ route('usuarios.perfil') }}" class="profile-btn-header" title="Perfil">
                <i class="fas fa-user-circle"></i>
                <span>Perfil</span>
            </a>
        </header>

        {{-- Barra lateral izquierda (estilizada en app.css) --}}
        @include('components.sidebar')

        {{-- Contenido principal de la página de perfil (estilizado en app.css) --}}
        <main class="main-content">
            <h2 class="page-title">Mi Perfil</h2>
            <p class="page-subtitle">Aquí puedes ver y gestionar tu información personal.</p>

            <div class="profile-container">
                @if(session()->has('usuario_id') && $usuario && (session('role') === 'Cliente' || session('role') === 'Administrador'))
                    <div class="profile-details">
                        <p><strong>Nombres:</strong> 
                            {{ ucfirst(strtolower($usuario->primer_nombre ?? '')) }}
                            {{ $usuario->segundo_nombre ? ucfirst(strtolower($usuario->segundo_nombre)) : '' }}
                        </p>
                        <p><strong>Apellidos:</strong> 
                            {{ ucfirst(strtolower($usuario->primer_apellido ?? '')) }}
                            {{ $usuario->segundo_apellido ? ucfirst(strtolower($usuario->segundo_apellido)) : '' }}
                        </p>
                        <p><strong>Correo:</strong> {{ $usuario->correo ?? 'No registrado' }}</p>
                        <p><strong>Teléfono:</strong> {{ $usuario->telefono ?? 'No registrado' }}</p>
                    </div>
                @elseif(!session()->has('usuario_id'))
                    <p class="text-gray-300">Debes iniciar sesión para ver tu perfil.</p>
                @else
                    <p class="text-gray-300">No tienes permisos para ver esta información.</p>
                @endif

                {{-- Botón para volver al panel --}}
                    <a href="{{ route('inicio') }}" class="btn-secondary inline-block">
                        <i class="fas fa-arrow-left"></i> Volver al panel
                    </a>
                {{-- Contenedor flexible para los botones --}}
                <div class="flex items-center justify-center sm:justify-start gap-4 mt-3">
                    @if(session()->has('usuario_id'))
                        {{-- Botón para cerrar sesión --}}
                        <form action="{{ route('usuarios.cerrarSesion') }}" method="POST">
                            @csrf
                            <button type="submit" class="btn-logout">
                                <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                            </button>
                        </form>
                    @else
                        {{-- Invitado: Acceder como Cliente (redirige a registro y fija rol Cliente) --}}
                        <form action="{{ route('role.set') }}" method="POST">
                            @csrf
                            <input type="hidden" name="role" value="Cliente">
                            <button type="submit" class="btn-logout">
                                <i class="fas fa-user-plus"></i> Acceder como Cliente
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </main>
    </div>
</body>
</html>
