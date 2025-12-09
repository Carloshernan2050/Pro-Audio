<?php

namespace App\Services;

use App\Repositories\Interfaces\CotizacionRepositoryInterface;
use App\Repositories\Interfaces\SubServicioRepositoryInterface;
use Illuminate\Support\Facades\Log;

class ChatbotSessionManager
{
    private CotizacionRepositoryInterface $cotizacionRepository;

    private SubServicioRepositoryInterface $subServicioRepository;

    public function __construct(
        CotizacionRepositoryInterface $cotizacionRepository,
        SubServicioRepositoryInterface $subServicioRepository
    ) {
        $this->cotizacionRepository = $cotizacionRepository;
        $this->subServicioRepository = $subServicioRepository;
    }
    public function limpiarSesionChat(): void
    {
        session()->forget('chat.selecciones');
        session()->forget('chat.intenciones');
        session()->forget('chat.days');
    }

    public function guardarCotizacion(int $personasId, array $selecciones, int $dias): void
    {
        if (empty($selecciones) || ! $personasId) {
            return;
        }

        try {
            // Usar repositorio en lugar de modelo directo (DIP)
            $items = $this->subServicioRepository->obtenerPorIds($selecciones);
            $fechaCotizacion = now();
            $diasValidos = max(1, $dias);

            foreach ($items as $item) {
                $monto = (float) $item->precio * $diasValidos;

                // Usar repositorio en lugar de modelo directo (DIP)
                $this->cotizacionRepository->crear([
                    'personas_id' => $personasId,
                    'sub_servicios_id' => $item->id,
                    'monto' => $monto,
                    'fecha_cotizacion' => $fechaCotizacion,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error al guardar cotización: '.$e->getMessage());
        }
    }

    public function obtenerDiasParaRespuesta(int $dias): ?int
    {
        $sessionDaysValue = (int) session('chat.days', 0);
        if ($dias > 0) {
            return $dias;
        } elseif ($sessionDaysValue > 0) {
            return $sessionDaysValue;
        }

        return null;
    }

    public function extraerDiasDelRequest(string $mensaje, bool $esContinuacion, int $sessionDays, ChatbotTextProcessor $textProcessor): int
    {
        $dias = 0;
        if (preg_match('/(\d+)\s*d[ií]as?/i', $mensaje, $m)) {
            $dias = max(1, (int) $m[1]);
        } else {
            $dias = $textProcessor->extraerDiasDesdePalabras($mensaje) ?? 0;
        }
        if ($dias <= 0 && $esContinuacion && $sessionDays > 0) {
            $dias = $sessionDays;
        }
        if ($dias > 0) {
            session(['chat.days' => $dias]);
        } elseif (! $esContinuacion) {
            session()->forget('chat.days');
        }

        return $dias;
    }
}
