# Pruebas

## Feature: AuthTest
Archivo: `tests/Feature/AuthTest.php`.
- Comprueba registro, login, logout y refresh de token.
- Verifica que `me` requiere autenticacion.

## Feature: RecetaCrudTest
Archivo: `tests/Feature/RecetaCrudTest.php`.
- Comprueba CRUD basico de recetas autenticadas.
- Prueba paginacion, orden y busqueda.

## Feature: RecetaAuthorizationTest
Archivo: `tests/Feature/RecetaAuthorizationTest.php`.
- Comprueba que solo el propietario puede editar o borrar.
- En tests con roles se crean roles manualmente, porque la BD de tests es nueva y
  no hereda los seeders del entorno real.

## Unit: RecetaServiceTest
Archivo: `tests/Unit/RecetaServiceTest.php`.
- Comprueba la regla de negocio de recetas publicadas.
- Lanza `DomainException` cuando toca.
