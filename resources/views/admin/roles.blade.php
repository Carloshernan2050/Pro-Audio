<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Roles</title>
    @vite('resources/css/app.css')
    @vite('resources/css/roles.css')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body>
<div class="dashboard-container roles-page" style="padding:20px;">
    <a href="{{ route('inicio') }}" class="btn-volver btn-back-fixed" title="Volver al inicio">
        <i class="fas fa-arrow-left"></i>
    </a>
    <div class="roles-header">
        <h2 class="roles-title"><i class="fas fa-user-shield"></i> Gestión de Roles</h2>
    </div>
    <div class="roles-toolbar">
        <div class="roles-legend">
            <span class="legend-pill superadmin">Superadmin</span>
            <span class="legend-pill admin">Admin</span>
            <span class="legend-pill usuario">Cliente</span>
        </div>
        <div class="roles-search">
            <i class="fas fa-search"></i>
            <input type="text" id="rolesSearch" class="search-input" placeholder="Buscar por nombre o correo...">
        </div>
    </div>
    <p class="roles-hint">Asigna uno o varios roles a cada persona. Usa la búsqueda para filtrar rápidamente.</p>
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="roles-card">
        <div class="roles-scroll">
            <table class="roles-table" aria-label="Listado de usuarios con sus roles asignados">
                <caption class="sr-only">Tabla con usuarios del sistema, sus correos y roles asignables.</caption>
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Usuario</th>
                    <th>Correo</th>
                    <th>Roles</th>
                    <th>Acción</th>
                </tr>
                </thead>
                <tbody>
                @foreach($usuarios as $u)
                    <tr>
                        <td>{{ $u->id }}</td>
                        <td>{{ $u->primer_nombre }} {{ $u->primer_apellido }}</td>
                        <td>{{ $u->correo }}</td>
                        <td>
                            <form id="rolesForm{{ $u->id }}" method="POST" action="{{ route('admin.roles.update') }}" class="roles-form">
                                @csrf
                                <input type="hidden" name="persona_id" value="{{ $u->id }}">
                                @php 
                                    $permitidos = ['Superadmin','Admin','Cliente']; 
                                    // Normalizar roles: convertir "Usuario" a "Cliente" si existe
                                    $rolesUsuario = collect(explode(',', (string)$u->roles))->map(function($role) {
                                        return trim($role) === 'Usuario' ? 'Cliente' : trim($role);
                                    })->filter()->unique()->values()->all();
                                    
                                    // Agrupar roles por nombre normalizado para evitar duplicados
                                    $rolesUnicos = [];
                                    $rolesYaMostrados = [];
                                @endphp
                                @foreach($roles as $r)
                                    @php
                                        // Normalizar nombre del rol
                                        $rolNombre = trim($r->name ?? '');
                                        if ($rolNombre === 'Usuario') {
                                            $rolNombre = 'Cliente';
                                        }
                                        
                                        // Si ya mostramos este rol, saltarlo
                                        if (in_array($rolNombre, $rolesYaMostrados, true)) {
                                            continue;
                                        }
                                        
                                        // Solo mostrar si está permitido y no se ha mostrado antes
                                        if (in_array($rolNombre, $permitidos, true) && !in_array($rolNombre, $rolesYaMostrados, true)) {
                                            $rolesYaMostrados[] = $rolNombre;
                                            $has = in_array($rolNombre, $rolesUsuario, true);
                                            $rolesUnicos[] = [
                                                'id' => $r->id,
                                                'name' => $rolNombre,
                                                'checked' => $has
                                            ];
                                        }
                                    @endphp
                                @endforeach
                                @foreach($rolesUnicos as $rolUnico)
                                    <label class="chip" data-role="{{ $rolUnico['name'] }}" title="{{ $rolUnico['name'] }}">
                                        <input type="checkbox" name="roles[]" value="{{ $rolUnico['id'] }}" {{ $rolUnico['checked'] ? 'checked' : '' }}>
                                        {{ $rolUnico['name'] }}
                                    </label>
                                @endforeach
                            </form>
                        </td>
                        <td>
                            <button type="submit" class="btn-guardar" form="rolesForm{{ $u->id }}"><i class="fas fa-save"></i> Guardar</button>
                            <span class="save-hint" aria-live="polite"></span>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <script>
    (function(){
        const input = document.getElementById('rolesSearch');
        const table = document.querySelector('.roles-table tbody');
        function norm(s){ return (s||'').toString().normalize('NFD').replace(/[\u0300-\u036f]/g,'').toLowerCase(); }
        function filter(){
            const q = norm(input.value);
            table.querySelectorAll('tr').forEach(tr => {
                const cols = tr.querySelectorAll('td');
                const text = norm((cols[1]?.innerText||'') + ' ' + (cols[2]?.innerText||''));
                tr.style.display = q && !text.includes(q) ? 'none' : '';
            });
        }
        if (input && table) {
            input.addEventListener('input', filter);
        }

        // feedback de guardado sin bloquear navegación
        document.querySelectorAll('.roles-form').forEach(form => {
            form.addEventListener('submit', function(){
                const hint = this.querySelector('.save-hint');
                if (hint){ hint.textContent = 'Guardando...'; setTimeout(()=>{ hint.textContent=''; }, 3000); }
            });
        });
    })();
    </script>
</div>
</body>
</html>
