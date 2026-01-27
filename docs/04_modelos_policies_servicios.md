# Modelos, recursos, policies y servicios

## Modelo Receta
Archivo: `app/Models/Receta.php`.

- Define los campos asignables con `$fillable`.
- Define la relacion `user()` con el autor de la receta.

## RecetaResource
Archivo: `app/Http/Resources/RecetaResource.php`.

- Transforma un modelo `Receta` en un array JSON.
- Se usa para devolver colecciones en `index()`.
- Permite controlar exactamente que campos salen en la API.

## RecetaPolicy
Archivo: `app/Policies/RecetaPolicy.php`.

- Contiene reglas de autorizacion por usuario.
- En este proyecto el admin puede editar/borrar cualquier receta, y el usuario
  normal solo sus propias recetas (ver docs/07_roles_permisos.md).
- Se invoca desde el controlador con `$this->authorize('update', $receta)`.

## RecetaService
Archivo: `app/Services/RecetaService.php`.

- Encapsula una regla de negocio: no modificar recetas publicadas.
- Lanza una `DomainException` cuando la regla se incumple.
- `bootstrap/app.php` traduce esa excepcion a una respuesta HTTP 409.
