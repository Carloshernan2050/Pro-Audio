<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clave Administrador - PRO AUDIO</title>
    @vite('resources/css/app.css')
</head>
<body>
    <div class="dashboard-container">
        <header class="top-bar">
            <h1>PRO AUDIO</h1>
        </header>
        <main class="main-content" style="display:flex; align-items:center; justify-content:center;">
            <div class="card" style="background:#121b2f; padding:32px; border-radius:16px; width:100%; max-width:480px; color:#e6edf3; box-shadow:0 10px 30px rgba(0,0,0,0.4);">
                <h2 style="text-align:center; margin-bottom:16px;">Validar acceso de Administrador</h2>
                @if(session('error'))
                    <div class="alert" style="padding:10px; border-radius:8px; background:#3d1e1f; color:#ffb4b4; margin-bottom:12px;">{{ session('error') }}</div>
                @endif
                <form method="POST" action="{{ route('admin.key.verify') }}">
                    @csrf
                    <label for="admin_key">Clave interna</label>
                    <input type="password" id="admin_key" name="admin_key" class="form-control" autocomplete="new-password" style="margin-top:8px;">
                    <button type="submit" class="btn" style="margin-top:12px; width:100%; padding:12px 16px; border-radius:10px; border:none; cursor:pointer; font-weight:600; background:#1f6feb; color:#fff;">Confirmar</button>
                </form>
                <a href="{{ route('role.select') }}" class="btn" style="display:block; text-align:center; margin-top:8px; padding:10px; border-radius:10px; background:#6e7681; color:#fff;">Volver</a>
            </div>
        </main>
    </div>
</body>
</html>


