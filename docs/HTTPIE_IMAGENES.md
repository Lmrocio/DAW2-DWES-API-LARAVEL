# Comandos HTTPie - Subida de Imágenes

## Autenticación previa

```bash
# Login
http POST :8000/api/auth/login \
  email=admin@demo.local password=password

# Guardar token
export TOKEN=<tu_token_aqui>
```

---

## Subida de Imágenes

### 1. Crear receta con imagen

**Nota:** Para subir archivos con HTTPie, usa `--form` o `-f`

```bash
http --form POST :8000/api/recetas \
  "Authorization:Bearer $TOKEN" \
  titulo="Paella Valenciana" \
  descripcion="Auténtica paella española" \
  instrucciones="1. Calentar aceite 2. Sofreír el pollo..." \
  imagen@/ruta/a/tu/imagen.jpg
```

**Ejemplo con ruta absoluta:**
```bash
http --form POST :8000/api/recetas \
  "Authorization:Bearer $TOKEN" \
  titulo="Tortilla de patatas" \
  descripcion="Clásica tortilla española" \
  instrucciones="Pelar patatas, freír, batir huevos..." \
  imagen@C:/Users/tu_usuario/Pictures/tortilla.jpg
```

**Respuesta (201 Created):**
```json
{
  "id": 1,
  "user_id": 1,
  "titulo": "Paella Valenciana",
  "descripcion": "Auténtica paella española",
  "instrucciones": "1. Calentar aceite...",
  "imagen_url": "recetas/aBcD1234.jpg",
  "publicada": false,
  "created_at": "2026-01-27T15:00:00.000000Z",
  "updated_at": "2026-01-27T15:00:00.000000Z"
}
```

---

### 2. Crear receta SIN imagen (opcional)

```bash
http POST :8000/api/recetas \
  "Authorization:Bearer $TOKEN" \
  titulo="Gazpacho" \
  descripcion="Sopa fría andaluza" \
  instrucciones="Triturar tomates, pepino, pimiento..."
```

**Respuesta:**
```json
{
  "id": 2,
  "imagen_url": null,
  ...
}
```

---

### 3. Ver receta con URL completa de la imagen

```bash
http GET :8000/api/recetas/1 \
  "Authorization:Bearer $TOKEN"
```

**Respuesta:**
```json
{
  "id": 1,
  "titulo": "Paella Valenciana",
  "descripcion": "Auténtica paella española",
  "imagen_url": "http://localhost/storage/recetas/aBcD1234.jpg",
  "ingredientes": [...],
  "likes_count": 5,
  "comentarios": [...]
}
```

**Nota:** La URL es completa y accesible desde el navegador/app.

---

### 4. Actualizar receta agregando imagen

```bash
http --form PUT :8000/api/recetas/1 \
  "Authorization:Bearer $TOKEN" \
  titulo="Paella Valenciana (actualizada)" \
  imagen@/ruta/a/nueva_imagen.jpg
```

---

### 5. Actualizar receta reemplazando imagen

```bash
# La imagen anterior se elimina automáticamente
http --form PUT :8000/api/recetas/1 \
  "Authorization:Bearer $TOKEN" \
  imagen@/ruta/a/imagen_actualizada.jpg
```

**Comportamiento:**
- Se elimina la imagen antigua del servidor
- Se guarda la nueva imagen
- La receta se actualiza con la nueva ruta

---

### 6. Actualizar receta sin tocar la imagen

```bash
# Si NO envías el campo 'imagen', la imagen actual se mantiene
http PUT :8000/api/recetas/1 \
  "Authorization:Bearer $TOKEN" \
  titulo="Nuevo título"
```

---

## Validaciones

### Formatos válidos
- **jpeg**
- **jpg**
- **png**

### Tamaño máximo
- **2 MB** (2048 KB)

---

## Casos de error

### Error: Formato no válido

```bash
http --form POST :8000/api/recetas \
  "Authorization:Bearer $TOKEN" \
  titulo="Receta" \
  descripcion="..." \
  instrucciones="..." \
  imagen@documento.pdf
```

**Respuesta (422 Validation Error):**
```json
{
  "message": "The imagen field must be a file of type: jpeg, png, jpg.",
  "errors": {
    "imagen": [
      "The imagen field must be a file of type: jpeg, png, jpg."
    ]
  }
}
```

---

### Error: Tamaño excedido (más de 2MB)

