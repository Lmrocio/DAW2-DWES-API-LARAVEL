<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

/**
 * @OA\Info(
 *     title="API de Recetas - Laravel 12",
 *     version="1.0.0",
 *     description="API REST para gestión de recetas con ingredientes, likes y comentarios.
 *                  Proyecto educativo DAW - DWES.
 *
 *                  Características:
 *                  - Autenticación con Laravel Sanctum
 *                  - CRUD completo de recetas
 *                  - Gestión de ingredientes
 *                  - Sistema de likes
 *                  - Sistema de comentarios
 *                  - Subida de imágenes
 *                  - Filtros avanzados",
 *     @OA\Contact(
 *         name="Soporte API",
 *         email="soporte@recetas-api.local"
 *     ),
 *     @OA\License(
 *         name="MIT",
 *         url="https://opensource.org/licenses/MIT"
 *     )
 * )
 *
 * @OA\Server(
 *     url="http://localhost/api",
 *     description="Servidor de desarrollo local"
 * )
 *
 * @OA\Server(
 *     url="http://localhost:8000/api",
 *     description="Servidor de desarrollo local (puerto 8000)"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Ingresa el token de Sanctum. Ejemplo: 1|abcdefghijklmnopqrstuvwxyz"
 * )
 *
 * @OA\Tag(
 *     name="Autenticación",
 *     description="Endpoints de registro, login y logout"
 * )
 *
 * @OA\Tag(
 *     name="Recetas",
 *     description="CRUD de recetas (Create, Read, Update, Delete)"
 * )
 *
 * @OA\Tag(
 *     name="Ingredientes",
 *     description="Gestión de ingredientes de una receta"
 * )
 *
 * @OA\Tag(
 *     name="Likes",
 *     description="Sistema de likes en recetas"
 * )
 *
 * @OA\Tag(
 *     name="Comentarios",
 *     description="Sistema de comentarios en recetas"
 * )
 */

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
