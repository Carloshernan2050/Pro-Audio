<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\ServiciosController;
use App\Http\Controllers\CalendarioController;
use App\Http\Controllers\ServiciosViewController;
use App\Http\Controllers\InventarioController;
use App\Http\Controllers\MovimientosInventarioController;
use App\Http\Controllers\AjustesController;
use App\Http\Controllers\ChatbotController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\BusquedaController;
use App\Http\Controllers\SubServiciosController;
use App\Http\Controllers\RoleAdminController;

// Inicio directo al dashboard (middleware pondrá Invitado por defecto)
Route::get('/', function(){ return redirect()->route('inicio'); });

// Inicio principal (dashboard de la app)
Route::get('/inicio', function () {
    return view('usuarios.dashboard');
})->name('inicio')->middleware('role:Superadmin,Admin,Usuario,Invitado');

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

// Redirección a dashboard
Route::get('/usuarios/dashboard', function () {
    return redirect()->route('dashboard');
})->name('usuarios.dashboard');

// Perfil
Route::get('/perfil', [UsuarioController::class, 'perfil'])->name('usuarios.perfil');

// Búsqueda de servicios
Route::get('/buscar', [BusquedaController::class, 'buscar'])->name('buscar')->middleware('role:Superadmin,Admin,Usuario,Invitado');

// Servicios (secciones del sitio)
Route::get('/usuarios/animacion', [ServiciosViewController::class, 'animacion'])->name('usuarios.animacion')->middleware('role:Superadmin,Admin,Usuario,Invitado');
Route::get('/usuarios/publicidad', [ServiciosViewController::class, 'publicidad'])->name('usuarios.publicidad')->middleware('role:Superadmin,Admin,Usuario,Invitado');
Route::get('/usuarios/alquiler', [ServiciosViewController::class, 'alquiler'])->name('usuarios.alquiler')->middleware('role:Superadmin,Admin,Usuario,Invitado');

// Ruta dinámica para servicios creados por el usuario
Route::get('/usuarios/servicio/{slug}', [ServiciosViewController::class, 'servicioPorSlug'])->name('usuarios.servicio')->middleware('role:Superadmin,Admin,Usuario,Invitado');

// Ajustes (solo Admin y Superadmin)
Route::get('/usuarios/ajustes', [AjustesController::class, 'index'])->name('usuarios.ajustes')->middleware('role:Superadmin,Admin');

// Chatbot
Route::get('/usuarios/chatbot', [ChatbotController::class, 'index'])->name('usuarios.chatbot')->middleware('role:Superadmin,Admin,Usuario');
Route::post('/chat/enviar', [ChatbotController::class, 'enviar'])->name('chat.enviar');

// CRUD de servicios
Route::resource('servicios', ServiciosController::class)->middleware('role:Superadmin,Admin');

// CRUD de subservicios
Route::resource('subservicios', SubServiciosController::class)->middleware('role:Superadmin,Admin');

// Rutas para inventario y movimientos
Route::resource('inventario', InventarioController::class)->middleware('role:Superadmin,Admin');
Route::resource('movimientos', MovimientosInventarioController::class)->middleware('role:Superadmin,Admin');

// Rutas del calendario
Route::controller(CalendarioController::class)->group(function () {
    // Ver calendario: Admin y Usuario
    Route::get('/calendario', 'inicio')->name('usuarios.calendario')->middleware('role:Superadmin,Admin,Usuario');
    // Mutaciones del calendario: solo Admin/Superadmin
    Route::post('/calendario', 'guardar')->name('calendario.guardar')->middleware('role:Superadmin,Admin');
    Route::put('/calendario/{id}', 'actualizar')->name('calendario.actualizar')->middleware('role:Superadmin,Admin');
    Route::delete('/calendario/{id}', 'eliminar')->name('calendario.eliminar')->middleware('role:Superadmin,Admin');
});

// Gestión de Roles (solo Superadmin)
Route::get('/admin/roles', [RoleAdminController::class, 'index'])->name('admin.roles.index')->middleware('role:Superadmin');
Route::post('/admin/roles', [RoleAdminController::class, 'update'])->name('admin.roles.update')->middleware('role:Superadmin');
