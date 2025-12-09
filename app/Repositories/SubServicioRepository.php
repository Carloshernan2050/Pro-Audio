<?php

namespace App\Repositories;

use App\Models\SubServicios;
use App\Repositories\Interfaces\SubServicioRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class SubServicioRepository implements SubServicioRepositoryInterface
{
    /**
     * Obtiene subservicios por sus IDs.
     *
     * @param  array  $ids
     * @return Collection
     */
    public function obtenerPorIds(array $ids): Collection
    {
        return SubServicios::whereIn('id', $ids)->get(['id', 'servicios_id', 'nombre', 'precio']);
    }

    public function find(int $id): SubServicios
    {
        return SubServicios::findOrFail($id);
    }

    /**
     * Busca un subservicio por ID con sus relaciones cargadas.
     *
     * @param  int  $id
     * @return SubServicios
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findWithRelations(int $id): SubServicios
    {
        return SubServicios::with('servicio')->findOrFail($id);
    }

    public function create(array $data): SubServicios
    {
        return SubServicios::create($data);
    }

    public function update(int $id, array $data): bool
    {
        $subServicio = SubServicios::findOrFail($id);

        return $subServicio->update($data);
    }

    public function delete(int $id): bool
    {
        $subServicio = SubServicios::findOrFail($id);

        return $subServicio->delete();
    }

    public function deleteByServicioId(int $servicioId): bool
    {
        return SubServicios::where('servicios_id', $servicioId)->delete() > 0;
    }

    public function queryWithServicioJoin(): \Illuminate\Database\Eloquent\Builder
    {
        return SubServicios::query()
            ->select('sub_servicios.id', 'sub_servicios.nombre', 'sub_servicios.precio', 'servicios.nombre_servicio')
            ->join('servicios', 'servicios.id', '=', 'sub_servicios.servicios_id');
    }

    public function obtenerParaTfidf(): \Illuminate\Database\Eloquent\Collection
    {
        return SubServicios::query()
            ->select('sub_servicios.nombre', 'servicios.nombre_servicio')
            ->join('servicios', 'servicios.id', '=', 'sub_servicios.servicios_id')
            ->get();
    }

    public function obtenerNombres(int $limit = 500): Collection
    {
        return SubServicios::query()
            ->select('nombre')
            ->limit($limit)
            ->get();
    }

    public function buscarPorTokensNormalizados(array $tokensNormalizados, string $columnaNormalizada, int $limit = 12): Collection
    {
        if (empty($tokensNormalizados)) {
            return SubServicios::query()->whereRaw('1 = 0')->get();
        }

        return SubServicios::query()
            ->select('sub_servicios.nombre')
            ->where(function ($q) use ($tokensNormalizados, $columnaNormalizada) {
                foreach ($tokensNormalizados as $index => $termino) {
                    if ($termino !== '') {
                        if ($index === 0) {
                            $q->whereRaw("{$columnaNormalizada} LIKE ?", ["%{$termino}%"]);
                        } else {
                            $q->orWhereRaw("{$columnaNormalizada} LIKE ?", ["%{$termino}%"]);
                        }
                    }
                }
            })
            ->limit($limit)
            ->get();
    }

    public function buscarPorTermino(string $terminoNormalizado, array $tokens): Collection
    {
        return SubServicios::query()
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
    }

    public function queryPorIntenciones(array $intenciones): \Illuminate\Database\Eloquent\Builder
    {
        return SubServicios::query()
            ->select('sub_servicios.id', 'sub_servicios.nombre', 'sub_servicios.precio', 'servicios.nombre_servicio')
            ->join('servicios', 'servicios.id', '=', 'sub_servicios.servicios_id')
            ->whereIn('servicios.nombre_servicio', $intenciones);
    }

    /**
     * Obtiene todos los subservicios con sus relaciones cargadas.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function allWithRelations(): \Illuminate\Database\Eloquent\Collection
    {
        return SubServicios::with('servicio')
            ->orderBy('servicios_id')
            ->orderBy('nombre')
            ->get();
    }
}

