<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Usuario</title>
    @vite('resources/css/app.css')
</head>
<body>

    <div class="background-registro">
        <div class="form-container">
            <a href="{{ route('inicio') }}" class="boton-cerrar">✖</a>
            <h2 class="titulo-formulario">Crear una cuenta nueva</h2>
            
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

            <form action="{{ route('usuarios.store') }}" method="POST">
                @csrf

                <div class="grupo-flex">
                    <div class="input-grupo">
                        <label>Primer nombre:</label>
                        <input type="text" name="primer_nombre" value="{{ old('primer_nombre') }}" required>
                    </div>

                    <div class="input-grupo">
                        <label>Segundo nombre:</label>
                        <input type="text" name="segundo_nombre" value="{{ old('segundo_nombre') }}">
                    </div>
                </div>

                <div class="grupo-flex">
                    <div class="input-grupo">
                        <label>Primer apellido:</label>
                        <input type="text" name="primer_apellido" value="{{ old('primer_apellido') }}" required>
                    </div>

                    <div class="input-grupo">
                        <label>Segundo apellido:</label>
                        <input type="text" name="segundo_apellido" value="{{ old('segundo_apellido') }}">
                    </div>
                </div>

                <div class="input-grupo">
                    <label>Correo:</label>
                    <input type="email" name="correo" value="{{ old('correo') }}" required>
                </div>
                
                <div class="input-grupo">
                    <label>Teléfono:</label>
                    <input type="text" name="telefono" value="{{ old('telefono') }}">
                </div>

                <div class="input-grupo">
                    <label>Dirección:</label>
                    <input type="text" name="direccion" value="{{ old('direccion') }}">
                </div>

                {{-- Agregamos los campos para la contraseña y su confirmación --}}
                <div class="input-grupo">
                    <label>Contraseña:</label>
                    <input type="password" name="contrasena" required>
                </div>

                <div class="input-grupo">
                    <label>Confirmar Contraseña:</label>
                    {{-- Este campo debe llamarse "contrasena_confirmation" para que la regla "confirmed" funcione --}}
                    <input type="password" name="contrasena_confirmation" required>
                </div>
                
                <button type="submit" class="boton-registro">REGISTRARSE</button>
            </form>

            <a href="{{ route('usuarios.inicioSesion') }}" class="enlace-login">¿Ya tienes una cuenta? Iniciar sesión</a>
        </div>
    </div>
</body>
</html>
