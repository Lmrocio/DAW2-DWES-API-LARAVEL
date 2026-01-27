# Roles y permisos (Spatie + Sanctum)

Este proyecto usa `spatie/laravel-permission` para roles y permisos, y Sanctum para
autenticacion por tokens en la API.

## Idea clave
Los roles se resuelven usando el *guard* activo. Como la API usa tokens de Sanctum,
el guard por defecto debe ser `sanctum` y no `web`.

## Variable de entorno AUTH_GUARD
Archivo: `.env` (no se versiona).
- `AUTH_GUARD=sanctum` fuerza el guard por defecto para la app.
- Esto evita que los roles se consulten con el guard `web` (sesiones).
- Alternativa: cambiar el guard en `config/auth.php`, pero no es deseable porque
  haria que todos los entornos usen Sanctum incluso si no quieres.

## Configuracion
### config/auth.php
- `defaults.guard` lee `AUTH_GUARD`.
- Permite cambiar el guard por entorno sin tocar el repositorio.

### config/permission.php
- Configuracion propia del paquete Spatie.
- No se modifica mucho, pero es importante para ver tablas y cache.

## Modelo User
Archivo: `app/Models/User.php`.
- Usa el trait `HasRoles` para poder llamar a `hasRole()` y `assignRole()`.

## Policy con roles
Archivo: `app/Policies/RecetaPolicy.php`.
- Se permite `update` y `delete` si el usuario es `admin`.
- Si no es admin, solo puede modificar/borrar su propia receta.

## Seeders
### database/seeders/RoleSeeder.php
- Crea los roles `admin` y `user` con `guard_name = sanctum`.
- Es importante que el guard coincida con el de la autenticacion.

### database/seeders/DatabaseSeeder.php
- Llama a `RoleSeeder` para poblar roles en entorno real.

## Tests y roles
- En tests la base de datos se crea desde cero.
- Por eso, si un test necesita roles, debe crearlos dentro del propio test
  (o en un helper). No se suele depender del seeder de produccion por claridad.
