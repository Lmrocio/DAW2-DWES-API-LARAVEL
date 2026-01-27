# Comandos HTTPie - Sistema de Likes

## Autenticación previa

```bash
# Login
http POST :8000/api/auth/login \
  email=admin@demo.local password=password

# Guardar token
export TOKEN=<tu_token_aqui>
```

---

## Sistema de Likes

### 1. Dar like a una receta (Toggle)

```bash
http POST :8000/api/recetas/1/like \
  "Authorization:Bearer $TOKEN"
```

**Respuesta (primera vez - dar like):**
```json
{
  "message": "Like añadido correctamente",
  "liked": true,
  "likes_count": 1
}
```

**Respuesta (segunda vez - quitar like):**
```json
{
  "message": "Like eliminado correctamente",
  "liked": false,
  "likes_count": 0
}
```

---

### 2. Obtener contador de likes de una receta

```bash
http GET :8000/api/recetas/1/likes/count \
  "Authorization:Bearer $TOKEN"
```

**Respuesta:**
```json
{
  "likes_count": 5
}
```

---

### 3. Listar usuarios que han dado like a una receta

```bash
http GET :8000/api/recetas/1/likes \
  "Authorization:Bearer $TOKEN"
```

**Respuesta:**
```json
{
  "likes": [
    {
      "id": 1,
      "user_id": 2,
      "user_name": "Juan Pérez",
      "created_at": "2026-01-27T13:00:00.000000Z"
    },
    {
      "id": 2,
      "user_id": 3,
      "user_name": "María García",
      "created_at": "2026-01-27T13:05:00.000000Z"
    }
  ],
  "likes_count": 2
}
```

---

### 4. Ver receta con contador de likes

```bash
http GET :8000/api/recetas/1 \
  "Authorization:Bearer $TOKEN"
```

**Respuesta:**
```json
{
  "id": 1,
  "titulo": "Tortilla de patatas",
  "descripcion": "Clásica tortilla española",
  "instrucciones": "...",
  "publicada": false,
  "user_id": 1,
  "created_at": "2026-01-27T12:00:00.000000Z",
  "ingredientes": [...],
  "likes_count": 5,
  "liked_by_user": true
}
```

**Campos relacionados con likes:**
- `likes_count`: Número total de likes de la receta
- `liked_by_user`: Indica si el usuario autenticado ha dado like a esta receta

---

### 5. Listar recetas con contador de likes

```bash
http GET :8000/api/recetas \
  "Authorization:Bearer $TOKEN"
```

**Respuesta:**
```json
{
  "data": [
    {
      "id": 1,
      "titulo": "Tortilla de patatas",
      "likes_count": 5,
      "liked_by_user": true
    },
    {
      "id": 2,
      "titulo": "Paella valenciana",
      "likes_count": 12,
      "liked_by_user": false
    }
  ],
  "links": {...},
  "meta": {...}
}
```

---

## Casos de uso completos

### Caso 1: Usuario da like y luego lo quita

```bash
# 1. Dar like
http POST :8000/api/recetas/1/like "Authorization:Bearer $TOKEN"
# Respuesta: "liked": true, "likes_count": 1

# 2. Verificar que aparece en la lista de usuarios que dieron like
http GET :8000/api/recetas/1/likes "Authorization:Bearer $TOKEN"
# Respuesta: incluye al usuario actual

# 3. Quitar like (toggle)
http POST :8000/api/recetas/1/like "Authorization:Bearer $TOKEN"
# Respuesta: "liked": false, "likes_count": 0

# 4. Verificar que ya no aparece en la lista
http GET :8000/api/recetas/1/likes "Authorization:Bearer $TOKEN"
# Respuesta: lista vacía
```

---

### Caso 2: Varios usuarios dan like a la misma receta

```bash
# Usuario 1 da like
http POST :8000/api/auth/login email=user1@demo.local password=password
export TOKEN1=<token_user1>
http POST :8000/api/recetas/1/like "Authorization:Bearer $TOKEN1"

# Usuario 2 da like
http POST :8000/api/auth/login email=user2@demo.local password=password
export TOKEN2=<token_user2>
http POST :8000/api/recetas/1/like "Authorization:Bearer $TOKEN2"

# Usuario 3 da like
http POST :8000/api/auth/login email=user3@demo.local password=password
export TOKEN3=<token_user3>
http POST :8000/api/recetas/1/like "Authorization:Bearer $TOKEN3"

# Ver contador de likes
http GET :8000/api/recetas/1/likes/count "Authorization:Bearer $TOKEN1"
# Respuesta: "likes_count": 3
```

