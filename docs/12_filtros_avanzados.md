# Filtros Avanzados en Recetas - Documentación Técnica

## Resumen

Sistema de filtros avanzados implementado usando **Eloquent Scopes** para mantener el controlador limpio y reutilizable.

---

## Arquitectura

### 1. Eloquent Scopes (Modelo Receta)

**Archivo:** `app/Models/Receta.php`

Los scopes son métodos reutilizables que encapsulan lógica de consulta.

#### Scope: `conIngrediente()`

**Propósito:** Filtrar recetas por ingrediente

```php
public function scopeConIngrediente($query, ?string $ingrediente)
{
    if (!$ingrediente) {
        return $query;
    }

    return $query->whereHas('ingredientes', function ($q) use ($ingrediente) {
        $q->where('nombre', 'ILIKE', "%{$ingrediente}%");
    });
}
```

**Uso:**
```php
Receta::conIngrediente('huevo')->get();
```

**Características:**
- Búsqueda case-insensitive con `ILIKE` (PostgreSQL)
- Búsqueda parcial con `%` wildcards
- Retorna el query sin modificar si no hay parámetro

---

#### Scope: `porPopularidad()`

**Propósito:** Ordenar por número de likes descendente

```php
public function scopePorPopularidad($query)
{
    return $query->withCount('likes')
        ->orderBy('likes_count', 'desc');
}
```

**Uso:**
```php
Receta::porPopularidad()->get();
```

**Características:**
- Usa `withCount('likes')` para contar likes eficientemente
- Ordena descendente (más likes primero)
- Una sola query con JOIN

---

#### Scope: `buscar()`

**Propósito:** Búsqueda general en título y descripción

```php
public function scopeBuscar($query, ?string $termino)
{
    if (!$termino) {
        return $query;
    }

    return $query->where(function ($q) use ($termino) {
        $q->where('titulo', 'ILIKE', "%{$termino}%")
            ->orWhere('descripcion', 'ILIKE', "%{$termino}%");
    });
}
```

**Uso:**
```php
Receta::buscar('paella')->get();
```

**Características:**
- Busca en título O descripción
- Case-insensitive
- Búsqueda parcial

---

#### Scope: `ordenarPor()`

**Propósito:** Ordenar por campos permitidos

```php
public function scopeOrdenarPor($query, ?string $campo, string $direccion = 'asc')
{
    if (!$campo) {
        return $query;
    }

    $camposPermitidos = ['titulo', 'created_at', 'likes_count'];

    if (!in_array($campo, $camposPermitidos)) {
        return $query;
    }

    if ($campo === 'likes_count' && !$query->getQuery()->columns) {
        $query->withCount('likes');
    }

    return $query->orderBy($campo, $direccion);
}
```

**Uso:**
```php
Receta::ordenarPor('titulo', 'desc')->get();
```

**Características:**
- Whitelist de campos permitidos (seguridad)
- Añade `withCount('likes')` si ordena por likes
- Dirección configurable (asc/desc)

---

### 2. RecetaController::index() refactorizado

**Archivo:** `app/Http/Controllers/Api/RecetaController.php`

**Antes (sin scopes):**
```php
public function index(Request $request)
{
    $query = Receta::query();

    if ($search = $request->query('q')) {
        $query->where(function ($q) use ($search) {
            $q->where('titulo', 'ILIKE', "%{$search}%")
                ->orWhere('descripcion', 'ILIKE', "%{$search}%");
        });
    }

    $allowedSorts = ['titulo', 'created_at'];
    if ($sort = $request->query('sort')) {
        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $field = ltrim($sort, '-');
        if (in_array($field, $allowedSorts)) {
            $query->orderBy($field, $direction);
        }
    }

    $recetas = $query->withCount('likes')->paginate($perPage);
    return RecetaResource::collection($recetas);
}
```

**Después (con scopes):**
```php
public function index(Request $request)
{
    $query = Receta::query();

    // Filtros usando scopes
    $query->buscar($request->query('q'));
    $query->conIngrediente($request->query('ingrediente'));

    // Ordenación
    $sort = $request->query('sort');

    if ($sort === 'popular' || $sort === '-popular') {
        $query->porPopularidad();
    } else if ($sort) {
        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $field = ltrim($sort, '-');
        $query->ordenarPor($field, $direction);
    }

    $query->withCount('likes');

    $perPage = min((int) $request->query('per_page', 10), 50);
    $recetas = $query->paginate($perPage);

    return RecetaResource::collection($recetas);
}
```

