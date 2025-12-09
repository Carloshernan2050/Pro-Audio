<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chatbot</title>

    {{-- Enlace a tu archivo CSS principal --}}
    @vite('resources/css/app.css')
    {{-- Estilos específicos SOLO para la vista del chatbot --}}
    @vite('resources/css/chatbot.css')

    {{-- Importación de íconos de Font Awesome --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    {{-- Contenido puro del chat, sin topbar ni sidebar, ideal para iframe/widget --}}
    <div class="page-container">
        <div class="chatbot-container">
            {{-- Encabezado del chat (puedes quitarlo si deseas solo el área de mensajes) --}}
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

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            let data;
            try {
                data = await response.json();
            } catch (jsonError) {
                throw new Error('Error al parsear la respuesta del servidor');
            }
            typing.remove();

            try {
                if (data && data.respuesta !== undefined && data.respuesta !== null) {
                    const safeRespuesta = (typeof data.respuesta === 'string')
                        ? data.respuesta
                        : (() => { try { return JSON.stringify(data.respuesta); } catch(_) { return String(data.respuesta); } })();
                    contenedor.innerHTML += `
                        <div class="message-wrapper incoming">
                            <i class="fas fa-robot chat-avatar"></i>
                            <div class="message-bubble">${safeRespuesta}</div>
                        </div>
                    `;
                    if (Array.isArray(data.tokenHints) && data.tokenHints.length && typeof data.originalMensaje === 'string') {
                        try { renderTokenCorrection(data.originalMensaje, data.tokenHints[0].token, data.tokenHints[0].sugerencias); } catch (e) { console.error('renderTokenCorrection error', e); }
                    } else if (data.sugerencias) { renderSuggestions(data.sugerencias); }
                    if (Number.isInteger(data.days) && data.days > 0) {
                        window.currentDays = data.days;
                    } else {
                        window.currentDays = 1;
                    }
                    if (Array.isArray(data.optionGroups) && data.optionGroups.length) {
                        try { renderOptionGroups(data.optionGroups, data.seleccionesPrevias || []); } catch (e) { console.error('renderOptionGroups error', e); }
                    }
                    if (Array.isArray(data.actions) && data.actions.length) {
                        try { renderActions(data.actions); } catch (e) { console.error('renderActions error', e); }
                    }
                    if (data.cotizacion && data.cotizacion.items) {
                        try { renderCotizacion(data.cotizacion); } catch (e) { console.error('renderCotizacion error', e); }
                    }
                    saveChatState();
                } else {
                    contenedor.innerHTML += `
                        <div class="message-wrapper incoming">
                            <i class="fas fa-robot chat-avatar"></i>
                            <div class="message-bubble text-red-500">Error: ${data && data.error ? data.error : 'Respuesta inválida'}</div>
                        </div>
                    `;
                    saveChatState();
                }
            } catch (_) {
                contenedor.innerHTML += `
                    <div class="message-wrapper incoming">
                        <i class="fas fa-robot chat-avatar"></i>
                        <div class="message-bubble text-red-500">Ocurrió un error mostrando la respuesta.</div>
                    </div>
                `;
                saveChatState();
            }

        } catch (error) {
            typing.remove();
            console.error('Error en enviarMensaje:', error);
            let errorMessage = 'Error al conectar con el servidor.';
            if (error.message) {
                errorMessage += ` (${error.message})`;
            }
            contenedor.innerHTML += `
                <div class="message-wrapper incoming">
                    <i class="fas fa-robot chat-avatar"></i>
                    <div class="message-bubble text-red-500">${errorMessage}</div>
                </div>
            `;
            saveChatState();
        }

        contenedor.scrollTop = contenedor.scrollHeight;
    }

    // Renderizar sugerencias (top-level)
    function renderSuggestions(sugerencias) {
        const contenedor = document.getElementById('messages-container');
        const wrap = document.createElement('div');
        wrap.classList.add('message-wrapper', 'incoming');
        const avatar = document.createElement('i');
        avatar.classList.add('fas','fa-robot','chat-avatar');
        const bubble = document.createElement('div');
        bubble.classList.add('message-bubble');

        try {
            let list = [];
            if (Array.isArray(sugerencias)) {
                list = sugerencias;
            } else if (typeof sugerencias === 'string') {
                list = [sugerencias];
            } else if (sugerencias && typeof sugerencias === 'object') {
                list = Object.values(sugerencias).slice(0, 5);
            }
            // Si no hay sugerencias de la BD, no mostrar fallback hardcodeado
            if (!Array.isArray(list) || list.length === 0) {
                list = [];
            }

            list.forEach((s) => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.dataset.suggest = String(s);
                btn.className = 'send-btn';
                btn.style.marginRight = '8px';
                btn.style.marginTop = '6px';
                btn.style.backgroundColor = '#fff';
                btn.style.color = '#000';
                btn.style.border = '1px solid #000';
                btn.textContent = String(s);
                bubble.appendChild(btn);
            });
            // Botón para mostrar lista nuevamente (refrescar)
            const again = document.createElement('button');
            again.type = 'button';
            again.id = 'show-list-again';
            again.className = 'send-btn';
            again.style.marginLeft = '8px';
            again.style.marginTop = '10px';
            again.style.backgroundColor = '#000';
            again.style.color = '#fff';
            again.style.border = '1px solid #000';
            again.textContent = 'Mostrar lista nuevamente';
            bubble.appendChild(again);
        } catch (e) {
            console.error('renderSuggestions error', e);
            bubble.textContent = 'No pude mostrar sugerencias.';
        }

        wrap.appendChild(avatar);
        wrap.appendChild(bubble);
        contenedor.appendChild(wrap);
        contenedor.scrollTop = contenedor.scrollHeight;
        saveChatState();
    }

    // Renderizar cotización
    function renderCotizacion(cotizacion) {
        const contenedor = document.getElementById('messages-container');
        const wrapper = document.createElement('div');
        wrapper.classList.add('message-wrapper', 'incoming');
        const avatar = document.createElement('i');
        avatar.classList.add('fas', 'fa-robot', 'chat-avatar');
        const bubble = document.createElement('div');
        bubble.classList.add('message-bubble');

        if (!cotizacion || !Array.isArray(cotizacion.items) || cotizacion.items.length === 0) {
            return;
        }

        const dias = Number(cotizacion.dias) || 1;
        const total = Number(cotizacion.total) || 0;

        let itemsHtml = '<div style="margin-top: 12px; padding-top: 12px; border-top: 1px solid #e5e7eb;">';
        cotizacion.items.forEach(item => {
            const nombre = item.nombre || 'Sin nombre';
            const precioUnitario = Number(item.precio_unitario) || 0;
            const subtotal = Number(item.subtotal) || precioUnitario * dias;
            
            itemsHtml += `
                <div style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #f3f4f6;">
                    <div>
                        <div style="font-weight: 500;">${nombre}</div>
                        <div style="font-size: 0.875rem; color: #6b7280;">
                            $${precioUnitario.toLocaleString('es-CO')} ${dias > 1 ? `× ${dias} días` : ''}
                        </div>
                    </div>
                    <div style="font-weight: 600; color: #059669;">
                        $${subtotal.toLocaleString('es-CO')}
                    </div>
                </div>
            `;
        });
        itemsHtml += '</div>';

        itemsHtml += `
            <div style="margin-top: 12px; padding-top: 12px; border-top: 2px solid #000; display: flex; justify-content: space-between; align-items: center;">
                <div style="font-weight: 700; font-size: 1.125rem;">Total:</div>
                <div style="font-weight: 700; font-size: 1.25rem; color: #059669;">
                    $${total.toLocaleString('es-CO')}
                </div>
            </div>
        `;

        bubble.innerHTML = itemsHtml;
        wrapper.appendChild(avatar);
        wrapper.appendChild(bubble);
        contenedor.appendChild(wrapper);
        contenedor.scrollTop = contenedor.scrollHeight;
        saveChatState();
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

    // Render subrayando el token y ofreciendo reemplazos
    function renderTokenCorrection(originalMensaje, token, sugerencias) {
        const contenedor = document.getElementById('messages-container');
        const wrap = document.createElement('div');
        wrap.classList.add('message-wrapper', 'incoming');
        const avatar = document.createElement('i');
        avatar.classList.add('fas','fa-robot','chat-avatar');
        const bubble = document.createElement('div');
        bubble.classList.add('message-bubble');

        const esc = (s) => String(s).replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        let highlighted = originalMensaje;
        try {
            const re = new RegExp(esc(token), 'i');
            highlighted = originalMensaje.replace(re, (m) => `<span style="text-decoration: underline; text-decoration-color: #ef4444; text-decoration-thickness: 2px;">${m}</span>`);
        } catch (_) {}

        const header = document.createElement('div');
        header.innerHTML = `No entendí la palabra "<strong>${token}</strong>" en tu mensaje:<br>${highlighted}<br><small>Tal vez quisiste decir:</small>`;
        bubble.appendChild(header);

        let list = [];
        if (Array.isArray(sugerencias)) list = sugerencias; else if (typeof sugerencias === 'string') list = [sugerencias];
        // Si no hay sugerencias, no mostrar fallback hardcodeado
        list.slice(0, 6).forEach((s) => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.dataset.replaceToken = token;
            btn.dataset.replaceWith = String(s);
            btn.dataset.originalMensaje = originalMensaje;
            btn.className = 'send-btn';
            btn.style.marginRight = '8px';
            btn.style.marginTop = '6px';
            btn.style.backgroundColor = '#fff';
            btn.style.color = '#000';
            btn.style.border = '1px solid #000';
            btn.textContent = String(s);
            bubble.appendChild(btn);
        });
        // Botón para mostrar lista nuevamente (refrescar)
        const again = document.createElement('button');
        again.type = 'button';
        again.id = 'show-list-again';
        again.className = 'send-btn';
        again.style.marginLeft = '8px';
        again.style.marginTop = '10px';
        again.style.backgroundColor = '#000';
        again.style.color = '#fff';
        again.style.border = '1px solid #000';
        again.textContent = 'Mostrar lista nuevamente';
        bubble.appendChild(again);

        wrap.appendChild(avatar);
        wrap.appendChild(bubble);
        contenedor.appendChild(wrap);
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
                ${actions.map(a => {
                    const meta = a.meta ? ` data-meta='${JSON.stringify(a.meta).replace(/'/g, "&apos;")}'` : '';
                    return `<button type="button" data-action-id="${a.id}"${meta} class="send-btn" style="margin-right:8px;margin-top:6px; background-color: #000; color: #fff; border: 1px solid #000;">${a.label}</button>`;
                }).join('')}
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
            if (!seleccion.length) { 
                if (typeof window.customAlert === 'function') {
                    window.customAlert('Por favor, selecciona al menos un sub-servicio.');
                } else {
                    alert('Por favor, selecciona al menos un sub-servicio.');
                }
                return; 
            }
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
                    if (data.cotizacion && data.cotizacion.items) {
                        try { renderCotizacion(data.cotizacion); } catch (e) { console.error('renderCotizacion error', e); }
                    }
                    if (Array.isArray(data.actions) && data.actions.length) {
                        try { renderActions(data.actions); } catch (e) { console.error('renderActions error', e); }
                    }
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

        // (renderSuggestions se define a nivel superior)

        if (el.matches('button[data-action-id]')) {
            const id = el.getAttribute('data-action-id');
            if (id === 'confirm_intent') {
                let meta = el.getAttribute('data-meta');
                try { meta = meta ? JSON.parse(meta.replace(/&apos;/g, "'")) : {}; } catch(_) { meta = {}; }
                const payload = { confirm_intencion: true };
                if (meta && Array.isArray(meta.intenciones)) payload.intenciones = meta.intenciones;
                if (meta && Number.isInteger(meta.dias)) payload.dias = meta.dias;
                const typing = document.createElement('div');
                typing.classList.add('message-wrapper','incoming');
                typing.innerHTML = `<i class="fas fa-robot chat-avatar"></i><div class="message-bubble">Cargando opciones...</div>`;
                contenedor.appendChild(typing);
                try {
                    const res = await fetch("{{ route('chat.enviar') }}", { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }, body: JSON.stringify(payload) });
                    const data = await res.json();
                    typing.remove();
                    if (data && data.respuesta) {
                        contenedor.innerHTML += `<div class="message-wrapper incoming"><i class=\"fas fa-robot chat-avatar\"></i><div class=\"message-bubble\">${data.respuesta}</div></div>`;
                        if (Array.isArray(data.optionGroups)) renderOptionGroups(data.optionGroups, data.seleccionesPrevias || []);
                        if (Array.isArray(data.actions)) renderActions(data.actions);
                        if (Number.isInteger(data.days) && data.days > 0) window.currentDays = data.days;
                        saveChatState();
                    }
                } catch(_) { typing.remove(); }
                return;
            }
            if (id === 'reject_intent') {
                const typing = document.createElement('div');
                typing.classList.add('message-wrapper','incoming');
                typing.innerHTML = `<i class="fas fa-robot chat-avatar"></i><div class="message-bubble">Mostrando catálogo...</div>`;
                contenedor.appendChild(typing);
                try {
                    const res = await fetch("{{ route('chat.enviar') }}", { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }, body: JSON.stringify({ mensaje: '' }) });
                    const data = await res.json();
                    typing.remove();
                    if (data && Array.isArray(data.optionGroups)) renderOptionGroups(data.optionGroups, data.seleccionesPrevias || []);
                } catch(_) { typing.remove(); }
                return;
            }
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
                            <div class="message-bubble">Gracias por tu interés. Contacta con un trabajador en <a href="https://wa.link/isz77x" target="_blank" rel="noopener noreferrer">https://wa.link/isz77x</a>.</div>
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

        // Refrescar chat y mostrar lista nuevamente
        if (el.id === 'show-list-again') {
            // limpiar estado y mostrar catálogo
            clearChatState();
            const contenedor = document.getElementById('messages-container');
            contenedor.innerHTML += `
                <div class="message-wrapper incoming">
                    <i class="fas fa-robot chat-avatar"></i>
                    <div class="message-bubble">Cargando catálogo...</div>
                </div>
            `;
            saveChatState();
            try {
                const response = await fetch("{{ route('chat.enviar') }}", {
                    method: "POST",
                    headers: { "Content-Type": "application/json", "X-CSRF-TOKEN": "{{ csrf_token() }}" },
                    body: JSON.stringify({ mensaje: "" })
                });
                const data = await response.json();
                if (data && Array.isArray(data.optionGroups)) {
                    renderOptionGroups(data.optionGroups, data.seleccionesPrevias || []);
                }
                saveChatState();
            } catch (_) {
                contenedor.innerHTML += `
                    <div class="message-wrapper incoming">
                        <i class="fas fa-robot chat-avatar"></i>
                        <div class="message-bubble text-red-500">No se pudo cargar el catálogo.</div>
                    </div>
                `;
                saveChatState();
            }
            contenedor.scrollTop = contenedor.scrollHeight;
        }

        if (el.matches('button[data-suggest]')) {
            const sugerencia = el.getAttribute('data-suggest');
            if (!sugerencia) return;
            // Mostrar como si el usuario hubiera escrito la sugerencia
            const contenedor = document.getElementById('messages-container');
            contenedor.innerHTML += `
                <div class="message-wrapper outgoing">
                    <div class="message-bubble">${sugerencia}</div>
                    <i class="fas fa-user-circle chat-avatar"></i>
                </div>
            `;
            saveChatState();
            // Enviar mensaje con la sugerencia
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
                    headers: { "Content-Type": "application/json", "X-CSRF-TOKEN": "{{ csrf_token() }}" },
                    body: JSON.stringify({ mensaje: sugerencia, sugerencia_aplicada: true })
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
                    if (Array.isArray(data.optionGroups)) { renderOptionGroups(data.optionGroups, data.seleccionesPrevias || []); }
                    if (Array.isArray(data.actions)) { renderActions(data.actions); }
                    if (Array.isArray(data.sugerencias) && data.sugerencias.length) { renderSuggestions(data.sugerencias); }
                    if (Number.isInteger(data.days) && data.days > 0) { window.currentDays = data.days; }
                    saveChatState();
                }
            } catch (_) {
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

        if (el.matches('button[data-replace-token]')) {
            const token = el.getAttribute('data-replace-token');
            const rep = el.getAttribute('data-replace-with');
            const original = el.getAttribute('data-original-mensaje');
            if (!token || !rep || !original) return;
            // Reemplazar SOLO la primera coincidencia con límites de palabra
            const re = new RegExp('(^|\\b)'+token.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')+'(\\b|$)', 'i');
            const corrected = original.replace(re, (m, p1, p2) => `${p1}${rep}${p2}`);

            // Mostrar como mensaje del usuario
            const contenedor = document.getElementById('messages-container');
            contenedor.innerHTML += `
                <div class="message-wrapper outgoing">
                    <div class="message-bubble">${corrected}</div>
                    <i class="fas fa-user-circle chat-avatar"></i>
                </div>
            `;
            saveChatState();

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
                    headers: { "Content-Type": "application/json", "X-CSRF-TOKEN": "{{ csrf_token() }}" },
                    body: JSON.stringify({ mensaje: corrected, sugerencia_aplicada: true })
                });
                const data = await response.json();
                typing.remove();
                if (data && data.respuesta) {
                    contenedor.innerHTML += `
                        <div class="message-wrapper incoming">
                            <i class="fas fa-robot chat-avatar"></i>
                            <div class="message-bubble">${data.respuesta}</div>
                        </div>
                    `;
                    if (Array.isArray(data.optionGroups)) { renderOptionGroups(data.optionGroups, data.seleccionesPrevias || []); }
                    if (Array.isArray(data.actions)) { renderActions(data.actions); }
                    if (Array.isArray(data.tokenHints) && data.tokenHints.length && typeof data.originalMensaje === 'string') {
                        renderTokenCorrection(data.originalMensaje, data.tokenHints[0].token, data.tokenHints[0].sugerencias);
                    } else if (data.sugerencias) { renderSuggestions(data.sugerencias); }
                    if (Number.isInteger(data.days) && data.days > 0) { window.currentDays = data.days; }
                    saveChatState();
                }
            } catch (_) {
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
    });
    </script>
</body>
</html>
