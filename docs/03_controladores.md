# Controladores

## app/Http/Controllers/Controller.php (base)
- Es el controlador base del proyecto.
- Se anaden los traits `AuthorizesRequests` y `ValidatesRequests`.
- En Laravel 10 venian por defecto; en 11/12 hay que incluirlos si queremos usar
  `$this->authorize()` o `$request->validate()` desde controladores.

## AuthController
Archivo: `app/Http/Controllers/Api/AuthController.php`.

Funciones principales:
- `register()`: valida datos, crea usuario y devuelve token.
- `login()`: valida credenciales, genera token si son correctas.
- `logout()`: revoca el token actual.
- `me()`: devuelve el usuario autenticado.
- `refresh()`: revoca el token y crea uno nuevo.

Notas didacticas:
- Se usa Sanctum para tokens (metodo `createToken`).
- La validacion se hace con `$request->validate()`.

## RecetaController
Archivo: `app/Http/Controllers/Api/RecetaController.php`.

Metodos REST:
- `index()`: lista recetas con filtros, orden y paginacion.
- `store()`: crea receta asociada al usuario autenticado.
- `show()`: devuelve una receta concreta.
- `update()`: autoriza y aplica reglas de negocio antes de actualizar.
- `destroy()`: autoriza y elimina la receta.

Puntos clave:
- Autorizacion con `$this->authorize()` (compatibilidad con proyectos reales).
- Regla de negocio en `RecetaService` (no tocar recetas publicadas).
- Uso de `RecetaResource` para estandarizar la salida al listar.
