<?php

namespace App\Repositories\Interfaces;

use App\Models\SubServicios;
use Illuminate\Database\Eloquent\Collection;

interface SubServicioRepositoryInterface
{
    /**
     * Obtiene subservicios por sus IDs.
     *
     * @param  array  $ids
     * @return Collection
     */
    public function obtenerPorIds(array $ids): Collection;

    /**
     * Obtiene todos los subservicios con sus nombres (para vocabulario).
     *
     * @param  int  $limit
     * @return Collection
     */
    public function obtenerNombres(int $limit = 500): Collection;

    /**
     * Busca un subservicio por ID.
     *
     * @param  int  $id
     * @return SubServicios
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function find(int $id): SubServicios;

    /**
     * Busca un subservicio por ID con sus relaciones cargadas.
     *
     * @param  int  $id
     * @return SubServicios
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findWithRelations(int $id): SubServicios;

    /**
     * Crea un nuevo subservicio.
     *
     * @param  array  $data
     * @return SubServicios
     */
    public function create(array $data): SubServicios;

    /**
     * Actualiza un subservicio existente.
     *
     * @param  int  $id
     * @param  array  $data
     * @return bool
     */
    public function update(int $id, array $data): bool;

    /**
     * Elimina un subservicio.
     *
     * @param  int  $id
     * @return bool
     */
    public function delete(int $id): bool;

    /**
     * Elimina todos los subservicios de un servicio.
     *
     * @param  int  $servicioId
     * @return bool
     */
    public function deleteByServicioId(int $servicioId): bool;

    /**
     * Obtiene un query builder con join a servicios para consultas complejas.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function queryWithServicioJoin(): \Illuminate\Database\Eloquent\Builder;

    /**
     * Obtiene subservicios con información de servicio para TF-IDF.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function obtenerParaTfidf(): \Illuminate\Database\Eloquent\Collection;

    /**
     * Busca subservicios por tokens normalizados (para sugerencias).
     * Usa funciones SQL específicas para normalización de texto.
     *
     * @param  array  $tokensNormalizados
     * @param  string  $columnaNormalizada
     * @param  int  $limit
     * @return Collection
     */
    public function buscarPorTokensNormalizados(array $tokensNormalizados, string $columnaNormalizada, int $limit = 12): Collection;

    /**
     * Busca subservicios por término de búsqueda con joins a servicios.
     *
     * @param  string  $terminoNormalizado
     * @param  array  $tokens
     * @return Collection
     */
    public function buscarPorTermino(string $terminoNormalizado, array $tokens): Collection;

    /**
     * Obtiene subservicios por intenciones (nombres de servicios) con join.
     *
     * @param  array  $intenciones
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function queryPorIntenciones(array $intenciones): \Illuminate\Database\Eloquent\Builder;

    /**
     * Obtiene todos los subservicios con sus relaciones cargadas.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function allWithRelations(): \Illuminate\Database\Eloquent\Collection;
}

