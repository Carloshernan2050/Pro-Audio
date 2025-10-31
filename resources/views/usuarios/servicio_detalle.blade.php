<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PRO AUDIO - Detalle de Servicio</title>
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

        {{-- Contenido principal de la página de detalle --}}
        <main class="main-content">
            {{-- Sección de detalles del servicio --}}
            <section class="servicio-detalle-container">
                <div class="detalle-principal">
                    <div class="detalle-imagen-principal">
                        <img src="/images/animacion/dj_profesional.jpg" alt="DJ Profesional" class="imagen-grande">
                    </div>
                    <div class="detalle-info">
                        <h2>DJ Profesional</h2>
                        <h4 class="precio-estimado">Precio Estimado: $200.000 COP</h4>
                        <p class="descripcion-servicio">
                            Nuestro servicio de DJ profesional ofrece una experiencia musical inigualable para tu evento. Con un amplio repertorio de géneros y la capacidad de adaptarse a la atmósfera del momento, garantizamos que tu público se mantendrá enérgico y entretenido de principio a fin.
                        </p>
                        <h4 class="caracteristicas-titulo">Características Destacadas:</h4>
                        <ul class="caracteristicas-lista">
                            <li><i class="fas fa-music"></i> Repertorio musical versátil.</li>
                            <li><i class="fas fa-sliders-h"></i> Control de mezcla y sonido profesional.</li>
                            <li><i class="fas fa-clock"></i> Servicio por horas, adaptable a tu evento.</li>
                            <li><i class="fas fa-microphone-alt"></i> Micrófono para anuncios y animación verbal.</li>
                            <li><i class="fas fa-volume-up"></i> Sonido de alta fidelidad.</li>
                        </ul>
                        <button class="btn-cotizar">Solicitar Cotización</button>
                    </div>
                </div>

                {{-- Galería de imágenes relacionadas --}}
                <div class="galeria-relacionada">
                    <h3>Otros Servicios de Animación</h3>
                    <div class="galeria-grid">
                        <img src="/images/animacion/maestro_ceremonias.jpg" alt="Maestro de Ceremonias" class="imagen-relacionada">
                        <img src="/images/animacion/efectos_especiales.jpg" alt="Efectos Especiales" class="imagen-relacionada">
                        <img src="/images/animacion/produccion_audiovisual.jpg" alt="Producción Audiovisual" class="imagen-relacionada">
                    </div>
                </div>
            </section>
        </main>
    </div>
</body>
</html>
