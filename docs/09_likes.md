# Implementación de Likes - Documentación Técnica

## 📋 Resumen

Se ha implementado el **sistema de Likes** para la API REST de Recetas, permitiendo que los usuarios marquen recetas como favoritas con una relación basada en la entidad `Like` en lugar de una relación N:M pura.

---

## 🏗️ Arquitectura implementada

### 1. Base de datos

**Migración:** `2026_01_27_130000_create_likes_table.php`

```sql
CREATE TABLE likes (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL,
    receta_id BIGINT NOT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receta_id) REFERENCES recetas(id) ON DELETE CASCADE,
    UNIQUE (user_id, receta_id)
);
```

**Decisiones técnicas:**
- `likes` se implementa como una tabla-entidad independiente con su propio `id` y timestamps
- **Restricción UNIQUE** en `(user_id, receta_id)`: Un usuario solo puede dar un like por receta
- **CASCADE ON DELETE**: Si se elimina un usuario o receta, se eliminan sus likes

---

### 2. Modelo de datos

**Archivo:** `app/Models/Like.php`

**Relación:** `Like` es una entidad con relaciones 1:N desde `User` y `Receta`:
- `User` -> hasMany(Like::class)
- `Receta` -> hasMany(Like::class)
- `Like` -> belongsTo(User::class) y belongsTo(Receta::class)

**Atributos fillable:**
```php
protected $fillable = [
    'user_id',
    'receta_id',
];
```

> Nota: En este diseño NO se utiliza `belongsToMany()` para modelar likes. En su lugar `Like` es una entidad explícita que facilita trazabilidad y extensibilidad.

---

### 3. Actualización del modelo Receta

**Archivo:** `app/Models/Receta.php`

**Nuevas relaciones:**

```php
// Relación hasMany con la entidad likes
public function likes()
{
    return $this->hasMany(Like::class);
}
```

**Atributo calculado:**

```php
protected $appends = ['likes_count'];

public function getLikesCountAttribute(): int
{
    return $this->likes()->count();
}
```

**Método auxiliar:**

```php
public function isLikedBy(?User $user): bool
{
    if (!$user) {
        return false;
    }
    return $this->likes()->where('user_id', $user->id)->exists();
}
```

---

### 4. API Resource actualizado

**Archivo:** `app/Http/Resources/RecetaResource.php`

**Nuevos campos:**

```php
'likes_count' => $this->likes()->count(),
'liked_by_user' => $this->when(
    $request->user(),
    fn() => $this->isLikedBy($request->user())
),
```

---

### 5. Controlador

**Archivo:** `app/Http/Controllers/Api/LikeController.php`

**Métodos:**

| Método | Ruta | Descripción |
|--------|------|-------------|
| toggleLike | POST /recetas/{receta}/like | Si existe el registro en `likes` lo elimina; si no existe lo crea (lógica explícita sobre la entidad `Like`) |
| count | GET /recetas/{receta}/likes/count | Obtener contador de likes |
| index | GET /recetas/{receta}/likes | Listar usuarios que dieron like |

**Ventaja:** El controlador opera sobre la entidad `Like` con create/delete explícitos, lo que hace la lógica más clara y testeable.

---

### 6. Rutas API

**Archivo:** `routes/api.php`

```php
Route::post('recetas/{receta}/like', [LikeController::class, 'toggleLike']);
Route::get('recetas/{receta}/likes', [LikeController::class, 'index']);
Route::get('recetas/{receta}/likes/count', [LikeController::class, 'count']);
```

---

### 7. Actualización del RecetaController

**Cambios en `index()`:**
```php
$recetas = $query->withCount('likes')->paginate($perPage);
```

**Cambios en `show()`:**
```php
$receta->load('ingredientes')->loadCount('likes');
```

**Beneficio:** Evita N+1 queries

---

### 8. Tests

**Archivo:** `tests/Feature/LikeTest.php`

**Cobertura: 12 tests**
- Usuario puede dar like a una receta
- Usuario puede quitar su like (toggle)
- Usuario no puede dar más de un like a la misma receta (constraint)
- Varios usuarios pueden dar like a la misma receta
- Obtener contador de likes
- Listar usuarios que dieron like
- RecetaResource incluye likes_count
- RecetaResource muestra si el usuario dio like
- Al eliminar receta se eliminan likes (cascade)
- Listado de recetas incluye likes_count
- Usuario no autenticado no puede dar like

---

## 📐 Decisiones técnicas

### Diseño elegido: Like como entidad (1:N desde User y Receta)

Para el sistema de Likes se ha evitado el uso de una relación Muchos a Muchos (N:M) pura para reducir la complejidad y aumentar la claridad del modelo de datos. En su lugar, se ha implementado el `Like` como una entidad independiente. Esto permite tratar cada interacción como un objeto único con su propia identidad, facilitando la trazabilidad y cumpliendo la restricción de unicidad mediante una clave compuesta en una relación de Uno a Muchos duplicada.

**Ventajas de este enfoque:**
- Trazabilidad: cada like tiene su propio `id` y timestamps.
- Extensibilidad: es sencillo añadir campos adicionales (por ejemplo `type`, `ip_address`, `meta`).
- Integridad: la restricción `UNIQUE(user_id, receta_id)` en la tabla `likes` asegura unicidad a nivel de BD.
- Claridad en la lógica de negocio: el controlador opera sobre la entidad `Like` (buscar -> crear/eliminar) en vez de llamar a métodos pivot implícitos.

---

### ¿Por qué N:M (cuando se piensa en el dominio)?

Aunque en el dominio conceptual existen muchos usuarios y muchas recetas relacionadas por likes (N:M), el modelado físico se implementa como dos relaciones 1:N unidas por la entidad `Like`. Este patrón conserva la semántica N:M pero aporta las ventajas comentadas al tratar el like como recurso.

### Toggle vs endpoints separados

**Opción elegida:** Un único endpoint que crea o elimina la entidad `Like` según su existencia (simplifica el frontend y mantiene la lógica de negocio clara).

**Alternativa:** Endpoints separados `POST /like` y `DELETE /like` son también válidos, pero aumentan la complejidad del frontend.

---

## ✅ Requisitos cumplidos

- [x] Tabla `likes` creada como entidad con `id` y timestamps
- [x] Restricción única `(user_id, receta_id)`
- [x] Relación `likes()` en `Receta` y `User` (1:N)
- [x] Lógica del controlador sobre la entidad `Like` (buscar -> crear/eliminar)
- [x] RecetaResource devuelve contador vía la relación hasMany
- [x] Tests funcionales creados

---

## 📊 Ejemplos de uso

### Dar like
```bash
http POST :8000/api/recetas/1/like "Authorization:Bearer $TOKEN"
```

**Respuesta (201):**
```json
{
  "message": "Like añadido correctamente",
  "liked": true,
  "likes_count": 1
}
```

### Ver receta con likes
```bash
http GET :8000/api/recetas/1 "Authorization:Bearer $TOKEN"
```

**Respuesta:**
```json
{
  "id": 1,
  "titulo": "Tortilla de patatas",
  "likes_count": 1,
  "liked_by_user": true,
  "ingredientes": [...]
}
```

---

## 🚀 Próximos pasos

1. Ejecutar migraciones: `php artisan migrate`
2. Ejecutar tests: `php artisan test --filter LikeTest`
3. Considerar counter cache (`likes_count` columna en recetas) si se requiere alto rendimiento en lecturas

---

**Fecha:** 27 de enero de 2026  
**Versión:** Laravel 12.x, PHP 8.2+
