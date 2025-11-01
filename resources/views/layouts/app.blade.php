<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    {{-- Permite que cada vista hija defina su propio título --}}
    <title>PRO AUDIO - @yield('title', 'Inicio')</title> 
    
    {{-- Llamada al archivo CSS principal usando Vite --}}
    @vite('resources/css/app.css')
    {{-- CSS específico del layout (topbar, sidebar, fondo, estructura) --}}
    @vite('resources/css/layout.css')

    {{-- Stack opcional para estilos por-vista --}}
    @stack('styles')

    {{-- Enlace a la librería de Font Awesome para los íconos --}}
    {{-- Se eliminó el atributo integrity/xintegrity para asegurar la carga de los iconos --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body>
    {{-- Contenedor principal del dashboard con la imagen de fondo --}}
    <div class="dashboard-container">
        
        {{-- Barra superior reutilizable --}}
        @include('components.topbar')

        {{-- Barra lateral izquierda (Sidebar) --}}
        @include('components.sidebar')

        {{-- Contenido principal --}}
        <main class="main-content">
            {{-- Aquí es donde se insertará el contenido único de cada vista hija --}}
            @yield('content') 
        </main>
    </div>
    {{-- Botón flotante global para abrir el Chatbot --}}
    <a href="{{ route('usuarios.chatbot') }}" class="chatbot-fab" title="Abrir chatbot" aria-label="Abrir chatbot">
        <i class="fas fa-robot"></i>
    </a>
    {{-- Ventana flotante del Chatbot (mini panel) --}}
    <div id="chatbot-widget" class="chatbot-widget" aria-hidden="true">
        <div class="chatbot-widget__drag" id="chatbot-drag"></div>
        <div class="chatbot-widget__controls">
            <button type="button" class="chatbot-ctrl" id="chatbot-expand" title="Expandir / Reducir" aria-label="Expandir o reducir">
                <i class="fas fa-up-right-and-down-left-from-center"></i>
            </button>
            <button type="button" class="chatbot-ctrl" id="chatbot-hide" title="Ocultar" aria-label="Ocultar">
                <i class="fas fa-xmark"></i>
            </button>
        </div>
        <iframe src="{{ route('usuarios.chatbot') }}" title="Chatbot" loading="lazy" class="chatbot-iframe"></iframe>
    </div>
    {{-- Stack opcional para scripts por-vista --}}
    @stack('scripts')
    <script>
    (function(){
        const fab = document.querySelector('.chatbot-fab');
        const widget = document.getElementById('chatbot-widget');
        const drag = document.getElementById('chatbot-drag');
        const btnExpand = document.getElementById('chatbot-expand');
        const btnHide = document.getElementById('chatbot-hide');
        if (fab && widget) {
            const toggle = () => {
                const open = widget.classList.toggle('is-open');
                widget.setAttribute('aria-hidden', open ? 'false' : 'true');
            };
            fab.addEventListener('click', function(e){ e.preventDefault(); toggle(); });
            window.addEventListener('keydown', function(e){ if (e.key === 'Escape') { widget.classList.remove('is-open'); widget.setAttribute('aria-hidden','true'); } });

            // Expandir / reducir
            if (btnExpand) btnExpand.addEventListener('click', function(){
                widget.classList.toggle('is-expanded');
            });
            if (btnHide) btnHide.addEventListener('click', function(){
                widget.classList.remove('is-open');
                widget.setAttribute('aria-hidden','true');
            });

            // Drag simple
            if (drag) {
                let startX = 0, startY = 0, startRight = 0, startBottom = 0, dragging = false;
                const onMove = (e) => {
                    if (!dragging) return;
                    const dx = e.clientX - startX;
                    const dy = e.clientY - startY;
                    // Convertir desplazamiento en cambios de right/bottom (invertidos)
                    widget.style.right = Math.max(8, startRight - dx) + 'px';
                    widget.style.bottom = Math.max(8, startBottom - dy) + 'px';
                };
                const onUp = () => { dragging = false; document.removeEventListener('mousemove', onMove); document.removeEventListener('mouseup', onUp); };
                drag.addEventListener('mousedown', (e) => {
                    if (!widget.classList.contains('is-open')) return;
                    dragging = true;
                    startX = e.clientX; startY = e.clientY;
                    startRight = parseInt(getComputedStyle(widget).right, 10) || 0;
                    startBottom = parseInt(getComputedStyle(widget).bottom, 10) || 0;
                    document.addEventListener('mousemove', onMove);
                    document.addEventListener('mouseup', onUp);
                });
            }
        }
    })();
    </script>
</body>
</html>