# Comandos HTTPie - Filtros Avanzados en Recetas

## Autenticación previa

```bash
# Login
http POST :8000/api/auth/login \
  email=admin@demo.local password=password

# Guardar token
export TOKEN=<tu_token_aqui>
```

---

## Filtros Avanzados

### 1. Filtrar por ingrediente

Busca recetas que contengan un ingrediente específico.

```bash
http GET :8000/api/recetas?ingrediente=huevo \
  "Authorization:Bearer $TOKEN"
```

**Respuesta:**
```json
{
  "data": [
    {
      "id": 1,
      "titulo": "Tortilla de patatas",
      "descripcion": "...",
      "ingredientes": [
        {"nombre": "Huevo", "cantidad": "4", "unidad": "ud"}
      ]
    },
    {
      "id": 3,
      "titulo": "Revuelto de setas",
      "descripcion": "...",
      "ingredientes": [
        {"nombre": "Huevo", "cantidad": "3", "unidad": "ud"}
      ]
    }
  ]
}
```

**Características:**
- Búsqueda case-insensitive (HUEVO = huevo = Huevo)
- Búsqueda parcial ("aceit" encontrará "Aceite de oliva")
- Solo muestra recetas que tengan ese ingrediente

**Ejemplos:**

```bash
# Buscar recetas con pollo
http GET :8000/api/recetas?ingrediente=pollo \
  "Authorization:Bearer $TOKEN"

# Buscar recetas con aceite
http GET :8000/api/recetas?ingrediente=aceite \
  "Authorization:Bearer $TOKEN"

# Buscar recetas con tomate
http GET :8000/api/recetas?ingrediente=tomate \
  "Authorization:Bearer $TOKEN"
```

---

### 2. Ordenar por popularidad (más likes primero)

Ordena las recetas por número de likes en orden descendente.

```bash
http GET :8000/api/recetas?sort=popular \
  "Authorization:Bearer $TOKEN"
```

**Respuesta:**
```json
{
  "data": [
    {
      "id": 5,
      "titulo": "Paella Valenciana",
      "likes_count": 25,
      "liked_by_user": true
    },
    {
      "id": 2,
      "titulo": "Gazpacho",
      "likes_count": 18,
      "liked_by_user": false
    },
    {
      "id": 1,
      "titulo": "Tortilla",
      "likes_count": 12,
      "liked_by_user": true
    }
  ]
}
```

**Con prefijo `-` (descendente explícito):**

```bash
http GET :8000/api/recetas?sort=-popular \
  "Authorization:Bearer $TOKEN"
```

---

### 3. Combinar filtro por ingrediente + popularidad

```bash
http GET :8000/api/recetas?ingrediente=arroz&sort=popular \
  "Authorization:Bearer $TOKEN"
```

**Resultado:** Recetas con arroz, ordenadas por popularidad (más likes primero)

**Ejemplo práctico:**
```
- Paella (arroz, 25 likes)
- Risotto (arroz, 12 likes)
- Arroz con leche (arroz, 8 likes)
```

---

### 4. Búsqueda de texto + filtro por ingrediente

```bash
http GET :8000/api/recetas?q=española&ingrediente=huevo \
  "Authorization:Bearer $TOKEN"
```

**Resultado:** Recetas que contengan "española" en título/descripción Y tengan huevo como ingrediente.

**Ejemplo:**
- ✅ "Tortilla española" con huevo
- ❌ "Tortilla francesa" sin huevo (aunque tenga "francesa")
- ❌ "Paella española" sin huevo (aunque tenga "española")

---

### 5. Combinar todos los filtros

```bash
http GET :8000/api/recetas?q=tradicional&ingrediente=arroz&sort=popular \
  "Authorization:Bearer $TOKEN"
```

**Resultado:** 
- Recetas que contengan "tradicional" en título/descripción
- Y tengan arroz como ingrediente
- Ordenadas por popularidad (más likes primero)

---

## Filtros tradicionales (siguen funcionando)

### Búsqueda general

```bash
# Buscar en título y descripción
http GET :8000/api/recetas?q=paella \
  "Authorization:Bearer $TOKEN"
```

---

### Ordenar por título

```bash
# Ascendente (A-Z)
http GET :8000/api/recetas?sort=titulo \
  "Authorization:Bearer $TOKEN"

# Descendente (Z-A)
http GET :8000/api/recetas?sort=-titulo \
  "Authorization:Bearer $TOKEN"
```

---

### Ordenar por fecha de creación

```bash
# Más recientes primero
http GET :8000/api/recetas?sort=-created_at \
  "Authorization:Bearer $TOKEN"

# Más antiguas primero
http GET :8000/api/recetas?sort=created_at \
  "Authorization:Bearer $TOKEN"
```

---

### Paginación

```bash
# Cambiar número de resultados por página
http GET :8000/api/recetas?per_page=20 \
  "Authorization:Bearer $TOKEN"

# Ir a una página específica
http GET :8000/api/recetas?page=2 \
  "Authorization:Bearer $TOKEN"
```

