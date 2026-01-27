<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Response;

// Guía docente: ver docs/01_bootstrap_app.md.
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (DomainException $e) {
            // Mapeo de mensajes → códigos de error
            $code = match ($e->getMessage()) {
                'No se puede modificar una receta ya publicada'
                    => 'RECETA_PUBLICADA',

                default => 'ERROR_DOMINIO',
            };

            return response()->json([
                'error' => [
                    'code' => $code,
                    'message' => $e->getMessage(),
                ],
            ], Response::HTTP_CONFLICT); // 409
        });
})->create();
