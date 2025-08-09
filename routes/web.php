<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('usuarios.registroUsuario'); // "registro" en vez de "resgistro"

});

use App\Http\Controllers\UsuarioController;

Route::get('/usuarios/crear', [UsuarioController::class, 'registro'])->name('usuarios.registroUsuario');
Route::post('/usuarios', [UsuarioController::class, 'store'])->name('usuarios.store');

