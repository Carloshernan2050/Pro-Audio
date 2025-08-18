<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\ServiciosController;

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

//dashboard
Route::get('/usuarios/dashboard', function () {
    return redirect()->route('dashboard');
})->name('usuarios.dashboard');

// Perfil
Route::get('/usuarios/perfil', function () {
    return view('usuarios.perfil');
})->name('usuarios.perfil');

// Sonido
Route::get('/usuarios/animacion', function () {
    return view('usuarios.animacion');
})->name('usuarios.animacion');

// Perifoneo
Route::get('/usuarios/publicidad', function () {
    return view('usuarios.publicidad');
})->name('usuarios.publicidad');

// Eventos
Route::get('/usuarios/alquiler', function () {
    return view('usuarios.alquiler');
})->name('usuarios.alquiler');

// Calendario
Route::get('/usuarios/calendario', function () {
    return view('usuarios.calendario');
})->name('usuarios.calendario');

// Ajustes
Route::get('/usuarios/ajustes', function () {
    return view('usuarios.ajustes');
})->name('usuarios.ajustes');

// Chatbot
Route::get('/usuarios/chatbot', function () {
    return view('usuarios.chatbot');
})->name('usuarios.chatbot');

Route::get('/perfil', [UsuarioController::class, 'perfil'])->name('usuarios.perfil');


Route::resource('servicios', ServiciosController::class);

use App\Http\Controllers\CalendarioController;

// Mostrar calendario usando el controlador
Route::get('/usuarios/calendario', [CalendarioController::class,'index'])->name('usuarios.calendario');

// CRUD del calendario
Route::post('/calendario', [CalendarioController::class,'store'])->name('calendario.store');
Route::put('/calendario/{id}', [CalendarioController::class,'update'])->name('calendario.update');
Route::delete('/calendario/{id}', [CalendarioController::class,'destroy'])->name('calendario.destroy');



