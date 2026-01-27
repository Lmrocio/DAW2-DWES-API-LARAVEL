# Arranque y registro en Laravel 12

En Laravel 11/12 el arranque cambia respecto a Laravel 10. Antes existian clases como
`App\Http\Kernel` o `App\Providers\AuthServiceProvider`. Ahora muchas cosas se registran
en `bootstrap/app.php` y en `bootstrap/providers.php`.

## bootstrap/app.php
Archivo clave de arranque. Es el lugar donde se configura la aplicacion y se conectan
rutas, middleware y manejadores de excepciones.

### withRouting(...)
- Indica donde estan los archivos de rutas.
- `web`: rutas web (no usadas en esta API, pero el archivo existe por defecto).
- `api`: rutas API, las que usamos para recetas y auth.
- `commands`: rutas de consola (Artisan).
- `health`: endpoint de salud (`/up`).

### withMiddleware(...)
- Permite registrar middleware globales.
- En este proyecto esta vacio, pero aqui iriamos poniendo middleware personalizados.

### withExceptions(...)
- Permite definir como se transforman excepciones a respuestas HTTP.
- Se captura `DomainException` y se devuelve un JSON con un codigo propio.
- Esto es util para reglas de negocio (ej. receta publicada no se puede modificar).

## bootstrap/providers.php
Aqui se listan los Service Providers que la app debe cargar.

- En Laravel 10 habia varios providers generados por defecto.
- En Laravel 12 se parte de una lista minima.
- Si quieres registrar policies, listeners, bindings, etc., puedes hacerlo en
  `AppServiceProvider` (o crear providers adicionales y listarlos aqui).

### Ejemplo de uso tipico
- Registrar policies con `Gate::policy(...)` en `AppServiceProvider::boot()`.
- Registrar servicios o bindings en `AppServiceProvider::register()`.

## Por que esto es importante para el alumnado
Porque en versiones anteriores muchas cosas estaban "auto-registradas" y ahora hay
que saber donde se ponen. `bootstrap/app.php` y `bootstrap/providers.php` son la pista.
