<?php

namespace App\Services;

use App\Models\Receta;
use DomainException;

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
}
