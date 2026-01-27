# Implementación de Likes - Documentación Técnica

## 📋 Resumen

Se ha implementado el **sistema de Likes** para la API REST de Recetas, permitiendo que los usuarios marquen recetas como favoritas con una relación N:M.

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
- **Tabla pivot** entre `users` y `recetas` (relación N:M)
- **Restricción UNIQUE** en `(user_id, receta_id)`: Un usuario solo puede dar un like por receta
- **CASCADE ON DELETE**: Si se elimina un usuario o receta, se eliminan sus likes

---

### 2. Modelo de datos

**Archivo:** `app/Models/Like.php`

**Relación:** **N:M (Muchos a Muchos)**
- Muchos usuarios pueden dar like a muchas recetas
- Tabla pivot: `likes`

**Atributos fillable:**
```php
protected $fillable = [
    'user_id',
    'receta_id',
];
```

---

### 3. Actualización del modelo Receta

**Archivo:** `app/Models/Receta.php`

**Nuevas relaciones:**

```php
// Relación hasMany con la tabla likes
public function likes()
{
    return $this->hasMany(Like::class);
}

// Relación belongsToMany con users
public function likedByUsers()
{
    return $this->belongsToMany(User::class, 'likes')
        ->withTimestamps();
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
'likes_count' => $this->whenCounted('likes'),
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
| toggle | POST /recetas/{receta}/like | Toggle: crear o eliminar like |
| count | GET /recetas/{receta}/likes/count | Obtener contador de likes |
| index | GET /recetas/{receta}/likes | Listar usuarios que dieron like |

**Ventaja del toggle:** Simplifica el frontend (un solo endpoint para dar/quitar like)

---

### 6. Rutas API

**Archivo:** `routes/api.php`

```php
Route::post('recetas/{receta}/like', [LikeController::class, 'toggle']);
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

### ¿Por qué N:M?

**Justificación:**
1. Un usuario puede dar like a **múltiples recetas**
2. Una receta puede tener likes de **múltiples usuarios**
3. Necesitamos almacenar **timestamps** (cuándo se dio el like)
4. Facilita consultas como "¿quién dio like a esta receta?"

### Restricción UNIQUE

```sql
UNIQUE (user_id, receta_id)
```

**Beneficios:**
- Garantía a nivel de BD: imposible crear duplicados
- Rendimiento: índice único acelera búsquedas
- Integridad: no depende solo de la lógica de aplicación

### Toggle vs endpoints separados

**Opción elegida:** Toggle (POST /like)

**Ventajas:**
- Frontend más simple: un solo endpoint
- No necesitas saber el estado previo
- Menos requests HTTP

---

## ✅ Requisitos cumplidos

- [x] Tabla pivot `likes` creada
- [x] Restricción única `(user_id, receta_id)`
- [x] Relación `likes()` en modelo Receta
- [x] Atributo calculado `likes_count`
- [x] Endpoint `POST /api/recetas/{receta}/like` (toggle)
- [x] Contador de likes aparece en RecetaResource
- [x] Tests funcionales creados (12 tests)
- [x] Extras: liked_by_user, endpoints adicionales, factory, cascade

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
3. Probar endpoints con HTTPie (ver `docs/HTTPIE_LIKES.md`)

---

**Fecha:** 27 de enero de 2026  
**Versión:** Laravel 12.x, PHP 8.2+
