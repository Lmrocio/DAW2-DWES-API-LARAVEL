# Base de datos

## Migraciones

### Crear tabla `recetas`
Archivo: `database/migrations/2026_01_14_185814_create_recetas_table.php`.
- Crea la tabla con relacion a `users` mediante `foreignId`.
- Incluye `titulo`, `descripcion`, `instrucciones` y timestamps.

### Columna `publicada`
Archivo: `database/migrations/2026_01_18_174518_add_publicada_to_recetas_table.php`.
- Anade el booleano `publicada` con valor por defecto `false`.
- La `down()` esta vacia (en entorno docente se suele simplificar).

## Factory
Archivo: `database/factories/RecetaFactory.php`.
- Genera recetas de prueba con `faker`.
- Crea un usuario automatico si no se indica `user_id`.
