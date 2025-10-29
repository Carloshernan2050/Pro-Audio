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
        if ($role === 'Administrador') {
            if (!session()->has('usuario_id')) {
                session(['pending_admin' => true]);
                return redirect()->route('usuarios.inicioSesion')->with('info', 'Inicia sesión para continuar como Administrador.');
            }
            session(['pending_admin' => true]);
            return redirect()->route('admin.key.form');
        } elseif ($role === 'Cliente') {
            session(['role' => 'Cliente']);
            return redirect()->route('usuarios.registroUsuario')->with('success', 'Seleccionaste Cliente, regístrate para continuar.');
        }
        // Invitado
        session(['role' => 'Invitado']);
        return redirect()->route('inicio')->with('success', 'Rol establecido: ' . $role);
    }

    public function adminKeyForm()
    {
        if (!session()->has('usuario_id') || !session('pending_admin')) {
            return redirect()->route('role.select');
        }
        return view('admin_key');
    }

    public function adminKeyVerify(Request $request)
    {
        if (!session()->has('usuario_id') || !session('pending_admin')) {
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


