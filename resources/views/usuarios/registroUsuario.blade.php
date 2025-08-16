<!DOCTYPE html>
<html>
<head>
    <title>Registro de Usuario</title>
</head>
<body>
    <h1>Registro de Usuario</h1>

    {{-- Mensaje de éxito --}}
    @if(session('success'))
        <p style="color: green;">{{ session('success') }}</p>
    @endif

    {{-- Mostrar errores de validación --}}
    @if ($errors->any())
        <div style="color: red;">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('usuarios.store') }}" method="POST">
        @csrf
        <label>Primer Nombre:</label>
        <input type="text" name="primer_nombre" value="{{ old('primer_nombre') }}" required><br>

        <label>Segundo Nombre:</label>
        <input type="text" name="segundo_nombre" value="{{ old('segundo_nombre') }}"><br>

        <label>Primer Apellido:</label>
        <input type="text" name="primer_apellido" value="{{ old('primer_apellido') }}" required><br>

        <label>Segundo Apellido:</label>
        <input type="text" name="segundo_apellido" value="{{ old('segundo_apellido') }}"><br>

        <label>Correo:</label>
        <input type="email" name="correo" value="{{ old('correo') }}" required><br>

        <label>Teléfono:</label>
        <input type="text" name="telefono" value="{{ old('telefono') }}"><br>

        <label>Dirección:</label>
        <input type="text" name="direccion" value="{{ old('direccion') }}"><br>

        <div>
            <label>Contraseña:</label>
            <input type="password" name="contrasena" required>
        </div>

        <div>
            <label>Confirmar contraseña:</label>
            <input type="password" name="contrasena_confirmation" required>
        </div>

        <button type="submit">Registrar</button>
        <a href="{{ route('usuarios.inicioSesion') }}" class="btn btn-primary">Iniciar Sesión</a>
    </form>
</body>
</html>
