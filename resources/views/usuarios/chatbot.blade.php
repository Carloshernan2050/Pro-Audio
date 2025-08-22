<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chatbot</title>

    {{-- Enlace a tu archivo CSS principal --}}
    @vite('resources/css/app.css')

    {{-- Importación de íconos de Font Awesome --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
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

    <div class="page-container">
        {{-- Contenedor principal del chat --}}
        <div class="chatbot-container">
            
            {{-- Encabezado del chat (perfil de usuario) --}}
            <div class="chat-header">
                <div class="chat-profile">
                    <i class="fas fa-user-circle chat-icon"></i>
                    <span class="chat-username">Chat</span>
                </div>
            </div>

            {{-- Contenedor de mensajes --}}
            <div class="messages-container">
                
                {{-- Mensaje entrante (del bot) --}}
                <div class="message-wrapper incoming">
                    <i class="fas fa-user-circle chat-avatar"></i>
                    <div class="message-bubble">Hola, ¿en qué puedo ayudarte?</div>
                </div>

                {{-- Mensaje saliente (del usuario) --}}
                <div class="message-wrapper outgoing">
                    <div class="message-bubble">Quisiera saber sobre los servicios.</div>
                    <i class="fas fa-user-circle chat-avatar"></i>
                </div>

                {{-- Otro mensaje entrante --}}
                <div class="message-wrapper incoming">
                    <i class="fas fa-user-circle chat-avatar"></i>
                    <div class="message-bubble">Claro. Tenemos servicios de alquiler de equipos de sonido, luces y animación.</div>
                </div>

                {{-- Otro mensaje saliente --}}
                <div class="message-wrapper outgoing">
                    <div class="message-bubble">¿Y sobre los precios?</div>
                    <i class="fas fa-user-circle chat-avatar"></i>
                </div>

                {{-- Otro mensaje entrante --}}
                <div class="message-wrapper incoming">
                    <i class="fas fa-user-circle chat-avatar"></i>
                    <div class="message-bubble">El precio depende del paquete y la duración del evento. ¿Tienes un evento en mente?</div>
                </div>
                
            </div>

            {{-- Contenedor de entrada de texto --}}
            <div class="input-container">
                <i class="fas fa-user-circle chat-avatar"></i>
                <input type="text" class="chat-input" placeholder="Escribe tu mensaje...">
                <button class="send-btn">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
        </div>
    </div>
</body>
</html>