# Implementación de Imagen del Plato Final - Documentación Técnica

## Resumen

Sistema de subida de imágenes implementado con validación, almacenamiento en disco público y URL accesibles vía API.

## Arquitectura

### 1. Base de datos

**Migración:** `2026_01_27_150000_add_imagen_url_to_recetas_table.php`

```sql
ALTER TABLE recetas ADD COLUMN imagen_url VARCHAR(255) NULL;
```

**Campo:**
- `imagen_url` - Almacena la ruta relativa de la imagen (ej: `recetas/abc123.jpg`)
- **Nullable** - La imagen es opcional

---

### 2. Modelo Receta

**Fillable actualizado:**
```php
protected $fillable = [
    'user_id',
    'titulo',
    'descripcion',
    'instrucciones',
    'publicada',
    'imagen_url',  // ← Agregado
];
```

**Accessor para URL completa:**
```php
public function getImagenUrlCompletaAttribute(): ?string
{
    if (!$this->imagen_url) {
        return null;
    }
    
    if (str_starts_with($this->imagen_url, 'http')) {
        return $this->imagen_url;
    }
    
    return asset('storage/' . $this->imagen_url);
}
```

**Comportamiento:**
- Si `imagen_url` es `null` → devuelve `null`
- Si ya es una URL completa → la devuelve tal cual
- Si es una ruta relativa → construye URL absoluta con `asset()`

**Appends actualizado:**
```php
protected $appends = ['likes_count', 'imagen_url_completa'];
```

---

### 3. RecetaResource

**Campo agregado:**
```php
'imagen_url' => $this->imagen_url_completa,
```

**Respuesta JSON:**
```json
{
  "id": 1,
  "titulo": "Paella",
  "imagen_url": "http://localhost/storage/recetas/xyz.jpg",
  ...
}
```

---

### 4. RecetaController

#### Método `store()`

**Validación:**
```php
'imagen' => 'nullable|image|mimes:jpeg,png,jpg|max:2048'
```

**Procesamiento:**
```php
if ($request->hasFile('imagen')) {
    $imagen = $request->file('imagen');
    $path = $imagen->store('recetas', 'public');
    $imagenUrl = $path;
}
```

**Comportamiento:**
- `store('recetas', 'public')` guarda en `storage/app/public/recetas/`
- Laravel genera un nombre único automáticamente
- Devuelve la ruta relativa: `recetas/abc123.jpg`

---

#### Método `update()`

**Validación:**
```php
'imagen' => 'nullable|image|mimes:jpeg,png,jpg|max:2048'
```

**Procesamiento:**
```php
if ($request->hasFile('imagen')) {
    // Eliminar imagen anterior
    if ($receta->imagen_url) {
        \Storage::disk('public')->delete($receta->imagen_url);
    }
    
    // Guardar nueva imagen
    $imagen = $request->file('imagen');
    $path = $imagen->store('recetas', 'public');
    $data['imagen_url'] = $path;
}
```

**Comportamiento:**
- Si se envía una nueva imagen, elimina la anterior
- Guarda la nueva imagen
- Si NO se envía imagen, mantiene la actual

---

#### Método `destroy()`

**Limpieza:**
```php
if ($receta->imagen_url) {
    \Storage::disk('public')->delete($receta->imagen_url);
}

$receta->delete();
```

**Comportamiento:**
- Elimina la imagen del disco antes de eliminar la receta
- Evita "huérfanos" en el almacenamiento

---

### 5. Validación

| Campo | Reglas |
|-------|--------|
| `imagen` | `nullable` - Es opcional |
| | `image` - Debe ser una imagen |
| | `mimes:jpeg,png,jpg` - Formatos válidos |
| | `max:2048` - Máximo 2MB (2048 KB) |

---

### 6. Almacenamiento

**Disco:** `public`

**Ruta en servidor:**
```
storage/app/public/recetas/
├── aBcD1234.jpg
├── eFgH5678.png
└── iJkL9012.jpg
```

**Enlace simbólico:**
```
public/storage → storage/app/public
```

**Comando necesario (una sola vez):**
```bash
php artisan storage:link
```

**URLs accesibles:**
```
http://localhost/storage/recetas/aBcD1234.jpg
```

---

### 7. Tests

**Archivo:** `tests/Feature/RecetaImagenTest.php`

**Cobertura: 12 tests**
- Crear receta con imagen
- Crear receta sin imagen
- Validación: solo imágenes
- Validación: tamaño máximo 2MB
- Validación: formatos válidos (jpeg, png, jpg)
- Actualizar receta agregando imagen
- Actualizar receta reemplazando imagen
- Eliminar receta elimina imagen
- RecetaResource incluye imagen_url
- Receta sin imagen devuelve null
- Actualizar texto sin modificar imagen

**Uso de `Storage::fake()`:**
```php
Storage::fake('public');

// Crear imagen fake
$imagen = UploadedFile::fake()->image('plato.jpg')->size(1024);

// Verificar que se guardó
Storage::disk('public')->assertExists($path);
```

---

## Flujo de datos

### Crear receta con imagen

