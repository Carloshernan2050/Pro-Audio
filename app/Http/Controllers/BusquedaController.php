<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SubServicios;
use App\Models\Servicios;

class BusquedaController extends Controller
{
    /**
     * Busca servicios y sub-servicios según el término de búsqueda
     */
    public function buscar(Request $request)
    {
        $termino = trim($request->input('buscar', ''));
        
        // Si no hay término de búsqueda, devolver vista vacía
        if (empty($termino)) {
            return view('usuarios.busqueda', [
                'termino' => '',
                'resultados' => collect()
            ]);
        }

        // Normalizar el término de búsqueda
        $terminoNormalizado = $this->normalizarTexto($termino);
        $tokens = explode(' ', $terminoNormalizado);
        
        // Buscar en sub-servicios (nombre y descripción)
        $resultados = SubServicios::query()
            ->select('sub_servicios.id', 'sub_servicios.nombre', 'sub_servicios.precio',
                     'sub_servicios.descripcion', 'servicios.nombre_servicio')
            ->join('servicios', 'servicios.id', '=', 'sub_servicios.servicios_id')
            ->where(function ($query) use ($terminoNormalizado, $tokens) {
                // Búsqueda en el término completo
                $query->where('sub_servicios.nombre', 'like', "%{$terminoNormalizado}%")
                      ->orWhere('sub_servicios.descripcion', 'like', "%{$terminoNormalizado}%");
                
                // Búsqueda por palabras individuales
                foreach ($tokens as $token) {
                    $token = trim($token);
                    if ($token !== '') {
                        $query->orWhere('sub_servicios.nombre', 'like', "%{$token}%")
                              ->orWhere('sub_servicios.descripcion', 'like', "%{$token}%");
                    }
                }
                
                // También buscar en el nombre del servicio
                $query->orWhere('servicios.nombre_servicio', 'like', "%{$terminoNormalizado}%");
            })
            ->orderBy('servicios.nombre_servicio')
            ->orderBy('sub_servicios.nombre')
            ->get();

        return view('usuarios.busqueda', [
            'termino' => $termino,
            'resultados' => $resultados
        ]);
    }

    /**
     * Normaliza el texto para mejorar la búsqueda
     */
    private function normalizarTexto(string $texto): string
    {
        // Convertir a minúsculas y eliminar acentos
        $texto = mb_strtolower($texto, 'UTF-8');
        
        // Correcciones comunes de ortografía
        $correcciones = [
            'animacion' => 'animación',
            'animador' => 'animación',
            'alquiler' => 'alquiler',
            'publicidad' => 'publicidad',
        ];
        
        foreach ($correcciones as $error => $correcto) {
            if (str_contains($texto, $error)) {
                $texto = str_replace($error, $correcto, $texto);
            }
        }
        
        return trim($texto);
    }
}
