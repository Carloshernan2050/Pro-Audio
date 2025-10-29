<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UsuarioController extends Controller
{
    public function registro()
    {
        return view('usuarios.registroUsuario');
    }

    public function store(Request $request)
    {
        $request->validate([
            'primer_nombre'     => 'required|string|max:255',
            'segundo_nombre'    => 'nullable|string|max:255',
            'primer_apellido'   => 'required|string|max:255',
            'segundo_apellido'  => 'nullable|string|max:255',
            'correo'            => 'required|email|unique:personas,correo',
            'telefono'          => 'nullable|string|max:20',
            'direccion'         => 'nullable|string|max:255',
            'contrasena'        => 'required|string|min:8|confirmed',
        ]);

        $data = $request->all();
        $data['fecha_registro'] = now();
        $data['estado'] = 1; // Activo por defecto

        // Guardar contraseña hasheada en la columna 'contrasena'
        $data['contrasena'] = Hash::make($request->contrasena);

        Usuario::create($data);

        return redirect()
            ->route('usuarios.inicioSesion') // Redirigir a la página de inicio de sesión
            ->with('success', 'Usuario registrado correctamente. Ahora puedes iniciar sesión. ');
    }
        public function inicioSesion()
    {
        return view('usuarios.inicioSesion'); // Vista del formulario de login
    }

    public function autenticar(Request $request)
    {
        $request->validate([
            'correo' => 'required|email',
            'contrasena' => 'required|string',
        ]);

        $usuario = Usuario::where('correo', $request->correo)->first();

        if ($usuario && Hash::check($request->contrasena, $usuario->contrasena)) {
            // Iniciar sesión
            session(['usuario_id' => $usuario->id, 'usuario_nombre' => $usuario->primer_nombre]);
            if (session('pending_admin')) {
                return redirect()->route('admin.key.form');
            }
            return redirect()->route('dashboard')->with('success', '¡Bienvenido, ' . $usuario->primer_nombre . '!');
        } else {
            return back()->withErrors(['correo' => 'Correo o contraseña incorrectos'])->withInput();
        }
    }

    public function cerrarSesion()
    {
        session()->flush(); // Limpiar la sesión
        return redirect()->route('usuarios.inicioSesion')->with('success', 'Sesión cerrada correctamente.');
    }

    public function perfil()
{
    // Obtiene el ID guardado en la sesión al iniciar sesión
    $usuarioId = session('usuario_id');

    // Busca al usuario
    $usuario = Usuario::find($usuarioId);

    // Envía los datos a la vista
    return view('usuarios.perfil', compact('usuario'));
}


}
