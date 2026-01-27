# Rutas de la API

Archivo: `routes/api.php`.

## Que contiene
- Un grupo de rutas protegidas por `auth:sanctum`.
- Un recurso REST para `recetas`.
- Un endpoint de prueba (`/ping`).
- Un prefijo `auth` con rutas de registro y login, y un subgrupo protegido.

## Rutas principales
### /api/recetas
- `Route::apiResource('recetas', RecetaController::class)` crea 5 rutas:
  index, store, show, update y destroy.
- Al estar dentro de `auth:sanctum`, solo usuarios autenticados pueden usarlo.

### /api/ping
- Devuelve `{ "pong": true }`.
- Sirve para comprobar que la API responde.

### /api/auth
- `POST /register`: crea usuario y devuelve token.
- `POST /login`: valida credenciales y devuelve token.
- Protegidas por `auth:sanctum`:
  - `POST /logout`: revoca el token actual.
  - `GET /me`: devuelve el usuario autenticado.
  - `POST /refresh`: revoca el token y crea otro.

## Autorizacion
- Las rutas de recetas usan `authorize()` dentro del controlador.
- Alternativa moderna: middleware `can:` en la ruta (ver comentario al final de `routes/api.php`).
