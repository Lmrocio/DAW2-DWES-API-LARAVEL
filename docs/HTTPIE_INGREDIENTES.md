# Comandos HTTPie - Funcionalidad de Ingredientes

## Autenticación previa

```bash
# Login
http POST :8000/api/auth/login \
  email=admin@demo.local password=password

# Guardar token
export TOKEN=<tu_token_aqui>
```

---

## CRUD de Ingredientes

### 1. Listar ingredientes de una receta

```bash
http GET :8000/api/recetas/1/ingredientes \
  "Authorization:Bearer $TOKEN"
```

**Respuesta esperada:**
```json
[
  {
    "id": 1,
    "receta_id": 1,
    "nombre": "Huevo",
    "cantidad": "3",
    "unidad": "ud",
    "created_at": "2026-01-27T12:00:00.000000Z",
    "updated_at": "2026-01-27T12:00:00.000000Z"
  }
]
```

---

### 2. Añadir ingrediente a una receta

```bash
http POST :8000/api/recetas/1/ingredientes \
  "Authorization:Bearer $TOKEN" \
  nombre="Huevo" \
  cantidad="3" \
  unidad="ud"
```

**Otros ejemplos:**

```bash
# Ingrediente con medida en gramos
http POST :8000/api/recetas/1/ingredientes \
  "Authorization:Bearer $TOKEN" \
  nombre="Harina" \
  cantidad="200" \
  unidad="g"

# Ingrediente con medida en ml
http POST :8000/api/recetas/1/ingredientes \
  "Authorization:Bearer $TOKEN" \
  nombre="Leche" \
  cantidad="250" \
  unidad="ml"

# Ingrediente con medida en cucharadas
http POST :8000/api/recetas/1/ingredientes \
  "Authorization:Bearer $TOKEN" \
  nombre="Aceite de oliva" \
  cantidad="2" \
  unidad="cucharadas"
```

---

### 3. Ver detalles de un ingrediente específico

```bash
http GET :8000/api/ingredientes/1 \
  "Authorization:Bearer $TOKEN"
```

---

### 4. Actualizar un ingrediente

```bash
http PUT :8000/api/ingredientes/1 \
  "Authorization:Bearer $TOKEN" \
  cantidad="4" \
  unidad="ud"
```

**Con PATCH (actualización parcial):**

```bash
http PATCH :8000/api/ingredientes/1 \
  "Authorization:Bearer $TOKEN" \
  cantidad="4"
```

---

### 5. Eliminar un ingrediente

```bash
http DELETE :8000/api/ingredientes/1 \
  "Authorization:Bearer $TOKEN"
```

**Respuesta esperada:**
```json
{
  "message": "Ingrediente eliminado correctamente"
}
```

---

## Ver receta con ingredientes

```bash
http GET :8000/api/recetas/1 \
  "Authorization:Bearer $TOKEN"
```

**Respuesta esperada:**
```json
{
  "id": 1,
  "titulo": "Tortilla de patatas",
  "descripcion": "Clásica tortilla española",
  "instrucciones": "...",
  "publicada": false,
  "user_id": 1,
  "created_at": "2026-01-27T12:00:00.000000Z",
  "ingredientes": [
    {
      "id": 1,
      "receta_id": 1,
      "nombre": "Huevo",
      "cantidad": "4",
      "unidad": "ud",
      "created_at": "2026-01-27T12:00:00.000000Z",
      "updated_at": "2026-01-27T12:00:00.000000Z"
    },
    {
      "id": 2,
      "receta_id": 1,
      "nombre": "Patata",
      "cantidad": "500",
      "unidad": "g",
      "created_at": "2026-01-27T12:00:00.000000Z",
      "updated_at": "2026-01-27T12:00:00.000000Z"
    }
  ]
}
```

---

## Casos de autorización

### ❌ Usuario sin autorización intenta modificar ingredientes de otra receta

```bash
# Si el usuario autenticado NO es el propietario de la receta 1
# y NO es admin, recibirá un error 403
http POST :8000/api/recetas/1/ingredientes \
  "Authorization:Bearer $TOKEN_OTRO_USUARIO" \
  nombre="Sal" \
  cantidad="1" \
  unidad="pizca"
```

**Respuesta esperada:**
```json
{
  "message": "This action is unauthorized."
}
```

### ✅ Admin puede modificar ingredientes de cualquier receta

```bash
# Login como admin
http POST :8000/api/auth/login \
  email=admin@demo.local password=password

export TOKEN_ADMIN=<token_admin>

# Admin puede agregar ingredientes a cualquier receta
http POST :8000/api/recetas/1/ingredientes \
  "Authorization:Bearer $TOKEN_ADMIN" \
  nombre="Sal" \
  cantidad="1" \
  unidad="pizca"
```

---

## Notas técnicas

### Decisión de diseño: Relación 1:N

Se ha implementado una relación **Uno a Muchos** (1:N):
- **Una Receta tiene muchos Ingredientes**
- **Un Ingrediente pertenece a una Receta**

**Justificación:**
- Los ingredientes tienen cantidades y unidades específicas para cada receta
- La misma "Harina" puede aparecer en distintas recetas con diferentes cantidades
- Es más simple y eficiente que una relación N:M
- Facilita el mantenimiento y la comprensión del código

### Validaciones implementadas

- `nombre`: requerido, string, máximo 100 caracteres
- `cantidad`: requerido, string (permite valores como "2-3", "1/2"), máximo 50 caracteres
- `unidad`: requerido, string, máximo 50 caracteres

### Seguridad (Policy)

- Solo el **propietario de la receta** puede crear/modificar/eliminar ingredientes
- Los **administradores** pueden gestionar ingredientes de cualquier receta
- La autorización se verifica en el controlador usando `$this->authorize()`