---

### Caso 3: Descubrir recetas populares

```bash
# Listar todas las recetas y ordenar manualmente por likes_count
http GET :8000/api/recetas "Authorization:Bearer $TOKEN"

# Las recetas vienen con likes_count, puedes ordenarlas en el cliente
# o implementar un filtro/ordenación en el backend
```

---

## Flujo de usuario típico

```bash
# 1. Autenticarse
http POST :8000/api/auth/login email=usuario@demo.local password=password
export TOKEN=<tu_token>

# 2. Ver lista de recetas con likes
http GET :8000/api/recetas "Authorization:Bearer $TOKEN"

# 3. Ver detalle de una receta
http GET :8000/api/recetas/1 "Authorization:Bearer $TOKEN"
# Verás: "likes_count": 5, "liked_by_user": false

# 4. Dar like a la receta
http POST :8000/api/recetas/1/like "Authorization:Bearer $TOKEN"
# Respuesta: "liked": true, "likes_count": 6

# 5. Verificar que el like se registró
http GET :8000/api/recetas/1 "Authorization:Bearer $TOKEN"
# Ahora verás: "likes_count": 6, "liked_by_user": true

# 6. Ver quién más ha dado like
http GET :8000/api/recetas/1/likes "Authorization:Bearer $TOKEN"
# Lista de usuarios que dieron like

# 7. Quitar el like
http POST :8000/api/recetas/1/like "Authorization:Bearer $TOKEN"
# Respuesta: "liked": false, "likes_count": 5
```

---

## Casos de error

### Error: Usuario no autenticado

```bash
http POST :8000/api/recetas/1/like
```

**Respuesta (401 Unauthorized):**
```json
{
  "message": "Unauthenticated."
}
```

---

### Error: Receta no existe

```bash
http POST :8000/api/recetas/9999/like \
  "Authorization:Bearer $TOKEN"
```

**Respuesta (404 Not Found):**
```json
{
  "message": "No query results for model [App\\Models\\Receta] 9999"
}
```

---

## Notas técnicas

### Toggle behavior

El endpoint `POST /api/recetas/{receta}/like` implementa un **toggle**:
- Si el usuario **NO** ha dado like → Lo crea (status 201)
- Si el usuario **YA** ha dado like → Lo elimina (status 200)

Esto simplifica el frontend, ya que solo necesitas un botón que llame a este endpoint.

### Restricción única

La tabla `likes` tiene una restricción única en `(user_id, receta_id)`:
- Un usuario solo puede dar **un like** por receta
- Si intentas crear duplicados directamente en la BD, lanzará un error de constraint

### Contador de likes

El contador se calcula de dos formas:
1. **Eager loading:** `$receta->loadCount('likes')` → Eficiente
2. **Atributo calculado:** `$receta->likes_count` → Usa el método `getLikesCountAttribute()`

### liked_by_user

El campo `liked_by_user` en `RecetaResource`:
- Solo aparece si hay un usuario autenticado
- Indica si el usuario actual ha dado like a esa receta
- Útil para mostrar/ocultar el botón de like en el frontend

---

## Integración con frontend

### Ejemplo en JavaScript (fetch)

```javascript
// Dar/quitar like (toggle)
async function toggleLike(recetaId, token) {
  const response = await fetch(`/api/recetas/${recetaId}/like`, {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Accept': 'application/json',
    }
  });
  
  const data = await response.json();
  console.log(data.message); // "Like añadido correctamente" o "Like eliminado correctamente"
  console.log(data.liked); // true o false
  console.log(data.likes_count); // número de likes
  
  return data;
}

// Obtener contador
async function getLikesCount(recetaId, token) {
  const response = await fetch(`/api/recetas/${recetaId}/likes/count`, {
    method: 'GET',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Accept': 'application/json',
    }
  });
  
  const data = await response.json();
  return data.likes_count;
}
```

---

**Documentación completa en:** `docs/09_likes.md`
