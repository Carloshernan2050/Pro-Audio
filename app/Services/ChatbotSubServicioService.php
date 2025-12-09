<?php

namespace App\Services;

use App\Repositories\Interfaces\SubServicioRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ChatbotSubServicioService
{
    private ChatbotResponseBuilder $responseBuilder;

    private ChatbotTextProcessor $textProcessor;

    private SubServicioRepositoryInterface $subServicioRepository;

    public function __construct(
        ChatbotResponseBuilder $responseBuilder,
        ChatbotTextProcessor $textProcessor,
        SubServicioRepositoryInterface $subServicioRepository
    ) {
        $this->responseBuilder = $responseBuilder;
        $this->textProcessor = $textProcessor;
        $this->subServicioRepository = $subServicioRepository;
    }

    public function obtenerSubServiciosPorIntenciones(array $intenciones): Collection
    {
        if (empty($intenciones)) {
            return collect();
        }

        // Usar repositorio en lugar de modelo directo (DIP)
        $query = $this->subServicioRepository->queryPorIntenciones($intenciones);

        return $this->responseBuilder->ordenarSubServicios($query)->get();
    }

    public function obtenerItemsSeleccionados(array $ids): Collection
    {
        if (empty($ids)) {
            return collect();
        }

        // Usar repositorio en lugar de modelo directo (DIP)
        $subServicios = $this->subServicioRepository->obtenerPorIds($ids);
        // Cargar relaciones si es necesario
        $subServicios->load('servicio');
        
        return $subServicios;
    }

    public function buscarSubServiciosRelacionados(string $mensajeCorregido, array $tokens, array $intenciones): Collection
    {
        // Normalizar el mensaje y tokens para búsqueda sin tildes
        $mensajeNormalizado = $mensajeCorregido !== '' ? $this->textProcessor->normalizarTexto($mensajeCorregido) : '';
        $tokensNormalizados = array_map(function ($tk) {
            return $this->textProcessor->normalizarTexto(trim($tk));
        }, array_filter($tokens, function ($tk) {
            return trim($tk) !== '';
        }));

        return $this->responseBuilder->ordenarSubServicios(
            $this->responseBuilder->subServiciosQuery()
                ->where(function ($q) use ($mensajeNormalizado, $tokensNormalizados) {
                    // Función helper para normalizar texto en SQL (remover tildes)
                    // SQLite usa REPLACE anidado para normalizar
                    $normalizarSql = function ($column) {
                        // Envolver el nombre de la columna correctamente
                        $grammar = DB::getQueryGrammar();
                        $wrapped = $grammar->wrap($column);
                        return DB::raw("LOWER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE({$wrapped}, 'á', 'a'), 'é', 'e'), 'í', 'i'), 'ó', 'o'), 'ú', 'u'), 'ñ', 'n'))");
                    };

                    // Solo buscar en NOMBRES, NO en descripciones
                    if ($mensajeNormalizado !== '') {
                        $q->where($normalizarSql('sub_servicios.nombre'), 'like', "%{$mensajeNormalizado}%");
                    }
                    foreach ($tokensNormalizados as $tk) {
                        if ($tk !== '') {
                            $q->orWhere($normalizarSql('sub_servicios.nombre'), 'like', "%{$tk}%");
                        }
                    }
                })
                ->when(! empty($intenciones), function ($q) use ($intenciones) {
                    $q->whereIn('servicios.nombre_servicio', $intenciones);
                })
        )->limit(12)->get();
    }
}
