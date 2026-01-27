# AGENTS.md

## Proposito del proyecto
API REST de recetas en Laravel 12 (Sail), usada como ejemplo docente para alumnado con poco Laravel.

## Puesta en marcha (Sail)
- Levantar contenedores: `./vendor/bin/sail up -d`
- Ejecutar tests: `./vendor/bin/sail artisan test`

## Convenciones y estilo
- Comentarios minimos en el codigo y explicaciones ampliadas en `docs/`.
- La documentacion por temas esta en `docs/00_indice.md`.
- Mantener el codigo en ASCII salvo justificacion clara.

## Estructura clave
- `bootstrap/app.php`: configuracion de arranque (rutas, middleware, excepciones).
- `bootstrap/providers.php`: listado de service providers.
- `routes/api.php`: rutas de la API.
- `app/Http/Controllers/Api/`: controladores de la API.
- `app/Policies/`: reglas de autorizacion.
- `app/Services/`: reglas de negocio.
- `app/Http/Resources/`: transformacion de respuestas JSON.
- `database/migrations/`: schema de base de datos.
- `tests/`: pruebas feature y unit.

## Laravel 12 vs Laravel 10 (puntos docentes)
- `bootstrap/app.php` y `bootstrap/providers.php` sustituyen registros que antes eran automaticos.
- Si quieres `$this->authorize()` debes incluir el trait `AuthorizesRequests` en el Controller base.
- Alternativas modernas: `Gate::authorize()` o middleware `can:` en rutas.

## Flujo recomendado para nuevas features
1) Crear/ajustar ruta en `routes/api.php`.
2) Crear/ajustar controlador y validacion.
3) Aplicar reglas de negocio en `app/Services/`.
4) Aplicar autorizacion en `app/Policies/`.
5) Ajustar recursos en `app/Http/Resources/` si cambia el JSON.
6) Añadir migracion/factory si cambia el esquema.
7) Escribir tests feature/unit.
8) Actualizar `docs/` si hay conceptos nuevos.

## Tests
- Ejecutar todo: `./vendor/bin/sail artisan test`
- Si falla un test, actualizar primero el codigo, no desactivar tests.

## Documentacion
- Indice general: `docs/00_indice.md`.
- Cada archivo importante referencia su tema con un comentario "Guia docente".
