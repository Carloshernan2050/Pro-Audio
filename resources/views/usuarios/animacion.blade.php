<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PRO AUDIO - Animación</title>
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

        {{-- Contenido principal de la página de animación --}}
        <main class="main-content">
            <h2 class="page-title">Servicios de Animación</h2>
            <p class="page-subtitle">Explora nuestras opciones creativas para que tu evento sea único y lleno de diversión.</p>

            {{-- Galería de productos de animación --}}
            <section class="productos-galeria">
                {{-- Item de producto 1 --}}
                <div class="producto-item">
                    <img src="/images/animacion/maestro_ceremonias.jpg" alt="Maestro de Ceremonias" class="producto-imagen">
                    <h4 class="producto-nombre">Maestro de Ceremonias (Aprox. $150.000 COP)</h4>
                </div>

                {{-- Item de producto 2 --}}
                <div class="producto-item">
                    <img src="/images/animacion/dj_profesional.jpg" alt="DJ Profesional" class="producto-imagen">
                    <h4 class="producto-nombre">DJ Profesional (Aprox. $200.000 COP)</h4>
                </div>

                {{-- Item de producto 3 --}}
                <div class="producto-item">
                    <img src="/images/animacion/fiesta_corporativa.jpg" alt="Animación para Fiestas Corporativas" class="producto-imagen">
                    <h4 class="producto-nombre">Animación Corporativa (Aprox. $250.000 COP)</h4>
                </div>
                
                {{-- Item de producto 4 --}}
                <div class="producto-item">
                    <img src="/images/animacion/lanzamiento_productos.jpg" alt="Lanzamiento de Productos" class="producto-imagen">
                    <h4 class="producto-nombre">Lanzamiento de Productos (Aprox. $300.000 COP)</h4>
                </div>
                
                {{-- Item de producto 5 --}}
                <div class="producto-item">
                    <img src="/images/animacion/eventos_sociales.jpg" alt="Animador de Eventos Sociales" class="producto-imagen">
                    <h4 class="producto-nombre">Eventos Sociales y Privados (Aprox. $180.000 COP)</h4>
                </div>

                {{-- Item de producto 6 --}}
                <div class="producto-item">
                    <img src="/images/animacion/concierto_iluminacion.jpg" alt="Animación en Conciertos" class="producto-imagen">
                    <h4 class="producto-nombre">Animación de Conciertos (Aprox. $400.000 COP)</h4>
                </div>

                {{-- Item de producto 7 --}}
                <div class="producto-item">
                    <img src="/images/animacion/coordinador_eventos.jpg" alt="Coordinador de Eventos" class="producto-imagen">
                    <h4 class="producto-nombre">Coordinador de Eventos (Aprox. $220.000 COP)</h4>
                </div>

                {{-- Item de producto 8 --}}
                <div class="producto-item">
                    <img src="/images/animacion/presentador_evento.jpg" alt="Presentador de Eventos" class="producto-imagen">
                    <h4 class="producto-nombre">Presentador de Eventos (Aprox. $170.000 COP)</h4>
                </div>

                {{-- Item de producto 9 --}}
                <div class="producto-item">
                    <img src="/images/animacion/efectos_especiales.jpg" alt="Efectos Especiales para Eventos" class="producto-imagen">
                    <h4 class="producto-nombre">Efectos Especiales (Aprox. $280.000 COP)</h4>
                </div>

                {{-- Item de producto 10 --}}
                <div class="producto-item">
                    <img src="/images/animacion/audiovisual.jpg" alt="Producción Audiovisual" class="producto-imagen">
                    <h4 class="producto-nombre">Producción Audiovisual (Aprox. $350.000 COP)</h4>
                </div>
            </section>
        </main>
    </div>
</body>
</html>
