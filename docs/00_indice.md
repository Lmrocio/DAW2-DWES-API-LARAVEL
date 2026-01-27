# Indice de documentacion (API Recetas - Laravel 12)

Este indice organiza la documentacion por temas para alumnado que no ha visto Laravel.

## Como leer esta documentacion
- Cada archivo PHP tiene un comentario breve que apunta al tema correspondiente.
- Esta guia explica el "que" y el "por que" del codigo, sin saturar el propio archivo.

## Temas
- docs/01_bootstrap_app.md: Arranque de la aplicacion y registro de componentes.
- docs/02_rutas_api.md: Rutas de la API y middleware.
- docs/03_controladores.md: Controladores y base Controller.
- docs/04_modelos_policies_servicios.md: Modelos, recursos, policies y servicios.
- docs/05_base_de_datos.md: Migraciones y factories.
- docs/06_tests.md: Pruebas feature y unit.
- docs/07_roles_permisos.md: Roles y permisos con Spatie + Sanctum.
- docs/08_ingredientes.md: Implementacion de ingredientes (extension de la API).
- docs/09_likes.md: Implementacion de likes (extension de la API).

## Archivos creados o modificados para este proyecto
(Partiendo de un Laravel 12 con Sail)

- bootstrap/app.php
- bootstrap/providers.php
- routes/api.php
- app/Http/Controllers/Controller.php
- app/Http/Controllers/Api/AuthController.php
- app/Http/Controllers/Api/RecetaController.php
- app/Models/Receta.php
- app/Http/Resources/RecetaResource.php
- app/Policies/RecetaPolicy.php
- app/Services/RecetaService.php
- database/migrations/2026_01_14_185814_create_recetas_table.php
- database/migrations/2026_01_18_174518_add_publicada_to_recetas_table.php
- database/migrations/2026_01_19_011252_create_permission_tables.php
- database/factories/RecetaFactory.php
- database/seeders/DatabaseSeeder.php
- database/seeders/RoleSeeder.php
- app/Models/User.php
- config/auth.php
- config/permission.php
- .env (no se versiona)
- tests/Feature/AuthTest.php
- tests/Feature/RecetaCrudTest.php
- tests/Feature/RecetaAuthorizationTest.php
- tests/Unit/RecetaServiceTest.php

## Extension: Ingredientes (Tarea)
- app/Models/Ingrediente.php
- app/Http/Resources/IngredienteResource.php
- app/Policies/IngredientePolicy.php
- app/Http/Controllers/Api/IngredienteController.php
- database/migrations/2026_01_27_120000_create_ingredientes_table.php
- database/factories/IngredienteFactory.php
- tests/Feature/IngredienteTest.php
- docs/08_ingredientes.md
- docs/HTTPIE_INGREDIENTES.md

## Extension: Likes (Tarea)
- app/Models/Like.php
- app/Http/Controllers/Api/LikeController.php
- database/migrations/2026_01_27_130000_create_likes_table.php
- database/factories/LikeFactory.php
- tests/Feature/LikeTest.php
- docs/09_likes.md
- docs/HTTPIE_LIKES.md

Si se crean mas archivos propios del proyecto, conviene anadirlos aqui con su tema.
