# Documentación Swagger/OpenAPI - API de Recetas

## 📋 Resumen

La API está completamente documentada con **Swagger/OpenAPI 3.0** usando el paquete **l5-swagger**.

**✅ IMPORTANTE:** El archivo JSON de OpenAPI ya está pre-generado en `storage/api-docs/api-docs.json`. No necesitas ejecutar comandos de generación.

---

## 🚀 Acceder a la documentación

### Interfaz interactiva Swagger UI

Una vez iniciado el servidor, accede a:

```
http://localhost/api/documentation
```

O si usas puerto 8000:

```
http://localhost:8000/api/documentation
```

**¡Listo!** La interfaz interactiva cargará automáticamente el JSON pre-generado.

---

### Acceso directo al JSON

Si prefieres acceder directamente al archivo JSON:

```
http://localhost/storage/api-docs/api-docs.json
```

---

## 🔐 Autenticación en Swagger UI

### Paso 1: Obtener token de Sanctum

Primero debes obtener un token válido. Puedes hacerlo:

**Opción A: Desde HTTPie/Curl**

```bash
http POST :8000/api/auth/login email=admin@demo.local password=password
```

**Respuesta:**
```json
{
  "token": "1|abcdefghijklmnopqrstuvwxyz123456789"
}
```

**Opción B: Desde Swagger UI**

1. Busca el endpoint `POST /api/auth/login` en la sección **Autenticación**
2. Click en "Try it out"
3. Ingresa:
   ```json
   {
     "email": "admin@demo.local",
     "password": "password"
   }
   ```
4. Click en "Execute"
5. Copia el token de la respuesta

---

### Paso 2: Configurar el token en Swagger

1. Click en el botón **"Authorize"** (icono de candado) en la esquina superior derecha
2. En el campo "Value", ingresa el token **completo** (incluyendo el prefijo):
   ```
   1|abcdefghijklmnopqrstuvwxyz123456789
   ```
3. Click en **"Authorize"**
4. Click en **"Close"**

**¡Listo!** Ahora todos tus requests incluirán el header:
```
Authorization: Bearer 1|abcdefghijklmnopqrstuvwxyz123456789
```

---

## 📚 Endpoints documentados

### Recetas (CRUD completo)
- ✅ `GET /api/recetas` - Listar recetas con filtros
- ✅ `POST /api/recetas` - Crear receta (con imagen opcional)
- ✅ `GET /api/recetas/{id}` - Ver receta específica
- ✅ `PUT /api/recetas/{id}` - Actualizar receta
- ✅ `DELETE /api/recetas/{id}` - Eliminar receta

### Ingredientes (CRUD completo)
- ✅ `GET /api/recetas/{receta}/ingredientes` - Listar ingredientes
- ✅ `POST /api/recetas/{receta}/ingredientes` - Agregar ingrediente
- ✅ `GET /api/ingredientes/{id}` - Ver ingrediente
- ✅ `PUT /api/ingredientes/{id}` - Actualizar ingrediente
- ✅ `DELETE /api/ingredientes/{id}` - Eliminar ingrediente

---

## 🔍 Características de la documentación

### Filtros avanzados

En el endpoint `GET /api/recetas` puedes ver todos los filtros disponibles:

- **q**: Búsqueda en título y descripción
- **ingrediente**: Filtrar por ingrediente
- **sort**: Ordenar (popular, titulo, created_at, con prefijo `-` para descendente)
- **page**: Paginación
- **per_page**: Resultados por página

### Ejemplos incluidos

Cada endpoint tiene valores de ejemplo para facilitar las pruebas:

```json
{
  "titulo": "Paella Valenciana",
  "descripcion": "Auténtica paella española",
  "instrucciones": "1. Calentar aceite 2. Sofreír..."
}
```

### Códigos de respuesta

Cada endpoint documenta todos los códigos de respuesta posibles:

- **200**: OK - Operación exitosa
- **201**: Created - Recurso creado
- **401**: Unauthorized - No autenticado
- **403**: Forbidden - No autorizado (sin permisos)
- **404**: Not Found - Recurso no encontrado
- **422**: Unprocessable Entity - Error de validación

---

## 🛠️ Configuración técnica

### Ubicación del archivo de configuración

```
config/l5-swagger.php
```

### Ubicación de la documentación generada

```
storage/api-docs/api-docs.json
```

### Regenerar documentación

**Caso normal:** No necesitas hacer nada. El JSON ya existe.

**Caso avanzado:** Si modificaste muchas anotaciones en los controladores y quieres que Swagger refleje los cambios:

```bash
# Opción 1: Intentar regenerar con l5-swagger (puede fallar)
php artisan l5-swagger:generate

# Opción 2: Editar manualmente storage/api-docs/api-docs.json
# (Si swagger-php falla, esta es la más práctica)
```

**Nota:** Para un proyecto educativo, el JSON pre-generado es suficiente para documentación y testing desde el navegador.

---

## 📝 Anotaciones implementadas

### En Controller.php (base)