```
Cliente HTTP (multipart/form-data)
    ↓
POST /api/recetas
    ↓
Middleware: auth:sanctum
    ↓
RecetaController::store()
    ↓
Validación (imagen: nullable|image|max:2048)
    ↓
$request->hasFile('imagen')? 
    ↓ SÍ
$imagen->store('recetas', 'public')
    ↓
Guardar ruta: recetas/abc123.jpg
    ↓
Receta::create([..., 'imagen_url' => $path])
    ↓
Response JSON (201)
{
  "imagen_url": "http://localhost/storage/recetas/abc123.jpg"
}
```

### Actualizar imagen

```
Cliente HTTP (multipart/form-data)
    ↓
PUT /api/recetas/{id}
    ↓
RecetaController::update()
    ↓
$request->hasFile('imagen')?
    ↓ SÍ
Eliminar imagen anterior:
Storage::disk('public')->delete($receta->imagen_url)
    ↓
Guardar nueva imagen:
$imagen->store('recetas', 'public')
    ↓
$receta->update(['imagen_url' => $newPath])
    ↓
Response JSON (200)
```

---

## Decisiones técnicas

### ¿Por qué almacenar en `public`?

**Ventajas:**
- Imágenes accesibles directamente vía URL
- No requiere controlador adicional para servir imágenes
- Mejor rendimiento (servidor web sirve archivos estáticos)

**Alternativa descartada:** Almacenar en `storage/app/private` requeriría:
- Un controlador para servir las imágenes
- Mayor carga en PHP
- Más complejo

---

### ¿Por qué usar accessor en lugar de guardar URL completa?

**Ventaja:**
- Flexibilidad: si cambia el dominio, no hay que actualizar BD
- Menos espacio en BD
- Portabilidad entre entornos (local, staging, producción)

**Ejemplo:**
```
BD: recetas/abc.jpg
Local: http://localhost/storage/recetas/abc.jpg
Producción: https://api.miapp.com/storage/recetas/abc.jpg
```

---

### ¿Por qué eliminar imagen anterior al actualizar?

**Razón:**
- Evitar acumulación de archivos huérfanos
- Ahorrar espacio en disco
- Mantenimiento automático

**Importante:** Solo se elimina si se envía una nueva imagen.

---

## Ejemplos de uso

### Con HTTPie (multipart)

```bash
http --form POST :8000/api/recetas \
  "Authorization:Bearer $TOKEN" \
  titulo="Paella" \
  descripcion="..." \
  instrucciones="..." \
  imagen@paella.jpg
```

### Con curl

```bash
curl -X POST http://localhost/api/recetas \
  -H "Authorization: Bearer $TOKEN" \
  -F "titulo=Paella" \
  -F "descripcion=..." \
  -F "instrucciones=..." \
  -F "imagen=@paella.jpg"
```

### Con JavaScript (fetch)

```javascript
const formData = new FormData();
formData.append('titulo', 'Paella');
formData.append('descripcion', '...');
formData.append('instrucciones', '...');
formData.append('imagen', fileInput.files[0]);

const response = await fetch('/api/recetas', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`,
  },
  body: formData
});
```

---

## Configuración necesaria

### 1. Crear enlace simbólico (una sola vez)

```bash
php artisan storage:link
```

**Esto crea:**
```
public/storage → ../storage/app/public
```

### 2. Verificar permisos (en producción)

```bash
chmod -R 775 storage
chmod -R 775 bootstrap/cache
```

---

## Troubleshooting

### Error: "The imagen field must be an image"

**Causa:** El archivo no es una imagen válida

**Solución:** Verificar que sea JPEG, PNG o JPG

---

### Error: "The imagen field may not be greater than 2048 kilobytes"

**Causa:** La imagen supera 2MB

**Solución:** Reducir tamaño o modificar validación

---

### Error 404 al acceder a la imagen

**Causa:** No se ejecutó `php artisan storage:link`

**Solución:**
```bash
php artisan storage:link
```

---

### Imágenes no se eliminan

**Causa:** Error en el código de eliminación

**Verificación:**
```bash
# Ver archivos en storage
ls -la storage/app/public/recetas/
```

---

## Mejoras futuras (opcionales)

### Redimensionar imágenes

```php
use Intervention\Image\Facades\Image;

$imagen = Image::make($file)
    ->fit(800, 600)
    ->save(storage_path('app/public/recetas/' . $filename));
```

### Múltiples imágenes

```php
// Migración
$table->json('imagenes')->nullable();

// Controlador
foreach ($request->file('imagenes') as $imagen) {
    $paths[] = $imagen->store('recetas', 'public');
}
```

### Optimización automática

```php
use Spatie\ImageOptimizer\OptimizerChainFactory;

$optimizer = OptimizerChainFactory::create();
$optimizer->optimize($imagePath);
```

---

## Requisitos cumplidos

- [x] Migración para campo `imagen_url`
- [x] Validación: jpeg, png, jpg, max 2MB
- [x] Almacenamiento en `storage/app/public/recetas/`
- [x] Accessor para URL absoluta
- [x] RecetaResource incluye `imagen_url`
- [x] Soporte en `store()` y `update()`
- [x] Eliminación automática al actualizar/borrar
- [x] 12 tests funcionales
- [x] Documentación completa

---

**Fecha:** 27 de enero de 2026  
**Laravel:** 12.x | **PHP:** 8.2+  
**Ver comandos HTTPie en:** `docs/HTTPIE_IMAGENES.md`
