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
    Route::post('recetas/{receta}/like', [LikeController::class, 'toggle']);
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

/*
 * Alternativa Laravel 11/12 (autorización por middleware):
 *
 * Route::put('/recetas/{receta}', [RecetaController::class, 'update'])
 *     ->middleware(['auth:sanctum', 'can:update,receta']);
 *
 * Route::delete('/recetas/{receta}', [RecetaController::class, 'destroy'])
 *     ->middleware(['auth:sanctum', 'can:delete,receta']);
 */
