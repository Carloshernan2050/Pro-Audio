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

    // Helpers de estado en sessionStorage
    function saveChatState() {
        try {
            const contenedor = document.getElementById('messages-container');
            sessionStorage.setItem('chat.messagesHTML', contenedor.innerHTML);
            sessionStorage.setItem('chat.days', String(Number(window.currentDays) || 1));
        } catch (_) {}
    }

    function loadChatState() {
        try {
            return {
                html: sessionStorage.getItem('chat.messagesHTML'),
                days: sessionStorage.getItem('chat.days')
            };
        } catch (_) { return { html: null, days: null }; }
    }

    function clearChatState() {
        try {
            sessionStorage.removeItem('chat.messagesHTML');
            sessionStorage.removeItem('chat.days');
            window.currentDays = 1;
        } catch (_) {}
    }

    // Mensaje inicial amigable + opciones agrupadas o restauración desde sessionStorage
    window.addEventListener('DOMContentLoaded', async () => {
        const contenedor = document.getElementById('messages-container');
        const restored = loadChatState();
        if (restored && restored.html) {
            contenedor.innerHTML = restored.html;
            window.currentDays = restored.days && Number(restored.days) > 0 ? Number(restored.days) : 1;
        } else {
            window.currentDays = 1;
            contenedor.innerHTML += `
                <div class="message-wrapper incoming">
                    <i class="fas fa-robot chat-avatar"></i>
                    <div class="message-bubble">¡Hola! Soy tu asistente de PRO AUDIO. Puedo ayudarte a estimar una cotización con base en nuestros servicios. Elige opciones del catálogo para comenzar.</div>
                </div>
            `;
            try {
                const response = await fetch("{{ route('chat.enviar') }}", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": "{{ csrf_token() }}"
                    },
                    body: JSON.stringify({ mensaje: "" })
                });
                const data = await response.json();
                if (Number.isInteger(data.days) && data.days > 0) {
                    window.currentDays = data.days;
                } else {
                    window.currentDays = 1;
                }
                if (Array.isArray(data.optionGroups) && data.optionGroups.length) {
                    renderOptionGroups(data.optionGroups, data.seleccionesPrevias || []);
                }
            } catch (e) {}
        }
        saveChatState();
        contenedor.scrollTop = contenedor.scrollHeight;
    });

    async function enviarMensaje() {
        const input = document.getElementById('mensaje');
        const mensaje = input.value.trim();
        const contenedor = document.getElementById('messages-container');

        if (!mensaje) return;

        // Detectar si el mensaje contiene días y buscar checkboxes marcados en el chat actual
        const diasMatch = mensaje.match(/(\d+)\s*d[ií]as?/i);
        const diasDetectados = diasMatch ? parseInt(diasMatch[1]) : 0;
        
        // Buscar checkboxes marcados en las opciones actuales del chat
        const checkboxesMarcados = Array.from(contenedor.querySelectorAll('input[name="subservicio"]:checked'));
        const seleccionadosDelChat = checkboxesMarcados.map(cb => Number(cb.value));
        
        // Si hay días detectados y hay checkboxes marcados, enviar la selección para calcular
        let bodyData = { mensaje };
        if (diasDetectados > 0 && seleccionadosDelChat.length > 0) {
            bodyData = {
                mensaje: mensaje,
                seleccion: seleccionadosDelChat,
                dias: diasDetectados
            };
        }

        // Mostrar mensaje del usuario
        contenedor.innerHTML += `
            <div class="message-wrapper outgoing">
                <div class="message-bubble">${mensaje}</div>
                <i class="fas fa-user-circle chat-avatar"></i>
            </div>
        `;
        input.value = '';
        contenedor.scrollTop = contenedor.scrollHeight;
        saveChatState();

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
                body: JSON.stringify(bodyData)
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
                if (Number.isInteger(data.days) && data.days > 0) {
                    window.currentDays = data.days;
                } else {
                    window.currentDays = 1;
                }
                if (Array.isArray(data.optionGroups) && data.optionGroups.length) {
                    renderOptionGroups(data.optionGroups, data.seleccionesPrevias || []);
                }
                if (Array.isArray(data.actions) && data.actions.length) {
                    renderActions(data.actions);
                }
                saveChatState();
            } else {
                contenedor.innerHTML += `
                    <div class="message-wrapper incoming">
                        <i class="fas fa-robot chat-avatar"></i>
                        <div class="message-bubble text-red-500">Error: ${data.error}</div>
                    </div>
                `;
                saveChatState();
            }

        } catch (error) {
            typing.remove();
            contenedor.innerHTML += `
                <div class="message-wrapper incoming">
                    <i class="fas fa-robot chat-avatar"></i>
                    <div class="message-bubble text-red-500">Error al conectar con el servidor.</div>
                </div>
            `;
            saveChatState();
        }

        contenedor.scrollTop = contenedor.scrollHeight;
    }

    function renderOptionGroups(groups, seleccionesPrevias = []) {
        const contenedor = document.getElementById('messages-container');
        const wrapper = document.createElement('div');
        wrapper.classList.add('message-wrapper', 'incoming');
        const d = window.currentDays && Number(window.currentDays) > 0 ? Number(window.currentDays) : 1;
        // Convertir seleccionesPrevias a array de números si no lo es
        const seleccionesIds = Array.isArray(seleccionesPrevias) 
            ? seleccionesPrevias.map(id => Number(id)) 
            : [];
        const groupsHtml = groups.map(g => `
            <div style="margin-bottom:8px;">
                <div style="font-weight:600;margin-bottom:4px;">${g.servicio}</div>
                ${g.items.map(o => {
                    const base = Number(o.precio) || 0;
                    const subtotal = base * d;
                    const linea = d > 1
                        ? `${o.nombre} — $${base.toLocaleString('es-CO')} × ${d} = $${subtotal.toLocaleString('es-CO')}`
                        : `${o.nombre} — $${base.toLocaleString('es-CO')}`;
                    const estaSeleccionado = seleccionesIds.includes(Number(o.id));
                    const checkedAttr = estaSeleccionado ? 'checked' : '';
                    const estiloSeleccionado = estaSeleccionado ? 'style="background-color: #f0f0f0; padding: 4px; border-radius: 4px;"' : '';
                    return `
                    <label style="display:flex;align-items:center;gap:8px;margin:4px 0;" ${estiloSeleccionado}>
                        <input type="checkbox" name="subservicio" value="${o.id}" ${checkedAttr}>
                        <span>${linea}${estaSeleccionado ? ' <small style="color: #059669;">(ya en cotización)</small>' : ''}</span>
                    </label>`;
                }).join('')}
            </div>
        `).join('');

        wrapper.innerHTML = `
            <i class="fas fa-robot chat-avatar"></i>
            <div class="message-bubble">
                <div id="opciones-lista">
                    ${groupsHtml}
                </div>
                <button type="button" id="confirmar-opciones" class="send-btn" style="margin-top:8px; background-color: #000; color: #fff; border: 1px solid #000;">
                    Confirmar selección
                </button>
            </div>
        `;
        contenedor.appendChild(wrapper);
        contenedor.scrollTop = contenedor.scrollHeight;
        saveChatState();
    }

    function renderActions(actions) {
        const contenedor = document.getElementById('messages-container');
        const wrap = document.createElement('div');
        wrap.classList.add('message-wrapper', 'incoming');
        wrap.innerHTML = `
            <i class="fas fa-robot chat-avatar"></i>
            <div class="message-bubble">
                ${actions.map(a => `
                    <button type="button" data-action-id="${a.id}" class="send-btn" style="margin-right:8px;margin-top:6px; background-color: #000; color: #fff; border: 1px solid #000;">${a.label}</button>
                `).join('')}
            </div>
        `;
        contenedor.appendChild(wrap);
        saveChatState();

        contenedor.scrollTop = contenedor.scrollHeight;
    }

    // Delegación de eventos para soportar elementos restaurados
    document.getElementById('messages-container').addEventListener('click', async (ev) => {
        const contenedor = document.getElementById('messages-container');
        const el = ev.target;
        if (!(el instanceof Element)) return;

        if (el.id === 'confirmar-opciones') {
            const bubble = el.closest('.message-bubble');
            const seleccion = Array.from(bubble.querySelectorAll('input[name="subservicio"]:checked')).map(i => Number(i.value));
            if (!seleccion.length) { alert('Selecciona al menos un sub-servicio.'); return; }
            const typing = document.createElement('div');
            typing.classList.add('message-wrapper', 'incoming');
            typing.innerHTML = `
                <i class="fas fa-robot chat-avatar"></i>
                <div class="message-bubble">Calculando cotización...</div>
            `;
            contenedor.appendChild(typing);
            contenedor.scrollTop = contenedor.scrollHeight;
            saveChatState();
            try {
                const response = await fetch("{{ route('chat.enviar') }}", {
                    method: "POST",
                    headers: { "Content-Type": "application/json", "X-CSRF-TOKEN": "{{ csrf_token() }}" },
                    body: JSON.stringify({ seleccion, dias: window.currentDays || 1 })
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
                    if (Number.isInteger(data.days) && data.days > 0) { window.currentDays = data.days; }
                    if (Array.isArray(data.actions)) { renderActions(data.actions); }
                    saveChatState();
                }
            } catch (_) {
                typing.remove();
                contenedor.innerHTML += `
                    <div class="message-wrapper incoming">
                        <i class="fas fa-robot chat-avatar"></i>
                        <div class="message-bubble text-red-500">Error al calcular la cotización.</div>
                    </div>
                `;
                saveChatState();
            }
            contenedor.scrollTop = contenedor.scrollHeight;
        }

        if (el.matches('button[data-action-id]')) {
            const id = el.getAttribute('data-action-id');
            if (id === 'add_more') {
                const typing = document.createElement('div');
                typing.classList.add('message-wrapper', 'incoming');
                typing.innerHTML = `
                    <i class="fas fa-robot chat-avatar"></i>
                    <div class="message-bubble">Buscando opciones adicionales...</div>
                `;
                contenedor.appendChild(typing);
                contenedor.scrollTop = contenedor.scrollHeight;
                try {
                    const response = await fetch("{{ route('chat.enviar') }}", {
                        method: "POST",
                        headers: { "Content-Type": "application/json", "X-CSRF-TOKEN": "{{ csrf_token() }}" },
                        body: JSON.stringify({ mensaje: "catalogo" })
                    });
                    
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    
                    const data = await response.json();
                    typing.remove();
                    
                    if (data.respuesta) {
                        contenedor.innerHTML += `
                            <div class="message-wrapper incoming">
                                <i class="fas fa-robot chat-avatar"></i>
                                <div class="message-bubble">${data.respuesta}</div>
                            </div>
                        `;
                    }
                    if (Array.isArray(data.optionGroups) && data.optionGroups.length) {
                        renderOptionGroups(data.optionGroups, data.seleccionesPrevias || []);
                    }
                    contenedor.scrollTop = contenedor.scrollHeight;
                    saveChatState();
                } catch (error) {
                    typing.remove();
                    console.error('Error al cargar opciones adicionales:', error);
                    contenedor.innerHTML += `
                        <div class="message-wrapper incoming">
                            <i class="fas fa-robot chat-avatar"></i>
                            <div class="message-bubble text-red-500">Error al cargar opciones adicionales. Por favor, intenta de nuevo.</div>
                        </div>
                    `;
                    contenedor.scrollTop = contenedor.scrollHeight;
                    saveChatState();
                }
            }
            if (id === 'clear') {
                const typing = document.createElement('div');
                typing.classList.add('message-wrapper', 'incoming');
                typing.innerHTML = `
                    <i class="fas fa-robot chat-avatar"></i>
                    <div class="message-bubble">Limpiando cotización...</div>
                `;
                contenedor.appendChild(typing);
                contenedor.scrollTop = contenedor.scrollHeight;
                try {
                    const response = await fetch("{{ route('chat.enviar') }}", {
                        method: "POST",
                        headers: { "Content-Type": "application/json", "X-CSRF-TOKEN": "{{ csrf_token() }}" },
                        body: JSON.stringify({ limpiar_cotizacion: true })
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
                        contenedor.scrollTop = contenedor.scrollHeight;
                        clearChatState();
                        saveChatState();
                        // Mostrar catálogo después de limpiar
                        setTimeout(async () => {
                            try {
                                const response2 = await fetch("{{ route('chat.enviar') }}", {
                                    method: "POST",
                                    headers: { "Content-Type": "application/json", "X-CSRF-TOKEN": "{{ csrf_token() }}" },
                                    body: JSON.stringify({ mensaje: "" })
                                });
                                const data2 = await response2.json();
                                if (Array.isArray(data2.optionGroups) && data2.optionGroups.length) {
                                    renderOptionGroups(data2.optionGroups);
                                }
                            } catch (_) {}
                        }, 500);
                    }
                } catch (_) {
                    typing.remove();
                    contenedor.innerHTML += `
                        <div class="message-wrapper incoming">
                            <i class="fas fa-robot chat-avatar"></i>
                            <div class="message-bubble text-red-500">Error al limpiar la cotización.</div>
                        </div>
                    `;
                    contenedor.scrollTop = contenedor.scrollHeight;
                    saveChatState();
                }
            }
            if (id === 'finish') {
                const typing = document.createElement('div');
                typing.classList.add('message-wrapper', 'incoming');
                typing.innerHTML = `
                    <i class="fas fa-robot chat-avatar"></i>
                    <div class="message-bubble">Procesando...</div>
                `;
                contenedor.appendChild(typing);
                contenedor.scrollTop = contenedor.scrollHeight;
                try {
                    const response = await fetch("{{ route('chat.enviar') }}", {
                        method: "POST",
                        headers: { "Content-Type": "application/json", "X-CSRF-TOKEN": "{{ csrf_token() }}" },
                        body: JSON.stringify({ terminar_cotizacion: true })
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
                        contenedor.scrollTop = contenedor.scrollHeight;
                        // Limpiar estado y mostrar botón para refrescar
                        if (data.limpiar_chat) {
                            clearChatState();
                            // Mostrar botón para refrescar chat
                            const refreshWrapper = document.createElement('div');
                            refreshWrapper.classList.add('message-wrapper', 'incoming');
                            refreshWrapper.innerHTML = `
                                <i class="fas fa-robot chat-avatar"></i>
                                <div class="message-bubble">
                                    <button type="button" id="refrescar-chat" class="send-btn" style="background-color: #000; color: #fff; border: 1px solid #000; width: 100%;">
                                        <i class="fas fa-redo"></i> Refrescar chat
                                    </button>
                                </div>
                            `;
                            contenedor.appendChild(refreshWrapper);
                            contenedor.scrollTop = contenedor.scrollHeight;
                            saveChatState();
                            
                            // Agregar listener al botón de refrescar
                            refreshWrapper.querySelector('#refrescar-chat').addEventListener('click', function() {
                                const refreshBtn = this;
                                refreshBtn.disabled = true;
                                refreshBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Esperando...';
                                
                                // Reiniciar el chat limpiando el contenedor y mostrando mensaje inicial
                                contenedor.innerHTML = `
                                    <div class="message-wrapper incoming">
                                        <i class="fas fa-robot chat-avatar"></i>
                                        <div class="message-bubble">¡Hola! Soy tu asistente de PRO AUDIO. Puedo ayudarte a estimar una cotización con base en nuestros servicios. Elige opciones del catálogo para comenzar.</div>
                                    </div>
                                `;
                                window.currentDays = 1;
                                
                                // Cargar catálogo inicial
                                fetch("{{ route('chat.enviar') }}", {
                                    method: "POST",
                                    headers: { "Content-Type": "application/json", "X-CSRF-TOKEN": "{{ csrf_token() }}" },
                                    body: JSON.stringify({ mensaje: "" })
                                })
                                .then(res => res.json())
                                .then(data2 => {
                                    if (Array.isArray(data2.optionGroups) && data2.optionGroups.length) {
                                        renderOptionGroups(data2.optionGroups, data2.seleccionesPrevias || []);
                                    }
                                })
                                .catch(_ => {});
                                saveChatState();
                                contenedor.scrollTop = contenedor.scrollHeight;
                            });
                        }
                        saveChatState();
                    }
                } catch (_) {
                    typing.remove();
                    contenedor.innerHTML += `
                        <div class="message-wrapper incoming">
                            <i class="fas fa-robot chat-avatar"></i>
                            <div class="message-bubble">Gracias por tu interés. Contacta con un trabajador mediante este correo <strong>ejemplo@gmail.com</strong>.</div>
                        </div>
                    `;
                    contenedor.scrollTop = contenedor.scrollHeight;
                    clearChatState();
                    
                    // Mostrar botón para refrescar chat
                    const refreshWrapper = document.createElement('div');
                    refreshWrapper.classList.add('message-wrapper', 'incoming');
                    refreshWrapper.innerHTML = `
                        <i class="fas fa-robot chat-avatar"></i>
                        <div class="message-bubble">
                            <button type="button" id="refrescar-chat-error" class="send-btn" style="background-color: #000; color: #fff; border: 1px solid #000; width: 100%;">
                                <i class="fas fa-redo"></i> Refrescar chat
                            </button>
                        </div>
                    `;
                    contenedor.appendChild(refreshWrapper);
                    contenedor.scrollTop = contenedor.scrollHeight;
                    saveChatState();
                    
                    // Agregar listener al botón de refrescar
                    refreshWrapper.querySelector('#refrescar-chat-error').addEventListener('click', function() {
                        const refreshBtn = this;
                        refreshBtn.disabled = true;
                        refreshBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Esperando...';
                        
                        contenedor.innerHTML = `
                            <div class="message-wrapper incoming">
                                <i class="fas fa-robot chat-avatar"></i>
                                <div class="message-bubble">¡Hola! Soy tu asistente de PRO AUDIO. Puedo ayudarte a estimar una cotización con base en nuestros servicios. Elige opciones del catálogo para comenzar.</div>
                            </div>
                        `;
                        window.currentDays = 1;
                        fetch("{{ route('chat.enviar') }}", {
                            method: "POST",
                            headers: { "Content-Type": "application/json", "X-CSRF-TOKEN": "{{ csrf_token() }}" },
                            body: JSON.stringify({ mensaje: "" })
                        })
                        .then(res => res.json())
                        .then(data2 => {
                            if (Array.isArray(data2.optionGroups) && data2.optionGroups.length) {
                                renderOptionGroups(data2.optionGroups, data2.seleccionesPrevias || []);
                            }
                        })
                        .catch(_ => {});
                        saveChatState();
                        contenedor.scrollTop = contenedor.scrollHeight;
                    });
                }
            }
        }
    });
    </script>
</body>
</html>
