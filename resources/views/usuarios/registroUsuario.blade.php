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
                        <label for="primer_nombre">Primer nombre:</label>
                        <input type="text" id="primer_nombre" name="primer_nombre" value="{{ old('primer_nombre') }}" required>
                    </div>

                    <div class="input-grupo">
                        <label for="segundo_nombre">Segundo nombre:</label>
                        <input type="text" id="segundo_nombre" name="segundo_nombre" value="{{ old('segundo_nombre') }}">
                    </div>
                </div>

                <div class="grupo-flex">
                    <div class="input-grupo">
                        <label for="primer_apellido">Primer apellido:</label>
                        <input type="text" id="primer_apellido" name="primer_apellido" value="{{ old('primer_apellido') }}" required>
                    </div>

                    <div class="input-grupo">
                        <label for="segundo_apellido">Segundo apellido:</label>
                        <input type="text" id="segundo_apellido" name="segundo_apellido" value="{{ old('segundo_apellido') }}">
                    </div>
                </div>

                <div class="input-grupo">
                    <label for="correo">Correo:</label>
                    <input type="email" id="correo" name="correo" value="{{ old('correo') }}" required>
                </div>
                
                <div class="input-grupo">
                    <label for="telefono">Teléfono:</label>
                    <input type="text" id="telefono" name="telefono" value="{{ old('telefono') }}">
                </div>

                <div class="input-grupo">
                    <label for="direccion">Dirección:</label>
                    <input type="text" id="direccion" name="direccion" value="{{ old('direccion') }}">
                </div>

                {{-- Agregamos los campos para la contraseña y su confirmación --}}
                <div class="input-grupo">
                    <label for="contrasena">Contraseña:</label>
                    <input type="password" id="contrasena" name="contrasena" required>
                </div>

                <div class="input-grupo">
                    <label for="contrasena_confirmation">Confirmar Contraseña:</label>
                    {{-- Este campo debe llamarse "contrasena_confirmation" para que la regla "confirmed" funcione --}}
                    <input type="password" id="contrasena_confirmation" name="contrasena_confirmation" required>
                </div>
                
                <button type="submit" class="boton-registro">REGISTRARSE</button>
            </form>

            <a href="{{ route('usuarios.inicioSesion') }}" class="enlace-login">¿Ya tienes una cuenta? Iniciar sesión</a>
        </div>
    </div>
</body>
</html>
