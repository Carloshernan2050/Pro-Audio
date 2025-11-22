<?php

namespace App\Services;

use Illuminate\Support\Collection;

class CalendarioEventService
{
    private const DEFAULT_COLOR = '#e91c1c';
    private const DEFAULT_TEXT_COLOR = '#ffffff';
    private const DEFAULT_PRODUCT_LABEL = 'Sin producto';
    private const DEFAULT_EVENT_TITLE = 'Alquiler';

    /**
     * Paleta de colores utilizada para diferenciar reservas en el calendario.
     */
    private array $calendarColorPalette = [
        ['bg' => '#ff6f00', 'border' => '#ffb74d', 'text' => '#1f1200'], // naranja intenso
        ['bg' => '#ff4081', 'border' => '#ff80ab', 'text' => '#fff3f7'], // rosado vibrante
        ['bg' => '#00c853', 'border' => '#69f0ae', 'text' => '#002310'], // verde brillante
        ['bg' => '#9c27b0', 'border' => '#ce93d8', 'text' => '#fdf2ff'], // morado vivo
        ['bg' => '#f44336', 'border' => '#ff7961', 'text' => '#fff5f5'], // rojo intenso
        ['bg' => '#ffd600', 'border' => '#ffef62', 'text' => '#3a2f00'], // amarillo luminoso
        ['bg' => '#ff1744', 'border' => '#ff616f', 'text' => '#fff2f4'], // rojo neón
        ['bg' => '#d500f9', 'border' => '#ea80fc', 'text' => '#fff2ff'], // magenta brillante
    ];

    private int $paletteCursor = 0;

    /**
     * Construye el evento y los datos relacionados para un registro.
     *
     * @return array{evento: array<string, mixed>, items: array<int, array<string, mixed>>}
     */
    public function construirEvento($registro, Collection $movimientos, Collection $inventarios): array
    {
        if ($registro->items && $registro->items->count() > 0) {
            [$titulo, $descripcion, $inventarioIds, $itemsData] = $this->construirEventoDesdeItems(
                $registro,
                $movimientos,
                $inventarios
            );
        } else {
            [$titulo, $descripcion, $inventarioIds, $itemsData] = $this->construirEventoFormatoAntiguo(
                $registro,
                $movimientos,
                $inventarios
            );
        }

        $colorSet = $this->nextPaletteColor();

        return [
            'evento' => [
                'title' => $titulo,
                'start' => $registro->fecha_inicio,
                'end' => $registro->fecha_fin,
                'description' => $descripcion,
                'calendarioId' => $registro->id,
                'inventarioIds' => $inventarioIds,
                'backgroundColor' => $colorSet['bg'],
                'borderColor' => $colorSet['border'],
                'textColor' => $colorSet['text'],
            ],
            'items' => $itemsData,
        ];
    }

    private function construirEventoDesdeItems($registro, Collection $movimientos, Collection $inventarios): array
    {
        $productos = [];
        $inventarioIds = [];
        $itemsData = [];

        foreach ($registro->items as $item) {
            $mov = $movimientos->get($item->movimientos_inventario_id);
            $inventarioId = $mov->inventario_id ?? null;
            if ($inventarioId && isset($inventarios[$inventarioId])) {
                $nombreProducto = $inventarios[$inventarioId]->descripcion ?? self::DEFAULT_PRODUCT_LABEL;
                $productos[] = $nombreProducto . ' (x' . $item->cantidad . ')';
                $inventarioIds[] = $inventarioId;
                $itemsData[] = [
                    'inventario_id' => $inventarioId,
                    'cantidad' => $item->cantidad,
                ];
            }
        }

        $titulo = !empty($productos) ? implode(', ', $productos) : self::DEFAULT_EVENT_TITLE;
        $descripcion = ($registro->descripcion_evento ?? '') . ' | Productos: ' . implode(', ', $productos);

        return [$titulo, $descripcion, $inventarioIds, $itemsData];
    }

    private function construirEventoFormatoAntiguo($registro, Collection $movimientos, Collection $inventarios): array
    {
        $mov = $movimientos->get($registro->movimientos_inventario_id);
        $inventarioId = $mov->inventario_id ?? null;

        $titulo = ($inventarioId && isset($inventarios[$inventarioId]))
            ? ($inventarios[$inventarioId]->descripcion ?? self::DEFAULT_PRODUCT_LABEL)
            : self::DEFAULT_PRODUCT_LABEL;

        if ($registro->cantidad) {
            $titulo .= ' (x' . $registro->cantidad . ')';
        }

        $descripcion = trim(
            ($registro->cantidad ? ('Cantidad solicitada: ' . $registro->cantidad . '. ') : '') .
            ($registro->descripcion_evento ?? '')
        );

        $inventarioIds = $inventarioId ? [$inventarioId] : [];
        $itemsData = [];

        if ($inventarioId) {
            $itemsData[] = [
                'inventario_id' => $inventarioId,
                'cantidad' => $registro->cantidad ?? 1,
            ];
        }

        return [$titulo, $descripcion, $inventarioIds, $itemsData];
    }

    public function nextPaletteColor(): array
    {
        if (empty($this->calendarColorPalette)) {
            return ['bg' => self::DEFAULT_COLOR, 'border' => self::DEFAULT_COLOR, 'text' => self::DEFAULT_TEXT_COLOR];
        }

        $paletteIndex = $this->paletteCursor % count($this->calendarColorPalette);
        $this->paletteCursor++;

        $color = $this->calendarColorPalette[$paletteIndex] ?? [];

        return [
            'bg' => $color['bg'] ?? self::DEFAULT_COLOR,
            'border' => $color['border'] ?? ($color['bg'] ?? self::DEFAULT_COLOR),
            'text' => $color['text'] ?? self::DEFAULT_TEXT_COLOR,
        ];
    }

    public function resetPaletteCursor(): void
    {
        $this->paletteCursor = 0;
    }
}