```php
/**
 * @OA\Info(
 *     title="API de Recetas - Laravel 12",
 *     version="1.0.0",
 *     description="API REST para gestión de recetas..."
 * )
 * 
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 * 
 * @OA\Tag(name="Recetas", description="CRUD de recetas")
 * @OA\Tag(name="Ingredientes", description="Gestión de ingredientes")
 */
```

### En cada método del controlador

```php
/**
 * @OA\Get(
 *     path="/recetas",
 *     tags={"Recetas"},
 *     summary="Listar todas las recetas",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(...),
 *     @OA\Response(...)
 * )
 */
public function index(Request $request)
```

---

## 🎯 Casos de uso

### Caso 1: Probar creación de receta

1. Autorízate en Swagger
2. Ve a `POST /api/recetas`
3. Click "Try it out"
4. Modifica el ejemplo:
   ```json
   {
     "titulo": "Mi Receta de Prueba",
     "descripcion": "Descripción de prueba",
     "instrucciones": "Paso 1, Paso 2, Paso 3"
   }
   ```
5. Click "Execute"
6. Verás la respuesta con status 201 y el ID de la receta creada

### Caso 2: Probar filtros

1. Ve a `GET /api/recetas`
2. Click "Try it out"
3. Ingresa en el parámetro `ingrediente`: `huevo`
4. Ingresa en el parámetro `sort`: `popular`
5. Click "Execute"
6. Verás las recetas filtradas y ordenadas

### Caso 3: Agregar ingrediente

1. Ve a `POST /api/recetas/{receta}/ingredientes`
2. Click "Try it out"
3. En `receta`, ingresa el ID de una receta (ej: `1`)
4. Modifica el body:
   ```json
   {
     "nombre": "Arroz",
     "cantidad": "400",
     "unidad": "g"
   }
   ```
5. Click "Execute"

---

## 🔧 Troubleshooting

### El JSON ya está pre-generado

**Buena noticia:** El archivo `storage/api-docs/api-docs.json` ya existe y está configurado.

**No necesitas ejecutar:**
```bash
# ❌ NO NECESARIO - El JSON ya existe
php artisan l5-swagger:generate
```

### Error: "swagger.json not found"

**Solución:**

1. Verifica que el archivo existe:
```bash
ls -la storage/api-docs/api-docs.json
```

2. Si no existe, cópialo del repositorio o crea uno nuevo.

### Los cambios en anotaciones no se reflejan

**Importante:** El JSON es estático. Si modificas los controladores y quieres que Swagger refleje los cambios:

```bash
# Opción 1: Regenerar (si swagger-php funciona en tu sistema)
php artisan l5-swagger:generate

# Opción 2: Actualizar manualmente el JSON en storage/api-docs/api-docs.json
```

### No aparece el botón "Authorize"

**Verificación:** Asegúrate de que en el JSON esté la seguridad definida:

```json
"components": {
  "securitySchemes": {
    "bearerAuth": {
      "type": "http",
      "scheme": "bearer"
    }
  }
}
```

**Si no aparece:**
1. Verifica que el JSON en `storage/api-docs/api-docs.json` tenga esta sección
2. Recarga la página del navegador (Ctrl+F5)

### "Required @OA\PathItem() not found"

**Problema:** Error común al ejecutar `php artisan l5-swagger:generate`

**Solución:** 
- ✅ El JSON pre-generado evita este problema completamente
- ❌ No intentes regenerar si swagger-php tiene problemas de parsing
- ✅ Usa el JSON que ya existe en el repositorio

---

## 📖 Ventajas de Swagger

### Para desarrolladores

1. **Documentación siempre actualizada**: Si cambia el código, se documenta
2. **Testing integrado**: Probar endpoints directamente desde el navegador
3. **Ejemplos visuales**: Ver estructuras de request/response
4. **Descubrimiento de API**: Explorar todos los endpoints disponibles

### Para el equipo

1. **Frontend conoce exactamente qué esperar**
2. **Backend documenta mientras desarrolla**
3. **QA puede probar sin Postman**
4. **Stakeholders pueden ver la API funcional**

---

## 🚀 Próximos pasos (opcional)

### Documentar más endpoints

Para documentar Likes y Comentarios, agrega anotaciones similares en:
- `app/Http/Controllers/Api/LikeController.php`
- `app/Http/Controllers/Api/ComentarioController.php`

### Exportar documentación

El archivo `storage/api-docs/api-docs.json` puede ser importado en:
- Postman
- Insomnia
- Otros clientes REST

---

## 📚 Referencias

- **Swagger UI**: http://localhost/api/documentation
- **JSON OpenAPI**: http://localhost/storage/api-docs/api-docs.json
- **Documentación l5-swagger**: https://github.com/DarkaOnLine/L5-Swagger
- **OpenAPI Specification**: https://swagger.io/specification/

---

**Fecha:** 27 de enero de 2026  
**Paquete:** darkaonline/l5-swagger  
**Versión OpenAPI:** 3.0
