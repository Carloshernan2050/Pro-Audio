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
            ->route('usuarios.registroUsuario')
            ->with('success', 'Usuario registrado correctamente.');
    }
}
