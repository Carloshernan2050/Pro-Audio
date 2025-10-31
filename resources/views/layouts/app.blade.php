<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    {{-- Permite que cada vista hija defina su propio título --}}
    <title>PRO AUDIO - @yield('title', 'Inicio')</title> 
    
    {{-- Llamada al archivo CSS principal usando Vite --}}
    @vite('resources/css/app.css')

    {{-- Enlace a la librería de Font Awesome para los íconos --}}
    {{-- Se eliminó el atributo integrity/xintegrity para asegurar la carga de los iconos --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body>
    {{-- Contenedor principal del dashboard con la imagen de fondo --}}
    <div class="dashboard-container">
        
        {{-- Barra superior (Header) --}}
        <header class="top-bar">
            <h1>PRO AUDIO</h1>
            <form class="search-form" action="{{ route('buscar') }}" method="GET">
                <input type="text" name="buscar" class="search-input" placeholder="Buscar servicios..." value="{{ request('buscar') ?? '' }}">
                <button type="submit" class="search-btn">
                    {{-- Icono de la lupa --}}
                    <i class="fas fa-search"></i> 
                </button>
            </form>
            <a href="{{ route('usuarios.perfil') }}" class="profile-btn-header" title="Perfil">
                <i class="fas fa-user-circle"></i>
                <span>Perfil</span>
            </a>
        </header>

        {{-- Barra lateral izquierda (Sidebar) --}}
        @include('components.sidebar')

        {{-- Contenido principal --}}
        <main class="main-content">
            {{-- Aquí es donde se insertará el contenido único de cada vista hija --}}
            @yield('content') 
        </main>
    </div>
</body>
</html>