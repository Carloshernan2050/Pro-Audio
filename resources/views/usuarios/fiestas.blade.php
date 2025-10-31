<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PRO AUDIO - Fiestas</title>
    {{-- Llamada al archivo CSS principal usando Vite --}}
    @vite('resources/css/app.css')

    {{-- Enlace a la librería de Font Awesome para los íconos --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" xintegrity="sha512-SnH5WK+bZxgPHs44uWpU6lJ9Xl3QO4K8y9Rk5vLqB34+Jk81f7qFk43Qk5p8G4eGk3k9Vb/qH6r/jB5sD5k6w==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body>
    {{-- Contenedor principal del dashboard con la imagen de fondo --}}
    <div class="dashboard-container">
        {{-- Barra superior --}}
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

        {{-- Barra lateral izquierda --}}
        @include('components.sidebar')

       <main class="main-content">
            <h2 class="page-title">Fiestas</h2>
            <p class="page-subtitle"></p>
            
            <section class="productos-servicio">
                <div class="productos-grid">
                    @forelse($subServicios as $subServicio)
                        <div class="producto-item">
                            <img src="/images/fiestas/{{ strtolower(str_replace(' ', '_', $subServicio->nombre)) }}.jpg" 
                                 alt="{{ $subServicio->nombre }}" 
                                 class="producto-imagen"
                                 onerror="this.src='/images/default.jpg'">
                            <h4 class="producto-nombre">{{ $subServicio->nombre }}</h4>
                            @if($subServicio->descripcion)
                                <p class="producto-descripcion">{{ $subServicio->descripcion }}</p>
                            @endif
                        </div>
                    @empty
                        <div class="no-services">
                            <p>No hay sub-servicios disponibles para Fiestas en este momento.</p>
                        </div>
                    @endforelse
                </div>
            </section>
        </main>
    </div>
</body>
</html>
