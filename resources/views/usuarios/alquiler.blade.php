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
                <button type="submit" class="search-btn">
                    <i class="fas fa-search"></i>
                </button>
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

        {{-- Contenido principal --}}
        <main class="main-content">
            <h2 class="page-title">Alquiler de Equipo de Sonido</h2>
            <p class="page-subtitle">Equipos profesionales de alta calidad para tus eventos. Disponibles por día.</p>
            
            <section class="productos-servicio">
                <div class="productos-grid">
                    {{-- Item de producto 1 --}}
                    <div class="producto-item">
                        <img src="/images/alquiler/bafle_autoamplificado.jpg" alt="Bafle Autoamplificado" class="producto-imagen">
                        <h4 class="producto-nombre">Bafle Autoamplificado</h4>
                    </div>

                    {{-- Item de producto 2 --}}
                    <div class="producto-item">
                        <img src="/images/alquiler/luces_audioritmicas.jpg" alt="Luces Audiorítmicas" class="producto-imagen">
                        <h4 class="producto-nombre">Luces Audiorítmicas</h4>
                    </div>

                    {{-- Item de producto 3 --}}
                    <div class="producto-item">
                        <img src="/images/alquiler/microfono_inalambrico.jpg" alt="Micrófono Inalámbrico" class="producto-imagen">
                        <h4 class="producto-nombre">Micrófono Inalámbrico</h4>
                    </div>
                    
                    {{-- Item de producto 4 --}}
                    <div class="producto-item">
                        <img src="/images/alquiler/maquina_humo.jpg" alt="Máquina de Humo" class="producto-imagen">
                        <h4 class="producto-nombre">Máquina de Humo</h4>
                    </div>
                    
                    {{-- Item de producto 5 --}}
                    <div class="producto-item">
                        <img src="/images/alquiler/proyector.jpg" alt="Proyector y Telón" class="producto-imagen">
                        <h4 class="producto-nombre">Proyector y Telón</h4>
                    </div>

                    {{-- Item de producto 6 --}}
                    <div class="producto-item">
                        <img src="/images/alquiler/cabina_dj.jpg" alt="Cabina para DJ" class="producto-imagen">
                        <h4 class="producto-nombre">Cabina para DJ</h4>
                    </div>

                    {{-- Item de producto 7 --}}
                    <div class="producto-item">
                        <img src="/images/alquiler/consola_mezclas.jpg" alt="Consola de Mezclas" class="producto-imagen">
                        <h4 class="producto-nombre">Consola de Mezclas</h4>
                    </div>

                    {{-- Item de producto 8 --}}
                    <div class="producto-item">
                        <img src="/images/alquiler/tripode_parlante.jpg" alt="Trípode para Parlante" class="producto-imagen">
                        <h4 class="producto-nombre">Trípode para Parlante</h4>
                    </div>
                </div>
            </section>
        </main>
    </div>
</body>
</html>