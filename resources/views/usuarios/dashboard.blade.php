<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <style>
        /* Estilo opcional para el sidebar lateral */
        .sidebar {
            min-height: 100vh;
            background-color: #f8f9fa;
            border-right: 1px solid #ddd;
        }
        .sidebar a {
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <!-- SIDEBAR IZQUIERDO -->
        <div class="col-md-3 col-lg-2 sidebar p-3">
            <h5 class="mb-4">Menú</h5>
            <a href="{{ route('usuarios.perfil') }}" class="btn btn-outline-primary w-100">Perfil</a>
            <a href="{{ route('usuarios.dashboard') }}" class="btn btn-outline-primary w-100">Inicio</a>
            <a href="{{ route('usuarios.animacion') }}" class="btn btn-outline-primary w-100">Animación</a>
            <a href="{{ route('usuarios.publicidad') }}" class="btn btn-outline-primary w-100">Publicidad</a>
            <a href="{{ route('usuarios.alquiler') }}" class="btn btn-outline-primary w-100">Alquiler</a>
            <a href="{{ route('usuarios.calendario') }}" class="btn btn-outline-primary w-100">Calendario</a>
            <a href="{{ route('usuarios.ajustes') }}" class="btn btn-outline-primary w-100">Ajustes</a>
            <a href="{{ route('usuarios.chatbot') }}" class="btn btn-outline-primary w-100">Chatbot</a>
        </div>
        
        <!-- CONTENIDO PRINCIPAL -->
        <div class="col-md-9 col-lg-10 p-5">
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
    </div>
</div>
</body>
</html>
