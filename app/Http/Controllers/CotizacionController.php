<?php

namespace App\Http\Controllers;

use App\Repositories\Interfaces\CotizacionRepositoryInterface;
use App\Services\ChatbotSessionManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CotizacionController extends Controller
{
    private ChatbotSessionManager $sessionManager;

    private CotizacionRepositoryInterface $cotizacionRepository;

    public function __construct(
        ChatbotSessionManager $sessionManager,
        CotizacionRepositoryInterface $cotizacionRepository
    ) {
        $this->sessionManager = $sessionManager;
        $this->cotizacionRepository = $cotizacionRepository;
    }

    /**
     * Store a newly created cotización in storage.
     */
    public function store(Request $request)
    {
        $selecciones = (array) $request->input('selecciones', session('chat.selecciones', []));
        $dias = (int) $request->input('dias', session('chat.days', 1));
        $personasId = $request->input('personas_id', session('usuario_id'));

        if (empty($selecciones) || ! $personasId) {
            return $this->jsonError('No se pueden guardar cotizaciones sin selecciones o sin usuario identificado.', 422);
        }

        try {
            $this->sessionManager->guardarCotizacion($personasId, $selecciones, $dias);

            return $this->jsonSuccess('Cotización guardada correctamente.', 201);
        } catch (\Exception $e) {
            return $this->handleException($e, 'Error al guardar cotización', 'Error al guardar la cotización.');
        }
    }

    /**
     * Muestra el historial de cotizaciones del cliente actual.
     */
    public function historial(Request $request)
    {
        $personasId = session('usuario_id');

        if (! $personasId) {
            return redirect()
                ->route('usuarios.inicioSesion')
                ->with('error', 'Debes iniciar sesión para ver tu historial de cotizaciones.');
        }

        try {
            $groupBy = $request->query('group_by'); // null | 'consulta' | 'dia'
            $cotizaciones = $this->cotizacionRepository->getByPersonasId($personasId);
            $grouped = $this->groupCotizaciones($cotizaciones, $groupBy);

            return $this->historialView($cotizaciones, $groupBy, $grouped);
        } catch (\Exception $e) {
            Log::error('Error al obtener historial de cotizaciones: '.$e->getMessage());

            return $this->historialView(collect(), null, null, 'Error al cargar el historial de cotizaciones.');
        }
    }

    /**
     * Agrupa las cotizaciones según el tipo de agrupación especificado.
     *
     * @param  \Illuminate\Database\Eloquent\Collection  $cotizaciones
     * @param  string|null  $groupBy
     * @return \Illuminate\Support\Collection|null
     */
    private function groupCotizaciones($cotizaciones, $groupBy)
    {
        if ($groupBy === 'dia') {
            return $cotizaciones->groupBy(function ($c) {
                return $this->formatFecha($c->fecha_cotizacion, 'Y-m-d');
            })->map(fn ($group) => $this->buildGroupData($group));
        }

        if ($groupBy === 'consulta') {
            return $cotizaciones->groupBy(function ($c) {
                $fecha = $this->formatFecha($c->fecha_cotizacion, 'Y-m-d H:i:s');

                return ($c->personas_id ?? '0').'|'.($fecha ?? '');
            })->map(function ($group) {
                $first = $group->first();
                $data = $this->buildGroupData($group);
                $data['persona'] = $first?->persona;
                $data['timestamp'] = $first?->fecha_cotizacion;

                return $data;
            })->sortByDesc(fn ($data) => $this->getTimestamp($data['timestamp']));
        }

        return null;
    }

    /**
     * Formatea una fecha de cotización de forma segura.
     *
     * @param  mixed  $fecha
     * @param  string  $format
     * @return string
     */
    private function formatFecha($fecha, string $format): string
    {
        return optional($fecha)?->format($format) ?? '';
    }

    /**
     * Construye el array común de datos para un grupo de cotizaciones.
     *
     * @param  \Illuminate\Support\Collection  $group
     * @return array
     */
    private function buildGroupData($group): array
    {
        return [
            'items' => $group,
            'total' => $group->sum('monto'),
            'count' => $group->count(),
        ];
    }

    /**
     * Retorna una respuesta JSON de error.
     *
     * @param  string  $message
     * @param  int  $statusCode
     * @return \Illuminate\Http\JsonResponse
     */
    private function jsonError(string $message, int $statusCode = 422): \Illuminate\Http\JsonResponse
    {
        return response()->json(['error' => $message], $statusCode);
    }

    /**
     * Retorna una respuesta JSON de éxito.
     *
     * @param  string  $message
     * @param  int  $statusCode
     * @return \Illuminate\Http\JsonResponse
     */
    private function jsonSuccess(string $message, int $statusCode = 200): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
        ], $statusCode);
    }

    /**
     * Maneja excepciones y retorna respuesta JSON de error.
     *
     * @param  \Exception  $e
     * @param  string  $logMessage
     * @param  string  $userMessage
     * @return \Illuminate\Http\JsonResponse
     */
    private function handleException(\Exception $e, string $logMessage, string $userMessage): \Illuminate\Http\JsonResponse
    {
        Log::error($logMessage.': '.$e->getMessage());

        return $this->jsonError($userMessage, 500);
    }

    /**
     * Retorna la vista del historial con los datos proporcionados.
     *
     * @param  \Illuminate\Support\Collection  $cotizaciones
     * @param  string|null  $groupBy
     * @param  \Illuminate\Support\Collection|null  $groupedCotizaciones
     * @param  string|null  $errorMessage
     * @return \Illuminate\View\View
     */
    private function historialView($cotizaciones, $groupBy, $groupedCotizaciones, ?string $errorMessage = null): \Illuminate\View\View
    {
        $view = view('usuarios.historial_cotizaciones', [
            'cotizaciones' => $cotizaciones,
            'groupBy' => $groupBy,
            'groupedCotizaciones' => $groupedCotizaciones,
        ]);

        if ($errorMessage) {
            return $view->with('error', $errorMessage);
        }

        return $view;
    }

    /**
     * Obtiene el timestamp de una fecha de forma segura.
     *
     * @param  mixed  $timestamp
     * @return int
     */
    private function getTimestamp($timestamp): int
    {
        return optional($timestamp)?->timestamp ?? 0;
    }
}

