<?php

namespace App\Http\Controllers;

use App\Repositories\Interfaces\SubServicioRepositoryInterface;
use Illuminate\Http\Request;

class BusquedaController extends Controller
{
    private SubServicioRepositoryInterface $subServicioRepository;

    public function __construct(SubServicioRepositoryInterface $subServicioRepository)
    {
        $this->subServicioRepository = $subServicioRepository;
    }
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
                'resultados' => collect(),
            ]);
        }

        // Normalizar el término de búsqueda
        $terminoNormalizado = $this->normalizarTexto($termino);
        $tokens = explode(' ', $terminoNormalizado);

        // Usar repositorio en lugar de modelo directo (DIP)
        $resultados = $this->subServicioRepository->buscarPorTermino($terminoNormalizado, $tokens);

        return view('usuarios.busqueda', [
            'termino' => $termino,
            'resultados' => $resultados,
        ]);
    }

    /**
     * Normaliza el texto para mejorar la búsqueda
     */
    public function normalizarTexto(string $texto): string
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
