<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PRO AUDIO - Dashboard</title>
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

        {{-- Contenido principal del dashboard --}}
        <main class="main-content">
            <h2 class="welcome-title">¡Bienvenido, {{ $usuario_nombre }}!</h2>
            <p class="welcome-text">¡Tu solución en sonido, iluminación y eventos!</p>

            <section class="empresa-presentacion">
                <div class="presentacion-bloque-media">
                    <img src="/images/carro.jpg" alt="Imagen del carro" class="presentacion-img-grande">
                </div>

                <div class="presentacion-bloque-texto">
                    <h2>Sobre Nosotros</h2>
                    <p>
                        Somos PRO AUDIO, una empresa dedicada a transformar tus ideas en eventos inolvidables. Con más de 10 años de experiencia, nos hemos consolidado como líderes en el mercado, ofreciendo soluciones de alta calidad en sonido, iluminación y video.
                    </p>
                    <h3>Nuestra Misión</h3>
                    <p>
                        Ser la empresa de referencia en el sector de eventos a nivel nacional, reconocida por la innovación, la calidad y el compromiso social.
                    </p>
                </div>

                <div class="presentacion-bloque-media">
                    <img src="/images/consola.jpg" alt="Imagen pequeña" class="presentacion-img-pequena">
                </div>

                <div class="presentacion-bloque-media">
                    <video class="presentacion-video" controls>
                        <source src="/videos/evento_v.mp4" type="video/mp4">
                        Tu navegador no soporta el video.
                    </video>
                </div>

    
                    <div class="presentacion-bloque-texto">
                        <h3>Nuestros Valores</h3>
                        <ul>
                            <li>Innovación:** Estamos siempre a la vanguardia tecnológica.</li>
                            <li>Calidad:** Solo ofrecemos productos y servicios de primera.</li>
                            <li>Compromiso:** Nuestra dedicación a cada proyecto es total.</li>
                            <li>Responsabilidad Social:** Contribuimos al desarrollo de la comunidad.</li>
                            <li>Excelencia:** Buscamos la perfección en cada detalle.</li>
                        </ul>
                    </div>
            </section>
        </main>
    </div>
</body>
</html>