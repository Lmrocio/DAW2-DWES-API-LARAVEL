# Comandos HTTPie - Sistema de Comentarios

## Autenticación previa

```bash
# Login
http POST :8000/api/auth/login \
  email=admin@demo.local password=password

# Guardar token
export TOKEN=<tu_token_aqui>
```

---

## Sistema de Comentarios

### 1. Listar comentarios de una receta

```bash
http GET :8000/api/recetas/1/comentarios \
  "Authorization:Bearer $TOKEN"
```

**Respuesta:**
```json
[
  {
    "id": 1,
    "receta_id": 1,
    "user_id": 2,
    "user_name": "Juan Pérez",
    "texto": "¡Excelente receta! La he probado y quedó deliciosa.",
    "created_at": "2026-01-27T14:00:00.000000Z",
    "updated_at": "2026-01-27T14:00:00.000000Z"
  },
  {
    "id": 2,
    "receta_id": 1,
    "user_id": 3,
    "user_name": "María García",
    "texto": "Muy buena, aunque yo le añadiría un poco más de sal.",
    "created_at": "2026-01-27T14:05:00.000000Z",
    "updated_at": "2026-01-27T14:05:00.000000Z"
  }
]
```

---

### 2. Crear un comentario en una receta

```bash
http POST :8000/api/recetas/1/comentarios \
  "Authorization:Bearer $TOKEN" \
  texto="¡Excelente receta! La he probado y quedó deliciosa."
```

**Respuesta (201 Created):**
```json
{
  "id": 1,
  "receta_id": 1,
  "user_id": 2,
  "user_name": "Juan Pérez",
  "texto": "¡Excelente receta! La he probado y quedó deliciosa.",
  "created_at": "2026-01-27T14:00:00.000000Z",
  "updated_at": "2026-01-27T14:00:00.000000Z"
}
```

**Ejemplos de comentarios:**

```bash
# Comentario positivo
http POST :8000/api/recetas/1/comentarios \
  "Authorization:Bearer $TOKEN" \
  texto="Perfecta para una cena familiar. Mis hijos la adoraron."

# Comentario con sugerencia
http POST :8000/api/recetas/1/comentarios \
  "Authorization:Bearer $TOKEN" \
  texto="Muy buena, aunque yo le añadiría un poco más de sal al gusto."

# Comentario detallado
http POST :8000/api/recetas/1/comentarios \
  "Authorization:Bearer $TOKEN" \
  texto="La preparé el fin de semana siguiendo todos los pasos. El resultado fue increíble, definitivamente volveré a hacerla."
```

---

### 3. Ver un comentario específico

```bash
http GET :8000/api/comentarios/1 \
  "Authorization:Bearer $TOKEN"
```

**Respuesta:**
```json
{
  "id": 1,
  "receta_id": 1,
  "user_id": 2,
  "user_name": "Juan Pérez",
  "texto": "¡Excelente receta!",
  "created_at": "2026-01-27T14:00:00.000000Z",
  "updated_at": "2026-01-27T14:00:00.000000Z"
}
```

---

### 4. Actualizar un comentario (solo el autor o admin)

```bash
http PUT :8000/api/comentarios/1 \
  "Authorization:Bearer $TOKEN" \
  texto="Actualicé mi comentario: ¡Excelente receta! La recomiendo 100%."
```

**Con PATCH (actualización parcial):**

```bash
http PATCH :8000/api/comentarios/1 \
  "Authorization:Bearer $TOKEN" \
  texto="Comentario actualizado"
```

**Respuesta:**
```json
{
  "id": 1,
  "receta_id": 1,
  "user_id": 2,
  "user_name": "Juan Pérez",
  "texto": "Comentario actualizado",
  "created_at": "2026-01-27T14:00:00.000000Z",
  "updated_at": "2026-01-27T14:10:00.000000Z"
}
```

---

### 5. Eliminar un comentario (solo el autor o admin)

```bash
http DELETE :8000/api/comentarios/1 \
  "Authorization:Bearer $TOKEN"
```

**Respuesta:**
```json
{
  "message": "Comentario eliminado correctamente"
}
```

---

### 6. Ver receta con comentarios

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
  "liked_by_user": true,
  "comentarios": [
    {
      "id": 1,
      "receta_id": 1,
      "user_id": 2,
      "user_name": "Juan Pérez",
      "texto": "¡Excelente receta!",
      "created_at": "2026-01-27T14:00:00.000000Z"
    }
  ],
  "comentarios_count": 1
}
```

---

## Casos de uso completos

### Caso 1: Conversación en una receta

```bash
# Usuario 1 comenta
http POST :8000/api/auth/login email=user1@demo.local password=password
export TOKEN1=<token_user1>

http POST :8000/api/recetas/1/comentarios \
  "Authorization:Bearer $TOKEN1" \
  texto="¿Alguien ha probado esta receta? ¿Qué tal queda?"

# Usuario 2 responde
http POST :8000/api/auth/login email=user2@demo.local password=password
export TOKEN2=<token_user2>

http POST :8000/api/recetas/1/comentarios \
  "Authorization:Bearer $TOKEN2" \
  texto="Yo la hice la semana pasada y quedó perfecta. Muy recomendable."

# Usuario 3 también comenta
http POST :8000/api/auth/login email=user3@demo.local password=password
export TOKEN3=<token_user3>

http POST :8000/api/recetas/1/comentarios \
  "Authorization:Bearer $TOKEN3" \
  texto="A mí me encantó. La única sugerencia es usar un poco menos de sal."

