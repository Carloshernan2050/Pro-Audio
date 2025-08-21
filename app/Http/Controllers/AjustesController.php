<?php

namespace App\Http\Controllers;

use App\Models\Servicios;

class AjustesController extends Controller
{
    public function index()
    {
        $servicios = Servicios::all();
        return view('usuarios.ajustes', compact('servicios'));
    }
}