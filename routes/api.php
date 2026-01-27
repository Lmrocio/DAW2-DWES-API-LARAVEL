<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
// Guía docente: ver docs/02_rutas_api.md.

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

use App\Http\Controllers\Api\RecetaController;
use App\Http\Controllers\Api\IngredienteController;
use App\Http\Controllers\Api\LikeController;
use App\Http\Controllers\Api\ComentarioController;

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('recetas', RecetaController::class);

    // Rutas anidadas para ingredientes de una receta
    Route::get('recetas/{receta}/ingredientes', [IngredienteController::class, 'index']);
    Route::post('recetas/{receta}/ingredientes', [IngredienteController::class, 'store']);

    // Rutas para operaciones CRUD directas sobre ingredientes
    Route::apiResource('ingredientes', IngredienteController::class)->except(['index', 'store']);

    // Rutas para el sistema de likes
    Route::post('recetas/{receta}/like', [LikeController::class, 'toggleLike']);
    Route::get('recetas/{receta}/likes', [LikeController::class, 'index']);
    Route::get('recetas/{receta}/likes/count', [LikeController::class, 'count']);

    // Rutas para el sistema de comentarios
    Route::get('recetas/{receta}/comentarios', [ComentarioController::class, 'index']);
    Route::post('recetas/{receta}/comentarios', [ComentarioController::class, 'store']);

    // Rutas para operaciones CRUD directas sobre comentarios
    Route::apiResource('comentarios', ComentarioController::class)->except(['index', 'store']);
});

Route::get('/ping', fn () => response()->json(['pong' => true]));

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/refresh', [AuthController::class, 'refresh']);
    });
});

// Ruta ligera y defensiva para la UI de Swagger/OpenAPI
Route::get('/documentation', function () {
    $openApiJson = storage_path('api-docs/api-docs.json');
    $swaggerUiPublic = public_path('vendor/swagger-ui');

    // Si ya existe el JSON de OpenAPI generado, intentar redirigir a la UI si está publicada
    if (file_exists($openApiJson)) {
        // Si la UI está publicada en public/vendor/swagger-ui, redirigimos al index con la URL del JSON
        if (is_dir($swaggerUiPublic) && file_exists($swaggerUiPublic . DIRECTORY_SEPARATOR . 'index.html')) {
            $url = url('storage/api-docs/api-docs.json');
            return redirect('/vendor/swagger-ui/index.html?url=' . urlencode($url));
        }

        // Si no hay UI publicada, devolver información útil
        return response()->json([
            'message' => 'OpenAPI JSON encontrado en storage/api-docs/api-docs.json, pero Swagger UI no está publicada.',
            'openapi_json' => storage_path('api-docs/api-docs.json'),
            'next_steps' => [
                '1' => 'Ejecutar: php artisan vendor:publish --provider="L5Swagger\\L5SwaggerServiceProvider" --tag=swagger-ui --force',
                '2' => 'Copiar los assets a public/vendor/swagger-ui o ejecutar composer post-update hooks',
                '3' => 'Abrir: /vendor/swagger-ui/index.html?url=' . url('storage/api-docs/api-docs.json'),
            ],
        ], 200);
    }

    // Si no existe el JSON, dar instrucciones para generar la documentación
    return response()->view('swagger.missing', [
        'instructions' => [
            '1' => 'Instalar paquete: composer require darkaonline/l5-swagger',
            '2' => 'Publicar configuración y assets: php artisan vendor:publish --provider="L5Swagger\\L5SwaggerServiceProvider"',
            '3' => 'Generar la spec OpenAPI: php artisan l5-swagger:generate',
            '4' => '(Opcional) crear enlace: php artisan storage:link',
            '5' => 'Abrir en navegador: /api/documentation o /vendor/swagger-ui/index.html?url=' . url('storage/api-docs/api-docs.json'),
        ],
    ], 200);
});

/*
 * Alternativa Laravel 11/12 (autorización por middleware):
 *
 * Route::put('/recetas/{receta}', [RecetaController::class, 'update'])
 *     ->middleware(['auth:sanctum', 'can:update,receta']);
 *
 * Route::delete('/recetas/{receta}', [RecetaController::class, 'destroy'])
 *     ->middleware(['auth:sanctum', 'can:delete,receta']);
 */