**Ventajas:**
- Más legible
- Lógica reutilizable (los scopes se pueden usar en otros lugares)
- Más fácil de testear
- Más fácil de mantener

---

## Parámetros de query disponibles

| Parámetro | Tipo | Descripción | Ejemplo |
|-----------|------|-------------|---------|
| `q` | string | Búsqueda en título/descripción | `?q=paella` |
| `ingrediente` | string | Filtrar por ingrediente | `?ingrediente=huevo` |
| `sort` | string | Campo de ordenación | `?sort=popular` |
| `page` | int | Número de página | `?page=2` |
| `per_page` | int | Resultados por página (máx 50) | `?per_page=20` |

---

## Opciones de ordenación

| Valor | Descripción |
|-------|-------------|
| `popular` | Por popularidad (más likes primero) |
| `-popular` | Por popularidad (descendente) |
| `titulo` | Por título (A-Z) |
| `-titulo` | Por título (Z-A) |
| `created_at` | Por fecha creación (antiguas primero) |
| `-created_at` | Por fecha creación (recientes primero) |

---

## Flujo de datos

### Ejemplo: Filtrar por ingrediente + ordenar por popularidad

```
Cliente HTTP
    ↓
GET /api/recetas?ingrediente=huevo&sort=popular
    ↓
RecetaController::index()
    ↓
$query = Receta::query()
    ↓
$query->buscar(null)  // No hace nada
    ↓
$query->conIngrediente('huevo')
    ↓
whereHas('ingredientes', where nombre ILIKE '%huevo%')
    ↓
$query->porPopularidad()
    ↓
withCount('likes')->orderBy('likes_count', 'desc')
    ↓
$query->paginate(10)
    ↓
SQL: 
SELECT recetas.*, COUNT(likes.id) as likes_count
FROM recetas
LEFT JOIN ingredientes ON ingredientes.receta_id = recetas.id
LEFT JOIN likes ON likes.receta_id = recetas.id
WHERE ingredientes.nombre ILIKE '%huevo%'
GROUP BY recetas.id
ORDER BY likes_count DESC
LIMIT 10 OFFSET 0
    ↓
RecetaResource::collection($recetas)
    ↓
JSON Response
```

---

## Queries SQL generadas

### Filtro por ingrediente

```sql
SELECT recetas.*
FROM recetas
WHERE EXISTS (
    SELECT *
    FROM ingredientes
    WHERE ingredientes.receta_id = recetas.id
    AND ingredientes.nombre ILIKE '%huevo%'
)
```

### Ordenar por popularidad

```sql
SELECT recetas.*, COUNT(likes.id) as likes_count
FROM recetas
LEFT JOIN likes ON likes.receta_id = recetas.id
GROUP BY recetas.id
ORDER BY likes_count DESC
```

### Combinación de filtros

```sql
SELECT recetas.*, COUNT(likes.id) as likes_count
FROM recetas
LEFT JOIN likes ON likes.receta_id = recetas.id
WHERE EXISTS (
    SELECT *
    FROM ingredientes
    WHERE ingredientes.receta_id = recetas.id
    AND ingredientes.nombre ILIKE '%huevo%'
)
AND (recetas.titulo ILIKE '%española%' OR recetas.descripcion ILIKE '%española%')
GROUP BY recetas.id
ORDER BY likes_count DESC
```

---

## Ventajas de usar Scopes

### 1. Reutilización

```php
// En el controlador
Receta::conIngrediente('huevo')->get();

// En otro lugar
Receta::porPopularidad()->take(5)->get();

// Combinados
Receta::conIngrediente('arroz')->porPopularidad()->get();
```

### 2. Testeable

```php
// Test del scope
public function test_scope_con_ingrediente()
{
    $receta = Receta::factory()->create();
    Ingrediente::factory()->create([
        'receta_id' => $receta->id,
        'nombre' => 'Huevo'
    ]);

    $resultado = Receta::conIngrediente('huevo')->get();
    
    $this->assertCount(1, $resultado);
}
```

### 3. Legibilidad

