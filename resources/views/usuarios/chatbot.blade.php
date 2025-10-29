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
            <a href="{{ route('inicio') }}" class="sidebar-btn"><i class="fas fa-home"></i> Inicio</a>
            <a href="{{ route('usuarios.animacion') }}" class="sidebar-btn"><i class="fas fa-laugh-beam"></i> Animación</a>
            <a href="{{ route('usuarios.publicidad') }}" class="sidebar-btn"><i class="fas fa-bullhorn"></i> Publicidad</a>
            <a href="{{ route('usuarios.alquiler') }}" class="sidebar-btn"><i class="fas fa-box"></i> Alquiler</a>
            @if(session('role') !== 'Invitado')
            <a href="{{ route('usuarios.calendario') }}" class="sidebar-btn"><i class="fas fa-calendar-alt"></i> Calendario</a>
            @endif
            @if(session('role') === 'Administrador')
            <a href="{{ route('usuarios.ajustes') }}" class="sidebar-btn"><i class="fas fa-cog"></i> Ajustes</a>
            @endif
            @if(session('role') !== 'Invitado')
            <a href="{{ route('usuarios.chatbot') }}" class="sidebar-btn active"><i class="fas fa-robot"></i> Chatbot</a>
            @endif
        </aside>

        {{-- Contenedor principal del chat --}}
        <div class="page-container">
            <div class="chatbot-container">
                
                {{-- Encabezado del chat --}}
                <div class="chat-header">
                    <div class="chat-profile">
                        <i class="fas fa-user-circle chat-icon"></i>
                        <span class="chat-username">Chat</span>
                    </div>
                </div>

                {{-- Contenedor dinámico de mensajes --}}
                <div id="messages-container" class="messages-container"></div>

                {{-- Entrada del chat --}}
                <div class="input-container">
                    <i class="fas fa-user-circle chat-avatar"></i>
                    <input type="text" id="mensaje" class="chat-input" placeholder="Escribe tu mensaje...">
                    <button id="send-btn" class="send-btn">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Script para manejar el envío del chat --}}
    <script>
    document.getElementById('send-btn').addEventListener('click', enviarMensaje);
    document.getElementById('mensaje').addEventListener('keypress', function (e) {
        if (e.key === 'Enter') enviarMensaje();
    });

    async function enviarMensaje() {
        const input = document.getElementById('mensaje');
        const mensaje = input.value.trim();
        const contenedor = document.getElementById('messages-container');

        if (!mensaje) return;

        // Mostrar mensaje del usuario
        contenedor.innerHTML += `
            <div class="message-wrapper outgoing">
                <div class="message-bubble">${mensaje}</div>
                <i class="fas fa-user-circle chat-avatar"></i>
            </div>
        `;
        input.value = '';
        contenedor.scrollTop = contenedor.scrollHeight;

        // Mostrar indicador de "escribiendo..."
        const typing = document.createElement('div');
        typing.classList.add('message-wrapper', 'incoming');
        typing.innerHTML = `
            <i class="fas fa-robot chat-avatar"></i>
            <div class="message-bubble">Escribiendo...</div>
        `;
        contenedor.appendChild(typing);
        contenedor.scrollTop = contenedor.scrollHeight;

        try {
            const response = await fetch("{{ route('chat.enviar') }}", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": "{{ csrf_token() }}"
                },
                body: JSON.stringify({ mensaje })
            });

            const data = await response.json();
            typing.remove();

            if (data.respuesta) {
                contenedor.innerHTML += `
                    <div class="message-wrapper incoming">
                        <i class="fas fa-robot chat-avatar"></i>
                        <div class="message-bubble">${data.respuesta}</div>
                    </div>
                `;
            } else {
                contenedor.innerHTML += `
                    <div class="message-wrapper incoming">
                        <i class="fas fa-robot chat-avatar"></i>
                        <div class="message-bubble text-red-500">Error: ${data.error}</div>
                    </div>
                `;
            }

        } catch (error) {
            typing.remove();
            contenedor.innerHTML += `
                <div class="message-wrapper incoming">
                    <i class="fas fa-robot chat-avatar"></i>
                    <div class="message-bubble text-red-500">Error al conectar con el servidor.</div>
                </div>
            `;
        }

        contenedor.scrollTop = contenedor.scrollHeight;
    }
    </script>
</body>
</html>
