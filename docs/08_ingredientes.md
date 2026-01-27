# Implementación de Ingredientes - Documentación Técnica

## 📋 Resumen

Se ha implementado la funcionalidad completa de **Ingredientes** para la API REST de Recetas, siguiendo exactamente los patrones arquitectónicos del proyecto existente.

---

## 🏗️ Arquitectura implementada

### 1. Base de datos

**Migración:** `2026_01_27_120000_create_ingredientes_table.php`

```sql
CREATE TABLE ingredientes (
    id BIGSERIAL PRIMARY KEY,
    receta_id BIGINT NOT NULL,
    nombre VARCHAR(255) NOT NULL,
    cantidad VARCHAR(255) NOT NULL,
    unidad VARCHAR(255) NOT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (receta_id) REFERENCES recetas(id) ON DELETE CASCADE
);
```

**Decisión técnica:** 
- `cantidad` es STRING para permitir valores como "2-3", "1/2", "al gusto"
- `CASCADE ON DELETE`: Si se elimina una receta, se eliminan automáticamente sus ingredientes

---

### 2. Modelo de datos

**Archivo:** `app/Models/Ingrediente.php`

**Relación:** **1:N (Uno a Muchos)**
- Una Receta tiene muchos Ingredientes
- Un Ingrediente pertenece a una Receta

**Justificación de la relación 1:N:**
1. Los ingredientes tienen cantidades y unidades **específicas** para cada receta
2. La misma "Harina" puede aparecer en diferentes recetas con distintas cantidades
3. Simplicidad: No necesitamos compartir ingredientes entre recetas
4. Rendimiento: Evita complejidad de tablas intermedias
5. Mantenibilidad: Más fácil de entender y mantener

**Atributos fillable:**
```php
protected $fillable = [
    'receta_id',
    'nombre',
    'cantidad',
    'unidad',
];
```

---

### 3. API Resources

**Archivo:** `app/Http/Resources/IngredienteResource.php`

Serializa el modelo Ingrediente a JSON:
```json
{
  "id": 1,
  "receta_id": 1,
  "nombre": "Huevo",
  "cantidad": "3",
  "unidad": "ud",
  "created_at": "2026-01-27T12:00:00.000000Z",
  "updated_at": "2026-01-27T12:00:00.000000Z"
}
```

**Actualización en RecetaResource:**
- Se agregó `ingredientes` usando `whenLoaded()` para evitar N+1 queries
- Uso de `IngredienteResource::collection()` para transformar la colección

---

### 4. Políticas de autorización (Policy)

**Archivo:** `app/Policies/IngredientePolicy.php`

**Reglas de negocio:**

| Acción | Propietario | Admin | Otro usuario |
|--------|-------------|-------|--------------|
| create | ✅ | ✅ | ❌ |
| update | ✅ | ✅ | ❌ |
| delete | ✅ | ✅ | ❌ |

**Implementación:**
```php
public function update(User $user, Ingrediente $ingrediente): bool
{
    if ($user->hasRole('admin')) {
        return true;
    }
    // Solo el dueño de la receta puede modificar ingredientes
    return $user->id === $ingrediente->receta->user_id;
}
```

**Patrón consistente:** Igual que `RecetaPolicy`

---

### 5. Controlador

**Archivo:** `app/Http/Controllers/Api/IngredienteController.php`

**Métodos implementados:**

| Método | Ruta | Descripción |
|--------|------|-------------|
| index | GET /recetas/{receta}/ingredientes | Lista ingredientes de una receta |
| store | POST /recetas/{receta}/ingredientes | Crea un ingrediente |
| show | GET /ingredientes/{ingrediente} | Muestra un ingrediente |
| update | PUT/PATCH /ingredientes/{ingrediente} | Actualiza un ingrediente |
| destroy | DELETE /ingredientes/{ingrediente} | Elimina un ingrediente |

**Características:**
- Validación con `$request->validate()`
- Autorización con `$this->authorize()`
- Uso de Resources para respuestas JSON
- Mensajes de respuesta claros

---

### 6. Rutas API

**Archivo:** `routes/api.php`

```php
Route::middleware('auth:sanctum')->group(function () {
    // Rutas anidadas para ingredientes de una receta
    Route::get('recetas/{receta}/ingredientes', [IngredienteController::class, 'index']);
    Route::post('recetas/{receta}/ingredientes', [IngredienteController::class, 'store']);
    
    // Rutas para operaciones CRUD directas sobre ingredientes
    Route::apiResource('ingredientes', IngredienteController::class)
        ->except(['index', 'store']);
});
```

**Diseño RESTful:**
- Rutas anidadas para creación y listado (contexto de la receta)
- Rutas directas para operaciones sobre ingredientes específicos
- Todas las rutas protegidas con `auth:sanctum`

---

### 7. Validaciones

```php
[
    'nombre' => 'required|string|max:100',
    'cantidad' => 'required|string|max:50',
    'unidad' => 'required|string|max:50',
]
```

**Para updates:**
```php
[
    'nombre' => 'sometimes|required|string|max:100',
    'cantidad' => 'sometimes|required|string|max:50',
    'unidad' => 'sometimes|required|string|max:50',
]
```

---

### 8. Tests

**Archivo:** `tests/Feature/IngredienteTest.php`