```php
// Antes (sin scopes)
$recetas = Receta::where(function($q) use ($ingrediente) {
    $q->whereHas('ingredientes', function($q2) use ($ingrediente) {
        $q2->where('nombre', 'ILIKE', "%{$ingrediente}%");
    });
})->withCount('likes')->orderBy('likes_count', 'desc')->get();

// Después (con scopes)
$recetas = Receta::conIngrediente($ingrediente)
    ->porPopularidad()
    ->get();
```

---

## Tests

**Archivo:** `tests/Feature/RecetaFiltrosAvanzadosTest.php`

**Cobertura: 13 tests**
- Filtrar por ingrediente
- Filtro case-insensitive
- Filtro parcial
- Ordenar por popularidad
- Ordenar con prefijo `-`
- Combinar ingrediente + popularidad
- Combinar múltiples filtros (búsqueda + ingrediente + popularidad)
- Ordenar por título (ascendente/descendente)
- Sin filtros devuelve todas
- Scope null no afecta consulta
- Recetas sin likes al final por popularidad

---

## Ejemplos de uso

### En el controlador

```php
// Top 10 recetas populares
$topRecetas = Receta::porPopularidad()->take(10)->get();

// Recetas con pollo, populares
$recetasPollo = Receta::conIngrediente('pollo')
    ->porPopularidad()
    ->get();

// Búsqueda + filtro + ordenación
$recetas = Receta::buscar('española')
    ->conIngrediente('arroz')
    ->porPopularidad()
    ->paginate(10);
```

### En otros lugares (servicios, jobs, etc.)

```php
// En un servicio de recomendaciones
class RecomendacionService
{
    public function recetasSimilares(Receta $receta)
    {
        $ingredientesPrincipales = $receta->ingredientes()
            ->limit(3)
            ->pluck('nombre');

        return Receta::where('id', '!=', $receta->id)
            ->conIngrediente($ingredientesPrincipales->first())
            ->porPopularidad()
            ->take(5)
            ->get();
    }
}
```

---

## Decisiones técnicas

### ¿Por qué Scopes en lugar de métodos estáticos?

**Scopes:**
- Se encadenan con el query builder
- Más flexibles
- Estándar de Eloquent

**Métodos estáticos:**
- Menos flexibles
- No se encadenan bien

### ¿Por qué retornar el query si el parámetro es null?

```php
if (!$ingrediente) {
    return $query;  // No modifica el query
}
```

**Razón:** Permite encadenar scopes sin preocuparse por nulls:

```php
// Funciona aunque ingrediente sea null
Receta::conIngrediente($ingrediente)->porPopularidad()->get();
```

### ¿Por qué ILIKE en lugar de LIKE?

**ILIKE:** Case-insensitive en PostgreSQL

**Ventaja:**
```
huevo = HUEVO = Huevo = HuEvO
```

---

## Mejoras futuras (opcionales)

### Scope para filtrar por múltiples ingredientes

```php
public function scopeConIngredientes($query, array $ingredientes)
{
    foreach ($ingredientes as $ingrediente) {
        $query->whereHas('ingredientes', function ($q) use ($ingrediente) {
            $q->where('nombre', 'ILIKE', "%{$ingrediente}%");
        });
    }
    return $query;
}
```

**Uso:**
```php
Receta::conIngredientes(['huevo', 'patata'])->get();
```

### Scope para filtrar por rango de likes

```php
public function scopeConLikesEntre($query, int $min, int $max)
{
    return $query->withCount('likes')
        ->having('likes_count', '>=', $min)
        ->having('likes_count', '<=', $max);
}
```

### Scope para recetas del mes

```php
public function scopeDelMes($query)
{
    return $query->whereMonth('created_at', now()->month)
        ->whereYear('created_at', now()->year);
}
```

---

## Requisitos cumplidos

- [x] Filtrado por ingrediente (contiene texto)
- [x] Ordenar por popularidad (mayor número de likes)
- [x] Uso de Eloquent Scopes en modelo Receta
- [x] Controlador limpio y legible
- [x] Tests funcionales (13 tests)
- [x] Documentación completa
- [x] Compatibilidad con filtros existentes

---

**Fecha:** 27 de enero de 2026  
**Laravel:** 12.x | **PHP:** 8.2+  
**Ver comandos HTTPie en:** `docs/HTTPIE_FILTROS_AVANZADOS.md`
