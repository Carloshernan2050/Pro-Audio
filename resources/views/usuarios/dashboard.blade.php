@extends('layouts.app')

@section('title', 'Dashboard')

@push('styles')
    @vite('resources/css/dashboard.css')
@endpush

@push('scripts')
    @vite('resources/js/app.js')
@endpush

@section('content')
        {{-- Contenido principal del dashboard --}}
        <div class="dashboard-content-wrapper">
            <h2 class="welcome-title">¡Bienvenido, {{ session('usuario_nombre') ? ucfirst(strtolower(session('usuario_nombre'))) : 'Invitado' }}!</h2>
            <p class="welcome-text">¡Tu solución profesional en sonido, iluminación y eventos!</p>
            
            {{-- Sección del carrusel de fotos (MANTENIDO TAL CUAL) --}}
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
            
            <hr> {{-- Separador visual --}}

            {{-- Nuevo Contenedor Grid para la Distribución Profesional --}}
            <div class="content-grid">
                
                {{-- Sección Primaria: Acerca de, Misión y Proceso (Columna principal) --}}
                <section class="primary-section">
                    
                    {{-- Bloque de Presentación de la Empresa (Imagen destacada) --}}
                    <div class="featured-image-container">
                        <img src="/images/carro.jpg" alt="Carro" class="presentacion-img-grande">
                    </div>

                    <div class="content-card about-us">
                        <h3>Sobre Nosotros: Más de 15 Años de Experiencia</h3>
                        <p>
                            Somos PRO AUDIO, una empresa dedicada a transformar tus ideas en eventos inolvidables. Con más de 10 años de experiencia, nos hemos consolidado como líderes en el mercado, ofreciendo soluciones de alta calidad en sonido, iluminación y video. Nuestro compromiso es la excelencia en cada detalle.
                        </p>
                    </div>

                    {{-- Bloque de la Misión --}}
                    <div class="content-card mission-card">
                        <h3>Nuestra Misión</h3>
                        <div class="presentacion-bloque row">
                            <div class="presentacion-bloque-media">
                                <img src="/images/consola.jpg" alt="Consola de Sonido" class="presentacion-img-pequena">
                            </div>
                            <div class="presentacion-bloque-texto">
                                <p>
                                    Ser la empresa de referencia en el sector de eventos a nivel nacional, reconocida por la innovación, la calidad y el compromiso social. Proveemos tecnología de punta y personal altamente capacitado.
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Bloque "Cómo Trabajamos" (Proceso de Contratación) --}}
                    <div class="content-card process-card">
                        <h3><i class="fas fa-magic"></i> Nuestro Proceso de Trabajo</h3>
                        <div class="process-steps-grid">
                            <div class="process-step">
                                <div class="step-icon"><i class="fas fa-file-contract"></i></div>
                                <h4>1. Cotización y Propuesta</h4>
                                <p>Define tu evento y recibe una propuesta personalizada sin compromiso.</p>
                            </div>
                            <div class="process-step">
                                <div class="step-icon"><i class="fas fa-tasks"></i></div>
                                <h4>2. Planificación y Logística</h4>
                                <p>Coordinamos equipos, personal y tiempos. ¡Nos encargamos de todos los detalles técnicos!</p>
                            </div>
                            <div class="process-step">
                                <div class="step-icon"><i class="fas fa-headphones-alt"></i></div>
                                <h4>3. Ejecución Exitosa</h4>
                                <p>Llevamos tu evento a cabo con profesionalismo, puntualidad y la mejor calidad de audio/video.</p>
                            </div>
                        </div>
                    </div>
                    
                </section>

                {{-- Sección Secundaria: Valores, Métricas y CTA (Columna de Destacados) --}}
                <aside class="secondary-section">
                    
                    {{-- Bloque de Métricas Clave (KPIs) --}}
                    <div class="content-card stats-card">
                        <h3><i class="fas fa-chart-line"></i> Nuestra Trayectoria</h3>
                        <div class="stats-grid">
                            <div class="stat-item">
                                <span class="stat-number">15+</span>
                                <span class="stat-label">Años de Experiencia</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-number">250+</span>
                                <span class="stat-label">Eventos Realizados</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-number">4.9/5</span>
                                <span class="stat-label">Calificación Promedio</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-number">100%</span>
                                <span class="stat-label">Equipos de Alta Fidelidad</span>
                            </div>
                        </div>
                    </div>

                    {{-- Bloque de los Valores (Card Destacada) --}}
                    <div class="content-card values-card">
                        <h3>Nuestros Valores Fundamentales</h3>
                        <ul>
                            <li><strong>Innovación:</strong> Siempre a la vanguardia tecnológica del sector.</li>
                            <li><strong>Calidad:</strong> Productos y servicios de primera línea garantizados.</li>
                            <li><strong>Compromiso:</strong> Dedicación total en la ejecución de cada proyecto.</li>
                            <li><strong>Responsabilidad Social:</strong> Contribuimos activamente al desarrollo de la comunidad.</li>
                            <li><strong>Excelencia:</strong> Búsqueda constante de la perfección en todos los servicios.</li>
                        </ul>
                        {{-- Video de Valores --}}
                        <div class="presentacion-bloque-media video-destacado">
                            <video class="presentacion-video" controls>
                                <source src="/videos/evento_v.mp4" type="video/mp4">
                                <track kind="subtitles" src="/videos/evento_v.vtt" srclang="es" label="Español" default>
                                <track kind="descriptions" src="/videos/evento_v_desc.vtt" srclang="es" label="Descripción">
                                Tu navegador no soporta el video.
                            </video>
                        </div>
                    </div>
                    
                    {{-- Bloque de Llamada a la Acción (CTA) para Contacto --}}
                    <div class="content-card cta-quote">
                        <h3>¿Tienes un nuevo proyecto?</h3>
                        <p>¡Inicia el proceso de cotización para tu evento de manera rápida y sencilla!</p>
                        <a href="#" class="btn-primary-action" data-open-chatbot>
                            <i class="fas fa-pen-nib"></i> Iniciar Contacto
                        </a>
                    </div>

                </aside>

            </div> {{-- Fin de .content-grid --}}
        </div> {{-- Fin de .dashboard-content-wrapper --}}
@endsection
