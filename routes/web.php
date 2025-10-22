<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\ServiciosController;
use App\Http\Controllers\CalendarioController; 
use App\Http\Controllers\ServiciosViewController;
use App\Http\Controllers\InventarioController;
use App\Http\Controllers\MovimientosInventarioController;

use App\Http\Controllers\AjustesController;

// Página principal -> redirige a registro
Route::get('/', function () {
    return view('usuarios.dashboard');
})->name('inicio');

// Registro
Route::get('/usuarios/crear', [UsuarioController::class, 'registro'])->name('usuarios.registroUsuario');
Route::post('/usuarios', [UsuarioController::class, 'store'])->name('usuarios.store');

// Inicio de sesión
Route::get('/usuarios/inicioSesion', [UsuarioController::class, 'inicioSesion'])->name('usuarios.inicioSesion');
Route::post('/usuarios/autenticar', [UsuarioController::class, 'autenticar'])->name('usuarios.autenticar');

// Cerrar sesión
Route::post('/usuarios/cerrarSesion', [UsuarioController::class, 'cerrarSesion'])->name('usuarios.cerrarSesion');

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
Route::get('/usuarios/animacion', [ServiciosViewController::class, 'animacion'])->name('usuarios.animacion');

// Perifoneo
Route::get('/usuarios/publicidad', [ServiciosViewController::class, 'publicidad'])->name('usuarios.publicidad');

// Eventos
Route::get('/usuarios/alquiler', [ServiciosViewController::class, 'alquiler'])->name('usuarios.alquiler');

// Ajustes
Route::get('/usuarios/ajustes', function () {
    return view('usuarios.ajustes');
})->name('usuarios.ajustes');

// Ajustes
Route::get('/usuarios/ajustes', [AjustesController::class, 'index'])
    ->name('usuarios.ajustes');

// Chatbot
Route::get('/usuarios/chatbot', function () {
    return view('usuarios.chatbot');
})->name('usuarios.chatbot');

Route::get('/perfil', [UsuarioController::class, 'perfil'])->name('usuarios.perfil');


Route::resource('servicios', ServiciosController::class);

// Rutas para inventario y movimientos
Route::resource('inventario', InventarioController::class);
Route::resource('movimientos', MovimientosInventarioController::class);

// Rutas del calendario
Route::controller(CalendarioController::class)->group(function () {
    // Vista principal del calendario
    Route::get('/calendario', 'inicio')->name('usuarios.calendario');
    
    // CRUD del calendario
    Route::post('/calendario', 'guardar')->name('calendario.guardar');
    Route::put('/calendario/{id}', 'actualizar')->name('calendario.actualizar');
    Route::delete('/calendario/{id}', 'eliminar')->name('calendario.eliminar');
});





