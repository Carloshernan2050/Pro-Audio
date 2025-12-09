<?php

namespace App\Services;

use App\Repositories\Interfaces\CalendarioItemRepositoryInterface;
use App\Repositories\Interfaces\CalendarioRepositoryInterface;
use App\Repositories\Interfaces\HistorialRepositoryInterface;
use App\Repositories\Interfaces\InventarioRepositoryInterface;
use App\Repositories\Interfaces\MovimientoInventarioRepositoryInterface;
use App\Repositories\Interfaces\ReservaRepositoryInterface;

/**
 * Contenedor de dependencias para CalendarioController.
 * Agrupa todos los servicios y repositorios necesarios para reducir el número de parámetros del constructor.
 */
class CalendarioDependencies
{
    public function __construct(
        public readonly CalendarioValidationService $validationService,
        public readonly CalendarioDataService $dataService,
        public readonly CalendarioEventService $eventService,
        public readonly CalendarioItemService $itemService,
        public readonly ReservaService $reservaService,
        public readonly CalendarioRepositoryInterface $calendarioRepository,
        public readonly CalendarioItemRepositoryInterface $calendarioItemRepository,
        public readonly InventarioRepositoryInterface $inventarioRepository,
        public readonly MovimientoInventarioRepositoryInterface $movimientoInventarioRepository,
        public readonly ReservaRepositoryInterface $reservaRepository,
        public readonly HistorialRepositoryInterface $historialRepository
    ) {
    }
}

