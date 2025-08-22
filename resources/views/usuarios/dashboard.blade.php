<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PRO AUDIO - Dashboard</title>
    {{-- Enlace a la fuente de Google Fonts --}}<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    {{-- Llamada al archivo CSS principal usando Vite --}}
    @vite('resources/css/app.css')
    @vite('resources/js/app.js')

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

        {{-- Contenido principal del dashboard --}}
        <main class="main-content">
            <h2 class="welcome-title">¡Bienvenido, {{ $usuario_nombre }}!</h2>
            <p class="welcome-text">¡Tu solución en sonido, iluminación y eventos!</p>
            
            {{-- Sección del carrusel de fotos --}}
            <section class="carousel-container">
                {{-- Las imágenes de los eventos --}}
                <div class="carousel-slide active">
                    <img src="{{ asset('/images/carrucel2.jpg') }}" alt="Evento 1">
                </div>
                <div class="carousel-slide">
                    <img src="{{ asset('images/carrucel3.jpg') }}" alt="Evento 2">
                </div>
                <div class="carousel-slide">
                    <img src="{{ asset('images/descarga.jpg') }}" alt="Evento 3">
                </div>

                {{-- Botones de navegación (anterior y siguiente) --}}
                <button class="carousel-btn prev-btn"><i class="fas fa-chevron-left"></i></button>
                <button class="carousel-btn next-btn"><i class="fas fa-chevron-right"></i></button>

                {{-- Puntos de navegación --}}
                <div class="carousel-dots">
                    <span class="dot active"></span>
                    <span class="dot"></span>
                    <span class="dot"></span>
                </div>
            </section>
            <section class="empresa-presentacion">
                <div class="presentacion-bloque-media">
                    <img src="/images/carro.jpg" alt="Imagen del carro" class="presentacion-img-grande">
                </div>

                <div class="presentacion-bloque-texto">
                    <h2>Sobre Nosotros</h2>
                    <p>
                        Somos PRO AUDIO, una empresa dedicada a transformar tus ideas en eventos inolvidables. Con más de 10 años de experiencia, nos hemos consolidado como líderes en el mercado, ofreciendo soluciones de alta calidad en sonido, iluminación y video.
                    </p>
                </div>

                {{-- Bloque de Presentación --}}
            <section class="presentacion-container">
                {{-- Bloque 1: Imagen a la izquierda, Texto a la derecha --}}
                <div class="presentacion-bloque">
                    <div class="presentacion-bloque-media">
                        <img src="/images/consola.jpg" alt="Imagen de Consola de Sonido" class="presentacion-img-pequena">
                    </div>
                    <div class="presentacion-bloque-texto">
                        <h3>Nuestra Misión</h3>
                        <p>
                            Ser la empresa de referencia en el sector de eventos a nivel nacional, reconocida por la innovación, la calidad y el compromiso social.
                        </p>
                    </div>
                </div>
        
                {{-- Bloque 2: Texto a la izquierda, Video a la derecha (orden invertido para pantallas grandes) --}}
                <div class="presentacion-bloque presentacion-bloque-invertido">
                    <div class="presentacion-bloque-texto">
                        <h3>Nuestros Valores</h3>
                        <ul>
                            <li><strong>Innovación:</strong> Estamos siempre a la vanguardia tecnológica.</li>
                            <li><strong>Calidad:</strong> Solo ofrecemos productos y servicios de primera.</li>
                            <li><strong>Compromiso:</strong> Nuestra dedicación a cada proyecto es total.</li>
                            <li><strong>Responsabilidad Social:</strong> Contribuimos al desarrollo de la comunidad.</li>
                            <li><strong>Excelencia:</strong> Buscamos la perfección en cada detalle.</li>
                        </ul>
                    </div>
                    <div class="presentacion-bloque-media">
                        <video class="presentacion-video" controls>
                            <source src="/videos/evento_v.mp4" type="video/mp4">
                            Tu navegador no soporta el video.
                        </video>
                    </div>
                </div>
            </section>
        </main>
    </div>
</body>
</html>