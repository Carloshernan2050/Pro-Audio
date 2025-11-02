<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

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
        ], [
            'contrasena.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'contrasena.confirmed' => 'La confirmación de contraseña no coincide.',
        ]);

        $data = $request->all();
        $data['fecha_registro'] = now();
        $data['estado'] = 1; // Activo por defecto

        // Guardar contraseña hasheada en la columna 'contrasena'
        $data['contrasena'] = Hash::make($request->contrasena);

        $persona = Usuario::create($data);

        // Asignar rol por defecto "Usuario"
        try {
            $rolId = DB::table('roles')->where('name', 'Usuario')->value('id');
            if (!$rolId) {
                $rolId = DB::table('roles')->where('nombre_rol', 'Usuario')->value('id');
            }
            if ($rolId) {
                DB::table('personas_roles')->insert(['personas_id' => $persona->id, 'roles_id' => $rolId]);
            }
        } catch (\Throwable $e) {
            // continuar sin romper el flujo
        }

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
            $nombreCapitalizado = ucfirst(strtolower($usuario->primer_nombre));
            session(['usuario_id' => $usuario->id, 'usuario_nombre' => $nombreCapitalizado]);

            // Cargar roles desde BD (puede haber múltiples) con alias seguro
            $rows = DB::table('personas_roles as pr')
                ->join('roles as r', 'r.id', '=', 'pr.roles_id')
                ->where('pr.personas_id', $usuario->id)
                ->selectRaw('COALESCE(r.name, r.nombre_rol) as role_name')
                ->pluck('role_name');

            $roles = $rows->map(fn($v) => (string)$v)->unique()->values()->all();

            if (empty($roles)) {
                $roles = ['Usuario'];
            }
            session(['roles' => $roles, 'role' => $roles[0] ?? 'Usuario']);

            if (session('pending_admin')) {
                return redirect()->route('admin.key.form');
            }
            // Siempre dirigir al dashboard; Superadmin verá el botón extra
            return redirect()->route('dashboard')->with('success', '¡Bienvenido, ' . $nombreCapitalizado . '!');
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

    public function updatePhoto(Request $request)
    {
        $usuarioId = session('usuario_id');
        
        if (!$usuarioId) {
            return response()->json(['success' => false, 'message' => 'Debes iniciar sesión'], 401);
        }
        
        // Verificar si es Invitado
        $roles = (array)session('roles');
        if (in_array('Invitado', $roles)) {
            return response()->json(['success' => false, 'message' => 'Los usuarios invitados no pueden subir foto de perfil'], 403);
        }
        
        $request->validate([
            'foto_perfil' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120', // 5MB máximo
        ]);
        
        $usuario = Usuario::find($usuarioId);
        
        if (!$usuario) {
            return response()->json(['success' => false, 'message' => 'Usuario no encontrado'], 404);
        }
        
        // Eliminar foto anterior si existe
        if ($usuario->foto_perfil && Storage::disk('public')->exists('perfiles/' . $usuario->foto_perfil)) {
            Storage::disk('public')->delete('perfiles/' . $usuario->foto_perfil);
        }
        
        // Guardar nueva foto
        $file = $request->file('foto_perfil');
        $filename = 'perfil_' . $usuarioId . '_' . time() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('perfiles', $filename, 'public');
        
        // Actualizar en la base de datos
        $usuario->foto_perfil = $filename;
        $usuario->save();
        
        return response()->json([
            'success' => true,
            'message' => 'Foto de perfil actualizada correctamente',
            'foto_url' => asset('storage/perfiles/' . $filename)
        ]);
    }

}
