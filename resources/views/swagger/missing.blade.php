<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Swagger Documentation - Not Generated</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial; color:#333; padding:40px; }
        h1 { color:#d33; }
        .box { background:#f8f9fa; border:1px solid #e9ecef; padding:20px; border-radius:8px; }
        code { background:#fff; padding:2px 6px; border-radius:4px; border:1px solid #eaeaea; }
        pre { background:#fff; padding:10px; border-radius:6px; border:1px solid #eaeaea; overflow:auto; }
        a.btn { display:inline-block; padding:8px 12px; background:#007bff; color:white; text-decoration:none; border-radius:6px; }
    </style>
</head>
<body>
    <h1>Documentación Swagger no generada</h1>
    <p>La especificación OpenAPI no se ha generado todavía o la UI de Swagger no está publicada.</p>

    <div class="box">
        <h2>Instrucciones rápidas</h2>
        <ol>
            <li>Instalar paquete: <code>composer require darkaonline/l5-swagger</code></li>
            <li>Publicar configuración y assets: <code>php artisan vendor:publish --provider="L5Swagger\L5SwaggerServiceProvider"</code></li>
            <li>Generar spec OpenAPI: <code>php artisan l5-swagger:generate</code></li>
            <li>Crear enlace simbólico (si aún no existe): <code>php artisan storage:link</code></li>
            <li>Abrir en el navegador: <a href="/api/documentation">/api/documentation</a> o <a href="/vendor/swagger-ui/index.html">/vendor/swagger-ui/index.html</a></li>
        </ol>

        <p>Si su entorno no dispone de Internet para ejecutar <code>composer require</code>, consulte <code>docs/13_swagger.md</code> para instrucciones manuales.</p>

        <p><a class="btn" href="/api/documentation">Reintentar /api/documentation</a></p>
    </div>

    <h3>Detalles técnicos</h3>
    <p>Ruta del JSON generado (si existe): <code>{{ storage_path('api-docs/api-docs.json') }}</code></p>
    <p>Ruta pública de Swagger UI esperada: <code>{{ public_path('vendor/swagger-ui') }}</code></p>
</body>
</html>
