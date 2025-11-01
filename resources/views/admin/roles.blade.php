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
    <h2 class="roles-title"><i class="fas fa-user-shield"></i> Gestión de Roles</h2>
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
                            <form method="POST" action="{{ route('admin.roles.update') }}" class="roles-form">
                                @csrf
                                <input type="hidden" name="persona_id" value="{{ $u->id }}">
                                @foreach($roles as $r)
                                    @php
                                        $has = collect(explode(',', (string)$u->roles))->filter()->contains($r->name);
                                    @endphp
                                    <label class="chip">
                                        <input type="checkbox" name="roles[]" value="{{ $r->id }}" {{ $has ? 'checked' : '' }}>
                                        {{ $r->name }}
                                    </label>
                                @endforeach
                                <button type="submit" class="btn-guardar"><i class="fas fa-save"></i> Guardar</button>
                            </form>
                        </td>
                        <td></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>
