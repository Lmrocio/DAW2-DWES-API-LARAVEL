<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

/**
 * Controller base del proyecto.
 * Guía docente: ver docs/03_controladores.md.
 *
 * NOTA DOCENTE:
 *  - En Laravel <=10, este trait venía por defecto.
 *  - En Laravel 11/12 NO se incluye automáticamente.
 *  - Se añade aquí para mantener compatibilidad con proyectos reales
 *    donde se usa $this->authorize().
 *
 * Alternativa moderna (Laravel 11/12):
 *  - Usar Gate::authorize() o middleware `can:`
 */
class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;
}