---

## Ejemplos de casos de uso

### Caso 1: Encontrar recetas populares con un ingrediente específico

```bash
# Quiero las recetas más populares que tengan pollo
http GET :8000/api/recetas?ingrediente=pollo&sort=popular \
  "Authorization:Bearer $TOKEN"
```

**Resultado:** Recetas con pollo, las más populares primero.

---

### Caso 2: Buscar recetas saludables con un ingrediente

```bash
# Recetas "saludables" que tengan aguacate
http GET :8000/api/recetas?q=saludable&ingrediente=aguacate \
  "Authorization:Bearer $TOKEN"
```

---

### Caso 3: Top 5 recetas más populares

```bash
# Las 5 recetas con más likes
http GET :8000/api/recetas?sort=popular&per_page=5 \
  "Authorization:Bearer $TOKEN"
```

---

### Caso 4: Recetas fáciles con pocos ingredientes

```bash
# Buscar "fácil" y ver las más populares
http GET :8000/api/recetas?q=fácil&sort=popular \
  "Authorization:Bearer $TOKEN"
```

---

### Caso 5: Descubrir qué puedo cocinar con lo que tengo

```bash
# Tengo huevos en casa, ¿qué puedo hacer?
http GET :8000/api/recetas?ingrediente=huevo&sort=popular \
  "Authorization:Bearer $TOKEN"

# Resultado: Recetas con huevo, las más populares primero
# - Tortilla (25 likes)
# - Revuelto (12 likes)
# - Huevos rancheros (8 likes)
```

---

## Combinaciones avanzadas

### Ejemplo 1: Recetas de pasta populares

```bash
http GET :8000/api/recetas?q=pasta&sort=popular&per_page=10 \
  "Authorization:Bearer $TOKEN"
```

---

### Ejemplo 2: Recetas vegetarianas con tomate

```bash
http GET :8000/api/recetas?q=vegetariana&ingrediente=tomate&sort=popular \
  "Authorization:Bearer $TOKEN"
```

---

### Ejemplo 3: Recetas rápidas con pollo, ordenadas por popularidad

```bash
http GET :8000/api/recetas?q=rápida&ingrediente=pollo&sort=popular \
  "Authorization:Bearer $TOKEN"
```

---

### Ejemplo 4: Recetas con arroz, ordenadas alfabéticamente

```bash
http GET :8000/api/recetas?ingrediente=arroz&sort=titulo \
  "Authorization:Bearer $TOKEN"
```

---

## Parámetros disponibles

| Parámetro | Descripción | Ejemplo |
|-----------|-------------|---------|
| `q` | Búsqueda en título y descripción | `q=paella` |
| `ingrediente` | Filtrar por ingrediente | `ingrediente=huevo` |
| `sort` | Ordenar resultados | `sort=popular` |
| `page` | Número de página | `page=2` |
| `per_page` | Resultados por página (máx 50) | `per_page=20` |

---

## Opciones de ordenación

| Valor | Descripción |
|-------|-------------|
| `popular` | Por popularidad (más likes primero) |
| `-popular` | Por popularidad descendente |
| `titulo` | Por título (A-Z) |
| `-titulo` | Por título (Z-A) |
| `created_at` | Por fecha (más antiguas primero) |
| `-created_at` | Por fecha (más recientes primero) |
| `likes_count` | Por número de likes ascendente |
| `-likes_count` | Por número de likes descendente |

---

## Respuesta de paginación

Todas las respuestas incluyen información de paginación:

```json
{
  "data": [...],
  "links": {
    "first": "http://localhost/api/recetas?page=1",
    "last": "http://localhost/api/recetas?page=5",
    "prev": null,
    "next": "http://localhost/api/recetas?page=2"
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 5,
    "per_page": 10,
    "to": 10,
    "total": 47
  }
}
```

---

## Notas técnicas

### Búsqueda de ingredientes

- **Case-insensitive:** `huevo` = `HUEVO` = `Huevo`
- **Búsqueda parcial:** `aceit` encontrará "Aceite de oliva"
- **Usa PostgreSQL ILIKE** para búsqueda eficiente

### Ordenación por popularidad

- Usa `withCount('likes')` automáticamente
- Recetas sin likes aparecen al final (likes_count = 0)
- Eficiente: una sola query con JOIN

### Combinación de filtros

Todos los filtros son compatibles entre sí:
```bash
?q=texto&ingrediente=huevo&sort=popular&per_page=20
```

---

## Curl equivalente

Si prefieres curl en lugar de HTTPie:

```bash
# Filtrar por ingrediente
curl -X GET "http://localhost/api/recetas?ingrediente=huevo" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json"

# Ordenar por popularidad
curl -X GET "http://localhost/api/recetas?sort=popular" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json"

# Combinar filtros
curl -X GET "http://localhost/api/recetas?q=española&ingrediente=huevo&sort=popular" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json"
```

---

**Documentación técnica completa en:** `docs/12_filtros_avanzados.md`