```bash
http --form POST :8000/api/recetas \
  "Authorization:Bearer $TOKEN" \
  titulo="Receta" \
  descripcion="..." \
  instrucciones="..." \
  imagen@imagen_grande.jpg  # 5MB
```

**Respuesta (422):**
```json
{
  "message": "The imagen field must not be greater than 2048 kilobytes.",
  "errors": {
    "imagen": [
      "The imagen field must not be greater than 2048 kilobytes."
    ]
  }
}
```

---

## Acceder a la imagen desde el navegador

Una vez subida, la imagen es accesible públicamente:

```
http://localhost/storage/recetas/aBcD1234.jpg
```

**Importante:** Debes ejecutar el comando de enlace simbólico:

```bash
php artisan storage:link
```

Esto crea un enlace simbólico de `storage/app/public` → `public/storage`

---

## Flujo completo con imagen

```bash
# 1. Autenticarse
http POST :8000/api/auth/login email=user@demo.local password=password
export TOKEN=<tu_token>

# 2. Crear receta con imagen
http --form POST :8000/api/recetas \
  "Authorization:Bearer $TOKEN" \
  titulo="Paella Valenciana" \
  descripcion="Deliciosa paella" \
  instrucciones="Paso 1, Paso 2..." \
  imagen@paella.jpg

# Respuesta: { "id": 1, "imagen_url": "recetas/xyz.jpg", ... }

# 3. Agregar ingredientes
http POST :8000/api/recetas/1/ingredientes \
  "Authorization:Bearer $TOKEN" \
  nombre="Arroz" cantidad="400" unidad="g"

# 4. Dar like
http POST :8000/api/recetas/1/like \
  "Authorization:Bearer $TOKEN"

# 5. Comentar
http POST :8000/api/recetas/1/comentarios \
  "Authorization:Bearer $TOKEN" \
  texto="¡Se ve deliciosa en la foto!"

# 6. Ver receta completa con imagen
http GET :8000/api/recetas/1 \
  "Authorization:Bearer $TOKEN"
```

**Respuesta completa:**
```json
{
  "id": 1,
  "titulo": "Paella Valenciana",
  "descripcion": "Deliciosa paella",
  "imagen_url": "http://localhost/storage/recetas/xyz.jpg",
  "ingredientes": [
    { "nombre": "Arroz", "cantidad": "400", "unidad": "g" }
  ],
  "likes_count": 1,
  "liked_by_user": true,
  "comentarios": [
    { "texto": "¡Se ve deliciosa en la foto!", "user_name": "Usuario" }
  ],
  "comentarios_count": 1
}
```

---

## Curl alternativo (si HTTPie no funciona bien con archivos)

```bash
# Crear receta con imagen usando curl
curl -X POST http://localhost/api/recetas \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" \
  -F "titulo=Paella Valenciana" \
  -F "descripcion=Deliciosa paella" \
  -F "instrucciones=Paso 1, Paso 2..." \
  -F "imagen=@paella.jpg"
```

---

## Notas técnicas

### Almacenamiento

Las imágenes se guardan en:
```
storage/app/public/recetas/
```

Accesibles vía:
```
public/storage/recetas/  (enlace simbólico)
```

### Nombres de archivo

Laravel genera nombres únicos automáticamente para evitar colisiones:
```
recetas/aBcDeFgH1234567890.jpg
```

### Eliminación automática

Cuando actualizas o eliminas una receta, la imagen antigua se elimina automáticamente del disco.

### URL absoluta

El accessor `imagen_url_completa` convierte:
```
recetas/abc.jpg  →  http://localhost/storage/recetas/abc.jpg
```

---

## Configuración necesaria

### 1. Crear enlace simbólico

```bash
php artisan storage:link
```

**Solo necesitas ejecutar esto UNA VEZ.**

### 2. Verificar permisos (en producción)

```bash
chmod -R 775 storage
chmod -R 775 bootstrap/cache
```

---

## Troubleshooting

### Imagen no se muestra (404)

**Problema:** No se ejecutó `php artisan storage:link`

**Solución:**
```bash
php artisan storage:link
```

### Error de permisos al subir

**Problema:** El directorio `storage/app/public` no tiene permisos de escritura

**Solución:**
```bash
chmod -R 775 storage
```

### Imagen muy grande

**Problema:** La imagen supera 2MB

**Solución:** Reducir el tamaño de la imagen antes de subirla, o modificar la validación en el controlador si es necesario.

---

**Documentación técnica completa en:** `docs/11_imagenes.md`
