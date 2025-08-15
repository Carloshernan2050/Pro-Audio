<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-5">
    <h2>Bienvenido, {{ $usuario_nombre }}</h2>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <p>Este es tu panel de control.</p>

    <!-- Botón de cerrar sesión -->
    <a href="{{ route('usuarios.cerrarSesion') }}" class="btn btn-danger">Cerrar Sesión</a>
</div>
</body>
</html>
