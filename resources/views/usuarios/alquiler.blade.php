<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PRO AUDIO - Alquiler</title>
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

        {{-- Contenido principal de la página de alquiler --}}
        <main class="main-content">
            <h2 class="page-title">Servicios de Alquiler y Equipos</h2>
            <p class="page-subtitle">Todo el equipo de audio y video que necesitas para tus eventos, disponible para alquiler.</p>

            {{-- Galería de productos de alquiler --}}
            <section class="productos-galeria">
                {{-- Item de producto 1 --}}
                <div class="producto-item">
                    <img src="/images/alquiler/parlante_activo.jpg" alt="Parlante Activo" class="producto-imagen">
                    <h4 class="producto-nombre">Parlante Activo (Aprox. $50.000 COP/día)</h4>
                </div>

                {{-- Item de producto 2 --}}
                <div class="producto-item">
                    <img src="/images/alquiler/microfono_inalambrico.jpg" alt="Micrófono Inalámbrico" class="producto-imagen">
                    <h4 class="producto-nombre">Micrófono Inalámbrico (Aprox. $30.000 COP/día)</h4>
                </div>

                {{-- Item de producto 3 --}}
                <div class="producto-item">
                    <img src="/images/alquiler/iluminacion_led.jpg" alt="Iluminación LED" class="producto-imagen">
                    <h4 class="producto-nombre">Iluminación LED (Aprox. $45.000 COP/día)</h4>
                </div>
                
                {{-- Item de producto 4 --}}
                <div class="producto-item">
                    <img src="/images/alquiler/maquina_humo.jpg" alt="Máquina de Humo" class="producto-imagen">
                    <h4 class="producto-nombre">Máquina de Humo (Aprox. $60.000 COP/día)</h4>
                </div>
                
                {{-- Item de producto 5 --}}
                <div class="producto-item">
                    <img src="/images/alquiler/proyector.jpg" alt="Proyector y Telón" class="producto-imagen">
                    <h4 class="producto-nombre">Proyector y Telón (Aprox. $80.000 COP/día)</h4>
                </div>

                {{-- Item de producto 6 --}}
                <div class="producto-item">
                    <img src="/images/alquiler/cabina_dj.jpg" alt="Cabina para DJ" class="producto-imagen">
                    <h4 class="producto-nombre">Cabina para DJ (Aprox. $70.000 COP/día)</h4>
                </div>

                {{-- Item de producto 7 --}}
                <div class="producto-item">
                    <img src="/images/alquiler/consola_mezclas.jpg" alt="Consola de Mezclas" class="producto-imagen">
                    <h4 class="producto-nombre">Consola de Mezclas (Aprox. $90.000 COP/día)</h4>
                </div>

                {{-- Item de producto 8 --}}
                <div class="producto-item">
                    <img src="/images/alquiler/tripode_parlante.jpg" alt="Trípode para Parlante" class="producto-imagen">
                    <h4 class="producto-nombre">Trípode para Parlante (Aprox. $15.000 COP/día)</h4>
                </div>
            </section>
        </main>
    </div>
</body>
</html>
