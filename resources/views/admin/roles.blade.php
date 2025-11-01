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
    <a href="{{ route('inicio') }}" class="btn-back btn-back-fixed"><i class="fas fa-arrow-left"></i> Volver al inicio</a>
    <div class="roles-header">
        <h2 class="roles-title"><i class="fas fa-user-shield"></i> Gestión de Roles</h2>
    </div>
    <div class="roles-toolbar">
        <div class="roles-legend">
            <span class="legend-pill superadmin">Superadmin</span>
            <span class="legend-pill admin">Admin</span>
            <span class="legend-pill usuario">Usuario</span>
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
            <table class="roles-table">
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
                                @php $permitidos = ['Superadmin','Admin','Usuario']; @endphp
                                @foreach($roles as $r)
                                    @if(in_array($r->name, $permitidos, true))
                                        @php
                                            $has = collect(explode(',', (string)$u->roles))->filter()->contains($r->name);
                                        @endphp
                                        <label class="chip" data-role="{{ $r->name }}" title="{{ $r->name }}">
                                            <input type="checkbox" name="roles[]" value="{{ $r->id }}" {{ $has ? 'checked' : '' }}>
                                            {{ $r->name }}
                                        </label>
                                    @endif
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
