<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UsuarioController;

// Página principal -> redirige a registro
Route::get('/', function () {
    return redirect()->route('usuarios.registroUsuario');
});

// Registro
Route::get('/usuarios/crear', [UsuarioController::class, 'registro'])->name('usuarios.registroUsuario');
Route::post('/usuarios', [UsuarioController::class, 'store'])->name('usuarios.store');

// Inicio de sesión
Route::get('/usuarios/inicioSesion', [UsuarioController::class, 'inicioSesion'])->name('usuarios.inicioSesion');
Route::post('/usuarios/autenticar', [UsuarioController::class, 'autenticar'])->name('usuarios.autenticar');

// Cerrar sesión
Route::get('/usuarios/cerrarSesion', [UsuarioController::class, 'cerrarSesion'])->name('usuarios.cerrarSesion');

// Dashboard protegido
Route::get('/dashboard', function () {
    if (!session()->has('usuario_id')) {
        return redirect()->route('usuarios.inicioSesion')->with('error', 'Debes iniciar sesión primero.');
    }
    return view('usuarios.dashboard', [
        'usuario_nombre' => session('usuario_nombre')
    ]);
})->name('dashboard');
