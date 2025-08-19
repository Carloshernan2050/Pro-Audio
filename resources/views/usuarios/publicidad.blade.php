<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PRO AUDIO - Publicidad</title>
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

        {{-- Contenido principal de la página de publicidad --}}
        <main class="main-content">
            <h2 class="page-title">Servicios de Publicidad</h2>
            <p class="page-subtitle">Impulsa tu marca y tus eventos con nuestras soluciones de audio y perifoneo profesional.</p>

            {{-- Galería de productos de publicidad --}}
            <section class="productos-galeria">
                {{-- Item de producto 1 --}}
                <div class="producto-item">
                    <img src="/images/publicidad/perifoneo.jpg" alt="Perifoneo Móvil" class="producto-imagen">
                    <h4 class="producto-nombre">Perifoneo Móvil (Aprox. $100.000 COP/día)</h4>
                </div>

                {{-- Item de producto 2 --}}
                <div class="producto-item">
                    <img src="/images/publicidad/spot_publicitario.jpg" alt="Creación de Spot Publicitario" class="producto-imagen">
                    <h4 class="producto-nombre">Creación de Spot (Aprox. $50.000 COP)</h4>
                </div>

                {{-- Item de producto 3 --}}
                <div class="producto-item">
                    <img src="/images/publicidad/circuito_publicitario.jpg" alt="Circuito Publicitario" class="producto-imagen">
                    <h4 class="producto-nombre">Circuito Publicitario (Aprox. $200.000 COP/semana)</h4>
                </div>
                
                {{-- Item de producto 4 --}}
                <div class="producto-item">
                    <img src="/images/publicidad/jingles_musicales.jpg" alt="Jingles Musicales" class="producto-imagen">
                    <h4 class="producto-nombre">Producción de Jingles (Aprox. $80.000 COP)</h4>
                </div>
                
                {{-- Item de producto 5 --}}
                <div class="producto-item">
                    <img src="/images/publicidad/locucion_comercial.jpg" alt="Locución Comercial" class="producto-imagen">
                    <h4 class="producto-nombre">Locución Comercial (Aprox. $40.000 COP/min)</h4>
                </div>

                {{-- Item de producto 6 --}}
                <div class="producto-item">
                    <img src="/images/publicidad/megaeventos.jpg" alt="Publicidad para Megaeventos" class="producto-imagen">
                    <h4 class="producto-nombre">Publicidad en Megaeventos (Precio a convenir)</h4>
                </div>

                {{-- Item de producto 7 --}}
                <div class="producto-item">
                    <img src="/images/publicidad/voicemail.jpg" alt="Publicidad para Buzón de Voz" class="producto-imagen">
                    <h4 class="producto-nombre">Voz para Buzón de Voz (Aprox. $30.000 COP)</h4>
                </div>

                {{-- Item de producto 8 --}}
                <div class="producto-item">
                    <img src="/images/publicidad/sonido_corporativo.jpg" alt="Sonido Corporativo" class="producto-imagen">
                    <h4 class="producto-nombre">Sonido Corporativo (Aprox. $150.000 COP)</h4>
                </div>
            </section>
        </main>
    </div>
</body>
</html>
