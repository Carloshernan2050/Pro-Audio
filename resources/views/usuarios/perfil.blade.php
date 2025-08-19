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
            <form class="search-form" action="#" method="GET">
                <input type="text" name="buscar" class="search-input" placeholder="Buscar...">
                <button type="submit" class="search-btn">🔍</button>
            </form>
        </header>

        {{-- Barra lateral izquierda (estilizada en app.css) --}}
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

        {{-- Contenido principal de la página de perfil (estilizado en app.css) --}}
        <main class="main-content">
            <h2 class="page-title">Mi Perfil</h2>
            <p class="page-subtitle">Aquí puedes ver y gestionar tu información personal.</p>

            <div class="profile-container">
                @if($usuario)
                    <div class="profile-details">
                        <p><strong>Nombre completo:</strong>
                            {{ $usuario->primer_nombre ?? '' }}
                            {{ $usuario->segundo_nombre ?? '' }}
                            {{ $usuario->primer_apellido ?? '' }}
                            {{ $usuario->segundo_apellido ?? '' }}</p>

                        <p><strong>Correo electrónico:</strong> {{ $usuario->correo ?? '' }}</p>
                    </div>
                @else
                    <p class="text-gray-300">No se encontró información del usuario.</p>
                @endif
                <a href="{{ route('usuarios.dashboard') }}" class="btn-secondary mt-3 inline-block">
                    <i class="fas fa-arrow-left"></i> Volver al panel
                </a>
            </div>
        </main>
    </div>
</body>
</html>
