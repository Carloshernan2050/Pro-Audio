<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seleccionar Rol - PRO AUDIO</title>
    @vite('resources/css/app.css')
    <script>
        function chooseRole(role) {
            document.getElementById('role').value = role;
            document.getElementById('role-form').submit();
        }
    </script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">
</head>
<body>
    <div class="dashboard-container">
        <header class="top-bar">
            <h1>PRO AUDIO</h1>
        </header>

        <main class="main-content" style="display:flex; align-items:center; justify-content:center;">
            <div class="card" style="background:#121b2f; padding:32px; border-radius:16px; width:100%; max-width:560px; color:#e6edf3; box-shadow:0 10px 30px rgba(0,0,0,0.4);">
                <h1 class="title" style="font-size:24px; margin-bottom:20px; text-align:center;">Selecciona tu rol</h1>

                @if(session('error'))
                    <div class="alert error" style="padding:10px 12px; border-radius:8px; margin-bottom:12px; background:#3d1e1f; color:#ffb4b4;">{{ session('error') }}</div>
                @endif
                @if(session('success'))
                    <div class="alert success" style="padding:10px 12px; border-radius:8px; margin-bottom:12px; background:#1c3b2a; color:#a7f3d0;">{{ session('success') }}</div>
                @endif

                <form id="role-form" method="POST" action="{{ route('role.set') }}">
                    @csrf
                    <input type="hidden" name="role" id="role" value="">

                    <div class="buttons" style="display:grid; grid-template-columns:1fr; gap:12px; margin-top:12px;">
                        <button type="button" class="btn btn-admin" style="padding:12px 16px; border-radius:10px; border:none; cursor:pointer; font-weight:600; background:#1f6feb; color:white;" onclick="chooseRole('Administrador')">Admin</button>
                        <button type="button" class="btn btn-client" style="padding:12px 16px; border-radius:10px; border:none; cursor:pointer; font-weight:600; background:#238636; color:white;" onclick="chooseRole('Cliente')">Cliente</button>
                        <button type="button" class="btn btn-guest" style="padding:12px 16px; border-radius:10px; border:none; cursor:pointer; font-weight:600; background:#6e7681; color:white;" onclick="chooseRole('Invitado')">Invitado</button>
                    </div>

                </form>

                @if(session('role'))
                    <form method="POST" action="{{ route('role.clear') }}" style="margin-top:16px;">
                        @csrf
                        <button class="btn" type="submit" style="padding:12px 16px; border-radius:10px; border:none; cursor:pointer; font-weight:600; background:#6e7681; color:white;">Salir del rol ({{ session('role') }})</button>
                    </form>
                @endif
            </div>
        </main>
    </div>
</body>
</html>


