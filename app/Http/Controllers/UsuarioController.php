<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use Illuminate\Http\Request;

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
    ]);

    $data = $request->all();
    $data['fecha_registro'] = now();
    $data['estado'] = 1; // Activo por defecto

    Usuario::create($data);

    return redirect()
        ->route('usuarios.registroUsuario')
        ->with('success', 'Usuario registrado correctamente.');
}



}
