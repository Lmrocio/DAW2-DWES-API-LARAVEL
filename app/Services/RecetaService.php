<?php

namespace App\Services;

use App\Models\Receta;
use DomainException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class RecetaService
{
    // Guía docente: ver docs/04_modelos_policies_servicios.md.
    /**
     * Comprueba si una receta puede modificarse según reglas de negocio.
     */
    public function assertCanBeModified(Receta $receta): void
    {
        if ($receta->publicada) {
            throw new DomainException(
                'No se puede modificar una receta ya publicada',
                0 // No usamos el código numérico de PHP, porque lo mapeamos en el Handler
            );
        }
    }

    /**
     * Maneja la subida de una imagen de receta.
     * Guarda la imagen en storage/app/public/recetas y retorna la ruta.
     *
     * @param UploadedFile $imagen
     * @return string Ruta relativa de la imagen guardada
     */
    public function handleImageUpload(UploadedFile $imagen): string
    {
        // Validación adicional de seguridad
        if (!in_array($imagen->getMimeType(), ['image/jpeg', 'image/png', 'image/jpg'])) {
            throw new DomainException('El archivo debe ser una imagen válida (jpeg, png, jpg)');
        }

        if ($imagen->getSize() > 2 * 1024 * 1024) { // 2MB
            throw new DomainException('La imagen no puede superar los 2MB');
        }

        // Guardar en storage/app/public/recetas
        return $imagen->store('recetas', 'public');
    }

    /**
     * Elimina la imagen anterior de una receta.
     *
     * @param string|null $imagenUrl
     * @return void
     */
    public function deleteImage(?string $imagenUrl): void
    {
        if ($imagenUrl && Storage::disk('public')->exists($imagenUrl)) {
            Storage::disk('public')->delete($imagenUrl);
        }
    }

    /**
     * Aplica filtros avanzados a una consulta de recetas.
     * Centraliza la lógica de filtrado para mantener el controlador limpio.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function applyFilters($query, array $filters)
    {
        // Filtro de búsqueda general
        if (isset($filters['q'])) {
            $query->buscar($filters['q']);
        }

        // Filtro por ingrediente
        if (isset($filters['ingrediente'])) {
            $query->conIngrediente($filters['ingrediente']);
        }

        // Filtro por número mínimo de likes
        if (isset($filters['min_likes'])) {
            $query->conMinLikes((int) $filters['min_likes']);
        }

        return $query;
    }

    /**
     * Aplica ordenación a una consulta de recetas.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string|null $sort
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function applySort($query, ?string $sort)
    {
        if ($sort === 'popular' || $sort === '-popular') {
            // Ordenar por popularidad (número de likes)
            $query->porPopularidad();
        } else if ($sort) {
            // Ordenación por otros campos
            $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
            $field = ltrim($sort, '-');
            $query->ordenarPor($field, $direction);
        }

        return $query;
    }
}
