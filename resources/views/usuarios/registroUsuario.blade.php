<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Usuario</title>
</head>
<body>
    <h1>Registrar Usuario</h1>

    <!-- Mostrar mensajes de éxito -->
    @if(session('success'))
        <p style="color: green;">{{ session('success') }}</p>
    @endif

    <!-- Mostrar errores de validación -->
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

        <label>Primer nombre:</label>
        <input type="text" name="primer_nombre" value="{{ old('primer_nombre') }}" required><br>

        <label>Segundo nombre:</label>
        <input type="text" name="segundo_nombre" value="{{ old('segundo_nombre') }}"><br>

        <label>Primer apellido:</label>
        <input type="text" name="primer_apellido" value="{{ old('primer_apellido') }}" required><br>

        <label>Segundo apellido:</label>
        <input type="text" name="segundo_apellido" value="{{ old('segundo_apellido') }}"><br>

        <label>Correo:</label>
        <input type="email" name="correo" value="{{ old('correo') }}" required><br>

        <label>Teléfono:</label>
        <input type="text" name="telefono" value="{{ old('telefono') }}"><br>

        <label>Dirección:</label>
        <input type="text" name="direccion" value="{{ old('direccion') }}"><br>

        <button type="submit">Guardar</button>
    </form>
</body>
</html>