**Cobertura de tests:**
- ✅ Listar ingredientes de una receta
- ✅ Propietario puede agregar ingredientes
- ✅ Usuario NO puede agregar ingredientes a recetas ajenas
- ✅ Admin puede agregar ingredientes a cualquier receta
- ✅ Validación de campos requeridos
- ✅ Propietario puede actualizar ingredientes
- ✅ Usuario NO puede actualizar ingredientes ajenos
- ✅ Propietario puede eliminar ingredientes
- ✅ Usuario NO puede eliminar ingredientes ajenos
- ✅ Admin puede eliminar ingredientes de cualquier receta
- ✅ Ver receta incluye ingredientes

**Total:** 11 tests

---

### 9. Factory para Testing

**Archivo:** `database/factories/IngredienteFactory.php`

Genera ingredientes realistas para pruebas:
- Huevo, Harina, Leche, Azúcar, Sal, etc.
- Con cantidades y unidades apropiadas

---

## 🔄 Flujo de datos

### Crear ingrediente

```
Cliente HTTP
    ↓
POST /api/recetas/1/ingredientes
    ↓
Middleware: auth:sanctum
    ↓
IngredienteController::store()
    ↓
Autorización: RecetaPolicy::update()
    ↓
Validación: Request
    ↓
Creación: $receta->ingredientes()->create()
    ↓
IngredienteResource::toArray()
    ↓
JSON Response (201)
```

### Actualizar ingrediente

```
Cliente HTTP
    ↓
PUT /api/ingredientes/1
    ↓
Middleware: auth:sanctum
    ↓
IngredienteController::update()
    ↓
Autorización: IngredientePolicy::update()
    ↓
Validación: Request
    ↓
Actualización: $ingrediente->update()
    ↓
IngredienteResource::toArray()
    ↓
JSON Response (200)
```

---

## 📊 Ejemplos de uso

### Crear receta con ingredientes

```bash
# 1. Crear receta
http POST :8000/api/recetas \
  "Authorization:Bearer $TOKEN" \
  titulo="Tortilla de patatas" \
  descripcion="Clásica tortilla española" \
  instrucciones="Pelar y cortar patatas..."

# Respuesta: { "id": 1, ... }

# 2. Agregar ingredientes
http POST :8000/api/recetas/1/ingredientes \
  "Authorization:Bearer $TOKEN" \
  nombre="Huevo" cantidad="4" unidad="ud"

http POST :8000/api/recetas/1/ingredientes \
  "Authorization:Bearer $TOKEN" \
  nombre="Patata" cantidad="500" unidad="g"

http POST :8000/api/recetas/1/ingredientes \
  "Authorization:Bearer $TOKEN" \
  nombre="Aceite de oliva" cantidad="100" unidad="ml"

# 3. Ver receta completa
http GET :8000/api/recetas/1 "Authorization:Bearer $TOKEN"
```

---

## ✅ Checklist de requisitos cumplidos

### Requisitos obligatorios

- [x] Modelo `Ingrediente` creado
- [x] Relación 1:N implementada y justificada
- [x] Campos: nombre, cantidad, unidad, receta_id
- [x] Migración con restricciones (foreign key, cascade)
- [x] IngredienteResource creado
- [x] IngredientePolicy creado
- [x] Solo propietario o admin puede modificar
- [x] IngredienteController creado
- [x] Rutas anidadas implementadas
- [x] RecetaResource incluye ingredientes
- [x] Tests funcionales creados

### Patrones del proyecto seguidos

- [x] API Resources para serialización JSON
- [x] Policies para autorización
- [x] Validación en controladores
- [x] Rutas RESTful
- [x] Middleware auth:sanctum
- [x] Factory para testing
- [x] Feature tests con RefreshDatabase
- [x] Estructura de carpetas consistente
- [x] Nomenclatura consistente

---

## 🚀 Próximos pasos

1. Ejecutar migraciones: `php artisan migrate`
2. Registrar la policy en `AppServiceProvider` si es necesario
3. Ejecutar tests: `php artisan test --filter IngredienteTest`
4. Probar endpoints con HTTPie (ver `docs/HTTPIE_INGREDIENTES.md`)

---

## 📝 Notas técnicas

### ¿Por qué 1:N y no N:M?

**1:N es suficiente porque:**
- No necesitamos reutilizar ingredientes entre recetas
- Cada receta tiene cantidades específicas
- Simplifica el modelo de datos
- Evita complejidad innecesaria

**N:M sería útil si:**
- Quisiéramos gestionar un catálogo de ingredientes compartido
- Necesitáramos estadísticas globales de ingredientes
- Tuviéramos que normalizar nombres de ingredientes

Para este proyecto, **1:N es la decisión correcta**.

### Manejo de errores

Los errores de validación devuelven 422 con detalles:
```json
{
  "message": "The nombre field is required.",
  "errors": {
    "nombre": ["The nombre field is required."],
    "cantidad": ["The cantidad field is required."]
  }
}
```

Los errores de autorización devuelven 403:
```json
{
  "message": "This action is unauthorized."
}
```

---

**Fecha de implementación:** 27 de enero de 2026  
**Versión de Laravel:** 12.x  
**Versión de PHP:** 8.2+