# Ver todos los comentarios
http GET :8000/api/recetas/1/comentarios "Authorization:Bearer $TOKEN1"
```

---

### Caso 2: Editar un comentario

```bash
# Crear comentario
http POST :8000/api/recetas/1/comentarios \
  "Authorization:Bearer $TOKEN" \
  texto="Este es mi comentrio original"  # (error de ortografía)

# Editar para corregir
http PUT :8000/api/comentarios/1 \
  "Authorization:Bearer $TOKEN" \
  texto="Este es mi comentario original (corregido)"
```

---

### Caso 3: Moderación (admin elimina comentario inapropiado)

```bash
# Usuario normal hace un comentario
http POST :8000/api/auth/login email=user@demo.local password=password
export TOKEN_USER=<token_user>

http POST :8000/api/recetas/1/comentarios \
  "Authorization:Bearer $TOKEN_USER" \
  texto="Comentario inapropiado..."

# Admin lo elimina
http POST :8000/api/auth/login email=admin@demo.local password=password
export TOKEN_ADMIN=<token_admin>

http DELETE :8000/api/comentarios/1 "Authorization:Bearer $TOKEN_ADMIN"
# Respuesta: "Comentario eliminado correctamente"
```

---

## Casos de error

### Error: Usuario no autenticado

```bash
http POST :8000/api/recetas/1/comentarios \
  texto="Intento sin autenticación"
```

**Respuesta (401 Unauthorized):**
```json
{
  "message": "Unauthenticated."
}
```

---

### Error: Campo texto vacío

```bash
http POST :8000/api/recetas/1/comentarios \
  "Authorization:Bearer $TOKEN"
```

**Respuesta (422 Validation Error):**
```json
{
  "message": "The texto field is required.",
  "errors": {
    "texto": [
      "The texto field is required."
    ]
  }
}
```

---

### Error: Texto demasiado largo (más de 1000 caracteres)

```bash
http POST :8000/api/recetas/1/comentarios \
  "Authorization:Bearer $TOKEN" \
  texto="<texto de más de 1000 caracteres>"
```

**Respuesta (422):**
```json
{
  "message": "The texto field must not be greater than 1000 characters.",
  "errors": {
    "texto": [
      "The texto field must not be greater than 1000 characters."
    ]
  }
}
```

---

### Error: Intentar eliminar comentario de otro usuario

```bash
# Usuario 1 crea comentario
http POST :8000/api/recetas/1/comentarios \
  "Authorization:Bearer $TOKEN1" \
  texto="Mi comentario"

# Usuario 2 intenta eliminarlo (falla)
http DELETE :8000/api/comentarios/1 \
  "Authorization:Bearer $TOKEN2"
```

**Respuesta (403 Forbidden):**
```json
{
  "message": "This action is unauthorized."
}
```

---

## Flujo de usuario típico

```bash
# 1. Autenticarse
http POST :8000/api/auth/login email=usuario@demo.local password=password
export TOKEN=<tu_token>

# 2. Ver una receta
http GET :8000/api/recetas/1 "Authorization:Bearer $TOKEN"
# Verás: "comentarios": [...], "comentarios_count": 5

# 3. Leer los comentarios
http GET :8000/api/recetas/1/comentarios "Authorization:Bearer $TOKEN"

# 4. Agregar tu comentario
http POST :8000/api/recetas/1/comentarios \
  "Authorization:Bearer $TOKEN" \
  texto="Me encantó esta receta. La hice para mi familia y todos quedaron encantados."

# 5. Editar tu comentario (si lo deseas)
http PUT :8000/api/comentarios/1 \
  "Authorization:Bearer $TOKEN" \
  texto="Me encantó esta receta. La hice para mi familia y todos quedaron encantados. Actualización: la volví a hacer hoy."

# 6. Eliminar tu comentario (si cambias de opinión)
http DELETE :8000/api/comentarios/1 \
  "Authorization:Bearer $TOKEN"
```

---

## Notas técnicas

### Autorización

- **Crear comentario**: Cualquier usuario autenticado
- **Listar comentarios**: Cualquier usuario autenticado
- **Ver comentario**: Cualquier usuario autenticado
- **Actualizar comentario**: Solo el autor o admin
- **Eliminar comentario**: Solo el autor o admin

### Validación

- `texto`: requerido, string, máximo 1000 caracteres

### Ordenación

Los comentarios se devuelven ordenados por **más reciente primero** (`latest()`).

### Cascada

Si se elimina una receta, todos sus comentarios se eliminan automáticamente (CASCADE ON DELETE).

### Información del usuario

Cada comentario incluye `user_name` para mostrar quién lo escribió, sin necesidad de hacer una request adicional.

---

## Integración con frontend

### Ejemplo en JavaScript (fetch)

```javascript
// Listar comentarios
async function getComentarios(recetaId, token) {
  const response = await fetch(`/api/recetas/${recetaId}/comentarios`, {
    headers: {
      'Authorization': `Bearer ${token}`,
      'Accept': 'application/json',
    }
  });
  return await response.json();
}

// Crear comentario
async function crearComentario(recetaId, texto, token) {
  const response = await fetch(`/api/recetas/${recetaId}/comentarios`, {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Accept': 'application/json',
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({ texto })
  });
  return await response.json();
}

// Eliminar comentario
async function eliminarComentario(comentarioId, token) {
  const response = await fetch(`/api/comentarios/${comentarioId}`, {
    method: 'DELETE',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Accept': 'application/json',
    }
  });
  return await response.json();
}
```

---

**Documentación completa en:** `docs/10_comentarios.md`
