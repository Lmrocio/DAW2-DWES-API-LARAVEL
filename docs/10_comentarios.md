# Implementación de Comentarios - Documentación Técnica

## Resumen

Sistema de Comentarios implementado con relación 1:N, políticas de autorización y endpoints RESTful.

## Arquitectura

### 1. Base de datos

**Migración:** `2026_01_27_140000_create_comentarios_table.php`

```sql
CREATE TABLE comentarios (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL,
    receta_id BIGINT NOT NULL,
    texto TEXT NOT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receta_id) REFERENCES recetas(id) ON DELETE CASCADE
);
```

**Decisiones:**
- Relación 1:N (Una receta tiene muchos comentarios)
- CASCADE ON DELETE en ambas foreign keys
- Campo texto tipo TEXT (sin límite estricto en BD, validado en aplicación)

---

### 2. Modelo

**Archivo:** `app/Models/Comentario.php`

**Relaciones:**
- `belongsTo(User::class)` - Un comentario pertenece a un usuario
- `belongsTo(Receta::class)` - Un comentario pertenece a una receta

**Fillable:**
```php
['user_id', 'receta_id', 'texto']
```

---

### 3. Resource

**Archivo:** `app/Http/Resources/ComentarioResource.php`

Incluye `user_name` para evitar requests adicionales.

---

### 4. Policy

**Archivo:** `app/Policies/ComentarioPolicy.php`

| Acción | Autenticado | Autor | Admin |
|--------|------------|-------|-------|
| create | ✅ | ✅ | ✅ |
| update | ❌ | ✅ | ✅ |
| delete | ❌ | ✅ | ✅ |

---

### 5. Controller

**Archivo:** `app/Http/Controllers/Api/ComentarioController.php`

**Métodos:**
- `index()` - Listar comentarios (ordenados por más reciente)
- `store()` - Crear comentario
- `show()` - Ver comentario
- `update()` - Actualizar comentario (con autorización)
- `destroy()` - Eliminar comentario (con autorización)

---

### 6. Rutas

```php
// Anidadas
GET  /api/recetas/{receta}/comentarios
POST /api/recetas/{receta}/comentarios

// Directas
GET    /api/comentarios/{comentario}
PUT    /api/comentarios/{comentario}
DELETE /api/comentarios/{comentario}
```

---

### 7. Validación

```php
'texto' => 'required|string|max:1000'
```

---

### 8. Tests

**14 tests** cubriendo:
- Crear, listar, actualizar, eliminar
- Autorización (autor vs otros usuarios vs admin)
- Validaciones
- Cascade delete
- Ordenación

---

## Características

✅ Cualquier usuario autenticado puede comentar  
✅ Solo autor o admin pueden editar/eliminar  
✅ Comentarios ordenados por más reciente  
✅ Incluye nombre del usuario automáticamente  
✅ Cascade delete si se elimina receta/usuario  
✅ Validación máximo 1000 caracteres  
✅ 14 tests de cobertura completa  

---

## Ejemplo de uso

```bash
# Crear comentario
http POST :8000/api/recetas/1/comentarios \
  "Authorization:Bearer $TOKEN" \
  texto="¡Excelente receta!"

# Listar comentarios
http GET :8000/api/recetas/1/comentarios \
  "Authorization:Bearer $TOKEN"

# Eliminar comentario (solo autor o admin)
http DELETE :8000/api/comentarios/1 \
  "Authorization:Bearer $TOKEN"
```

---

## Actualización en RecetaResource

Ahora incluye:
```php
'comentarios' => ComentarioResource::collection($this->whenLoaded('comentarios')),
'comentarios_count' => $this->whenCounted('comentarios'),
```

---

**Ver comandos HTTPie completos en:** `docs/HTTPIE_COMENTARIOS.md`

**Fecha:** 27 de enero de 2026  
**Laravel:** 12.x | **PHP:** 8.2+
