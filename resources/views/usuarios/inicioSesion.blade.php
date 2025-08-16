<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión</title>
    {{-- Mantén la directiva de Vite --}}
    @vite('resources/css/app.css')
</head>
<body>

    <div class="background-registro">
        <div class="form-container">
            <h2 class="titulo-formulario">Iniciar Sesión</h2>

            @if(session('success'))
                <p class="mensaje-exito">{{ session('success') }}</p>
            @endif

            @if ($errors->any())
                <div class="errores-validacion">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('usuarios.autenticar') }}" method="POST">
                @csrf

                <div class="input-grupo">
                    <label>Correo:</label>
                    <input type="email" name="correo" value="{{ old('correo') }}" required>
                </div>

                {{-- Aquí está la corrección: el nombre del campo debe ser "contrasena" --}}
                <div class="input-grupo">
                    <label>Contraseña:</label>
                    <input type="password" name="contrasena" required>
                </div>

                <button type="submit" class="boton-registro">INICIAR SESIÓN</button>
            </form>

            <a href="{{ route('usuarios.registroUsuario') }}" class="enlace-login">¿No tienes una cuenta? Regístrate</a>
        </div>
    </div>
</body>
</html>
