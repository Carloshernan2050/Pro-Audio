<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Perfil de usuario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Perfil de Usuario</h5>
                </div>
                <div class="card-body">
                    @if($usuario)
                        <p><strong>Nombre completo:</strong>
                            {{ $usuario->primer_nombre }}
                            {{ $usuario->segundo_nombre }}
                            {{ $usuario->primer_apellido }}
                            {{ $usuario->segundo_apellido }}</p>

                        <p><strong>Correo electrónico:</strong> {{ $usuario->correo }}</p>
                    @else
                        <p>No se encontró información del usuario.</p>
                    @endif

                    <a href="{{ route('dashboard') }}" class="btn btn-secondary mt-3">Volver al panel</a>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
