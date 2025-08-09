<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('usuarios.resgistroUsuario');
});

use App\Http\Controllers\UsuarioController;

Route::get('/usuarios/crear', [UsuarioController::class, 'create'])->name('usuarios.create');
Route::post('/usuarios', [UsuarioController::class, 'store'])->name('usuarios.store');
