<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function select()
    {
        return view('role_select');
    }

    public function set(Request $request)
    {
        $request->validate([
            'role' => 'required|in:Administrador,Cliente,Invitado',
        ]);

        $role = $request->input('role');
        $route = 'inicio';
        $messageType = 'success';
        $message = 'Rol establecido: '.$role;

        if ($role === 'Administrador') {
            session(['pending_admin' => true]);
            if (! session()->has('usuario_id')) {
                $route = 'usuarios.inicioSesion';
                $messageType = 'info';
                $message = 'Inicia sesión para continuar como Administrador.';
            } else {
                $route = 'admin.key.form';
                $message = null;
            }
        } elseif ($role === 'Cliente') {
            session(['role' => 'Cliente']);
            $route = 'usuarios.registroUsuario';
            $message = 'Seleccionaste Cliente, regístrate para continuar.';
        } else {
            // Invitado
            session(['role' => 'Invitado']);
        }

        $redirect = redirect()->route($route);
        if ($message !== null) {
            $redirect = $redirect->with($messageType, $message);
        }

        return $redirect;
    }

    public function adminKeyForm()
    {
        if (! session()->has('usuario_id') || ! session('pending_admin')) {
            return redirect()->route('role.select');
        }

        return view('admin_key');
    }

    public function adminKeyVerify(Request $request)
    {
        if (! session()->has('usuario_id') || ! session('pending_admin')) {
            return redirect()->route('role.select');
        }
        $request->validate(['admin_key' => 'required|string']);
        if ($request->input('admin_key') !== 'ProAudio00') {
            return back()->with('error', 'Clave de administrador incorrecta.');
        }
        session()->forget('pending_admin');
        session(['role' => 'Administrador']);

        return redirect()->route('inicio')->with('success', 'Acceso de Administrador concedido.');
    }

    public function clear()
    {
        session()->forget('role');

        return redirect()->route('role.select')->with('success', 'Rol limpiado.');
    }
}
