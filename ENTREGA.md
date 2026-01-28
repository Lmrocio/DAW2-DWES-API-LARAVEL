# ENTREGA - API de Recetas Laravel 12

**Alumna:** Rocío Luque Montes  
**Módulo:** DWES - Desarrollo Web en Entorno Servidor  
**Curso:** 2º DAW  
**Fecha:** 27 de enero de 2026  

---

## Funcionalidades implementadas

### Extensiones obligatorias (100%)

#### 1. Sistema de Ingredientes (1:N)

**Modelo:** `Ingrediente`

**Campos:**
- `nombre` (string, max 100)
- `cantidad` (string, max 50)
- `unidad` (string, max 50)
- `receta_id` (foreign key)

**Funcionalidades:**
- ✅ CRUD completo de ingredientes
- ✅ Relación 1:N con Recetas (una receta tiene muchos ingredientes)
- ✅ Rutas anidadas: `/api/recetas/{receta}/ingredientes`
- ✅ Autorización: solo el propietario de la receta o admin pueden modificar ingredientes
- ✅ Los ingredientes se incluyen automáticamente al consultar una receta
- ✅ Validación de campos requeridos
- ✅ Cascade on delete: si se elimina una receta, se eliminan sus ingredientes

**Decisión técnica - ¿Por qué 1:N?**

He elegido una relación **1:N (Uno a Muchos)** porque:

1. **Especificidad:** Los ingredientes tienen cantidades y unidades específicas para cada receta concreta
   - Ejemplo: "Arroz 400g" para Paella es diferente a "Arroz 200g" para Risotto
   
2. **Contexto:** Un ingrediente pertenece a una sola receta y tiene sentido solo en ese contexto
   - No tiene sentido compartir "Arroz 400g" entre múltiples recetas
   
3. **Simplicidad:** Evita la complejidad de una tabla pivot cuando no es necesaria
   - Más fácil de mantener y consultar
   
4. **Casos de uso reales:** En una aplicación de recetas, cada ingrediente es único para su receta
   - "4 huevos" para tortilla ≠ "2 huevos" para flan

**Alternativa descartada:** Relación N:M con tabla pivot `receta_ingrediente` sería innecesariamente compleja y no aportaría valor en este caso de uso.

---

#### 2. Sistema de Likes (Entidad Like - 1:N)

**Modelo:** `Like` (entidad)

**Relación:** Implementado como entidad intermedia con dos relaciones 1:N:
- `User` -> hasMany(Like::class)
- `Receta` -> hasMany(Like::class)
- `Like` -> belongsTo(User::class) y belongsTo(Receta::class)

**Funcionalidades:**
- ✅ Dar like a una receta (POST)
- ✅ Quitar like (mismo endpoint: lógica explícita de crear/eliminar)
- ✅ Un usuario solo puede dar un like por receta (constraint UNIQUE)
- ✅ Contador de likes en cada receta (`likes_count`)
- ✅ Indicador de si el usuario actual dio like (`liked_by_user`)
- ✅ Listar usuarios que dieron like a una receta (a través del modelo `Like`)
- ✅ Obtener el contador de likes
- ✅ Cascade on delete: si se elimina usuario o receta, se eliminan los likes

**Decisión técnica - ¿Por qué modelar `Like` como entidad (1:N) en lugar de usar `belongsToMany()` (N:M pura)?**

He decidido evitar una relación Muchos a Muchos (N:M) pura para reducir la complejidad y aumentar la claridad del modelo de datos. En su lugar, se ha implementado el `Like` como una entidad independiente (tabla `likes` con su propio `id` y timestamps). Esto permite:

1. **Trazabilidad:** Cada interacción es un objeto con identidad propia (`id`, `created_at`, `updated_at`).
2. **Extensibilidad:** Es sencillo añadir campos futuros (tipo de reacción, IP, metadata) sin cambiar la semántica básica.
3. **Integridad:** La restricción `UNIQUE(user_id, receta_id)` en la tabla `likes` garantiza unicidad a nivel de BD y evita duplicados incluso ante condiciones de carrera.
4. **Claridad en la lógica de negocio:** El controlador opera sobre la entidad `Like` (buscar -> crear/eliminar) en vez de depender de métodos pivot implícitos; esto facilita tests y políticas.
5. **Preserva la semántica N:M:** Conceptualmente sigue siendo una relación N:M entre usuarios y recetas, pero físicamente se representa como dos relaciones 1:N unidas por la entidad `Like`.

**Implementación y endpoints:**
- Migración: `create_likes_table.php` con `id`, `user_id`, `receta_id`, timestamps y `UNIQUE(user_id, receta_id)`.
- Controlador: `LikeController@toggleLike` (POST `/api/recetas/{receta}/like`) — busca por `(user_id, receta_id)` y elimina si existe, o crea si no existe.
- Recursos: `RecetaResource` muestra `likes_count` usando `likes()->count()` y `liked_by_user` con `isLikedBy()`.

**Ventaja frente a `belongsToMany()` puro:**
- Mantiene ventajas de una tabla pivot (consulta eficiente, unique) pero trata cada like como una entidad manipulable (mejor para auditoría, extensiones y tests).

---

#### 3. Sistema de Comentarios (1:N)

**Modelo:** `Comentario`

**Campos:**
- `texto` (text, max 1000 caracteres)
- `user_id` (foreign key)
- `receta_id` (foreign key)

**Funcionalidades:**
- ✅ CRUD completo de comentarios
- ✅ Relación 1:N con Recetas (una receta tiene muchos comentarios)
- ✅ Relación 1:N con Users (un usuario puede hacer muchos comentarios)
- ✅ Cualquier usuario autenticado puede comentar
- ✅ Solo el autor o admin pueden editar/eliminar un comentario (403 si no)
- ✅ Los comentarios incluyen el nombre del usuario automáticamente
- ✅ Ordenados por más reciente primero
- ✅ Contador de comentarios en RecetaResource
- ✅ Cascade on delete: si se elimina receta o usuario, se eliminan los comentarios

**Decisión técnica - Autorización:**

**ComentarioPolicy implementada:**
- `create()`: Cualquier usuario autenticado puede comentar (return true)
- `update()`: Solo autor o admin pueden editar
- `delete()`: Solo autor o admin pueden eliminar

Esta decisión permite:
- Fomentar la participación (todos pueden comentar)
- Proteger la integridad (solo autor/admin modifican)
- Moderación eficiente (admins pueden eliminar comentarios inapropiados)

---

### Extensiones opcionales (BONUS)

#### 4. Imagen del Plato Final

**Campo:** `imagen_url` en tabla `recetas` (nullable)

**Funcionalidades:**
- ✅ Subida de imagen al crear receta
- ✅ Subida de imagen al actualizar receta
- ✅ Validación de tipo: solo jpeg, png, jpg
- ✅ Validación de tamaño: máximo 2MB
- ✅ Almacenamiento en `storage/app/public/recetas/`
- ✅ Accessor para URL absoluta accesible desde la API
- ✅ Eliminación automática de imagen antigua al actualizar
- ✅ Eliminación automática de imagen al borrar receta

**Decisión técnica - Almacenamiento:**

**Opción elegida:** Disco `public` con enlace simbólico

**Razones:**
1. **Performance:** El servidor web sirve archivos estáticos directamente
2. **Simplicidad:** No requiere controlador adicional para servir imágenes
3. **Portabilidad:** URL relativa en BD, URL absoluta en API (accessor)

**Comando necesario (una sola vez):**
```bash
php artisan storage:link
```

Esto crea el enlace: `public/storage` → `storage/app/public`

**Accessor implementado:**
```php
public function getImagenUrlCompletaAttribute(): ?string
{
    if (!$this->imagen_url) return null;
    if (str_starts_with($this->imagen_url, 'http')) return $this->imagen_url;
    return asset('storage/' . $this->imagen_url);
}
```

**Ventaja:** La URL se adapta automáticamente al entorno (local/producción).

---

#### 5. Filtros Avanzados con Eloquent Scopes

**Scopes implementados en modelo Receta:**

1. **`conIngrediente(string $ingrediente)`**
   - Filtra recetas que contengan un ingrediente
   - Búsqueda case-insensitive (ILIKE en PostgreSQL)
   - Búsqueda parcial ("aceit" encuentra "Aceite de oliva")

2. **`porPopularidad()`**
   - Ordena por número de likes descendente
   - Usa `withCount('likes')` para eficiencia
   - Más populares primero

3. **`buscar(string $termino)`**
   - Búsqueda en título y descripción
   - Case-insensitive
   - Encapsula lógica de búsqueda general

4. **`ordenarPor(string $campo, string $direccion)`**
   - Whitelist de campos permitidos (seguridad)
   - Añade `withCount('likes')` automáticamente si ordena por likes

**Decisión técnica - ¿Por qué Scopes?**

**Ventajas:**
1. **Reutilización:** Los scopes se pueden usar en cualquier parte del código
2. **Legibilidad:** `Receta::conIngrediente('arroz')->porPopularidad()->get()`
3. **Mantenibilidad:** Lógica centralizada en el modelo
4. **Testing:** Cada scope se puede testear independientemente

**Comparación:**

```php
// Sin scopes (código acoplado en el controlador)
$query->whereHas('ingredientes', function ($q) use ($ingrediente) {
    $q->where('nombre', 'ILIKE', "%{$ingrediente}%");
});

// Con scopes (código limpio y reutilizable)
$query->conIngrediente($ingrediente);
```

---

#### 6. Documentación Swagger/OpenAPI

**Paquete:** `darkaonline/l5-swagger`

**Funcionalidades:**
- ✅ Interfaz Swagger UI accesible en navegador
- ✅ 10 endpoints documentados (Recetas + Ingredientes)
- ✅ Autenticación Bearer Token configurada
- ✅ Ejemplos de request/response
- ✅ Validaciones documentadas
- ✅ Códigos de respuesta HTTP
- ✅ Testing integrado desde el navegador

**Decisión técnica - Anotaciones en código:**

En lugar de un archivo YAML/JSON separado, uso anotaciones PHP (PHPDoc):

**Ventajas:**
1. **Sincronización automática:** Si cambio el código, actualizo la anotación al lado
2. **Menos archivos:** Todo en un solo lugar
3. **Generación automática:** `php artisan l5-swagger:generate`

**Ejemplo:**
```php
/**
 * @OA\Get(
 *     path="/recetas",
 *     tags={"Recetas"},
 *     summary="Listar todas las recetas",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="ingrediente", ...),
 *     @OA\Response(response=200, ...)
 * )
 */
public function index(Request $request)
```

---

## Tests implementados

**Total de tests:** ~85+ tests

**Cobertura:**
- ✅ IngredienteTest: 11 tests (CRUD + autorización)
- ✅ LikeTest: 12 tests (toggle + validaciones)
- ✅ ComentarioTest: 14 tests (CRUD + autorización)
- ✅ RecetaImagenTest: 12 tests (validación de formatos y tamaños)
- ✅ RecetaFiltrosAvanzadosTest: 13 tests (filtros + ordenación)
- ✅ **ExtensionesTest: 23 tests** (verificación final de requisitos críticos)

**Tests críticos verificados:**
- ✅ Usuario no puede borrar comentario de otro (403)
- ✅ Usuario puede dar like y el contador sube
- ✅ No se pueden subir archivos que no sean imágenes
- ✅ No hay regresiones en endpoints existentes
- ✅ Cascade delete funciona correctamente

---

## Comandos HTTPie para probar endpoints

### Configuración inicial

```bash
# 1. Obtener token de autenticación
http POST :8000/api/auth/login email=admin@demo.local password=password

# Guardar el token en variable
export TOKEN=1|abcdefghijklmnopqrstuvwxyz...
```

---

###  Ingredientes

#### Listar ingredientes de una receta
```bash
http GET :8000/api/recetas/1/ingredientes \
  "Authorization:Bearer $TOKEN"
```

#### Agregar ingrediente a una receta
```bash
http POST :8000/api/recetas/1/ingredientes \
  "Authorization:Bearer $TOKEN" \
  nombre="Arroz" \
  cantidad="400" \
  unidad="g"
```

**Respuesta esperada (201 Created):**
```json
{
  "id": 1,
  "nombre": "Arroz",
  "cantidad": "400",
  "unidad": "g",
  "receta_id": 1
}
```

#### Ver un ingrediente específico
```bash
http GET :8000/api/ingredientes/1 \
  "Authorization:Bearer $TOKEN"
```

#### Actualizar un ingrediente
```bash
http PUT :8000/api/ingredientes/1 \
  "Authorization:Bearer $TOKEN" \
  cantidad="500"
```

#### Eliminar un ingrediente
```bash
http DELETE :8000/api/ingredientes/1 \
  "Authorization:Bearer $TOKEN"
```

**Respuesta esperada (200 OK):**
```json
{
  "message": "Ingrediente eliminado correctamente"
}
```

---

### Likes

#### Dar like a una receta (toggle)
```bash
http POST :8000/api/recetas/1/like \
  "Authorization:Bearer $TOKEN"
```

**Primera vez - Respuesta (201 Created):**
```json
{
  "message": "Like añadido correctamente",
  "liked": true,
  "likes_count": 1
}
```

**Segunda vez (quitar like) - Respuesta (200 OK):**
```json
{
  "message": "Like eliminado correctamente",
  "liked": false,
  "likes_count": 0
}
```

#### Obtener contador de likes
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

#### Listar usuarios que dieron like
```bash
http GET :8000/api/recetas/1/likes \
  "Authorization:Bearer $TOKEN"
```

**Respuesta:**
```json
[
  {
    "id": 1,
    "name": "Juan Pérez",
    "email": "juan@example.com"
  },
  {
    "id": 2,
    "name": "María García",
    "email": "maria@example.com"
  }
]
```

---

### Comentarios

#### Listar comentarios de una receta
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
    "texto": "¡Excelente receta!",
    "created_at": "2026-01-27T12:00:00.000000Z"
  }
]
```

#### Crear un comentario
```bash
http POST :8000/api/recetas/1/comentarios \
  "Authorization:Bearer $TOKEN" \
  texto="¡Esta receta me encantó! La recomiendo 100%"
```

**Respuesta (201 Created):**
```json
{
  "id": 1,
  "receta_id": 1,
  "user_id": 1,
  "user_name": "Admin User",
  "texto": "¡Esta receta me encantó! La recomiendo 100%",
  "created_at": "2026-01-27T14:30:00.000000Z"
}
```

#### Ver un comentario específico
```bash
http GET :8000/api/comentarios/1 \
  "Authorization:Bearer $TOKEN"
```

#### Actualizar un comentario (solo autor o admin)
```bash
http PUT :8000/api/comentarios/1 \
  "Authorization:Bearer $TOKEN" \
  texto="Actualicé mi comentario: ¡Excelente receta!"
```

#### Eliminar un comentario (solo autor o admin)
```bash
http DELETE :8000/api/comentarios/1 \
  "Authorization:Bearer $TOKEN"
```

**Respuesta (200 OK):**
```json
{
  "message": "Comentario eliminado correctamente"
}
```

**Si intentas borrar comentario de otro (403 Forbidden):**
```json
{
  "message": "This action is unauthorized."
}
```

---

### Imágenes

**Nota:** Para subir archivos con HTTPie, usa `--form` o `-f`

#### Crear receta con imagen
```bash
http --form POST :8000/api/recetas \
  "Authorization:Bearer $TOKEN" \
  titulo="Paella Valenciana" \
  descripcion="Auténtica paella española" \
  instrucciones="Calentar aceite, sofreír pollo..." \
  imagen@/ruta/a/tu/imagen.jpg
```

**Ejemplo con ruta Windows:**
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
  "imagen_url": "recetas/aBcD1234.jpg",
  "created_at": "2026-01-27T15:00:00.000000Z"
}
```

#### Crear receta SIN imagen (opcional)
```bash
http POST :8000/api/recetas \
  "Authorization:Bearer $TOKEN" \
  titulo="Gazpacho" \
  descripcion="Sopa fría andaluza" \
  instrucciones="Triturar tomates, pepino..."
```

#### Ver receta con imagen (URL completa)
```bash
http GET :8000/api/recetas/1 \
  "Authorization:Bearer $TOKEN"
```

**Respuesta:**
```json
{
  "id": 1,
  "titulo": "Paella Valenciana",
  "imagen_url": "http://localhost/storage/recetas/aBcD1234.jpg",
  "ingredientes": [...],
  "likes_count": 5,
  "comentarios": [...]
}
```

#### Actualizar receta agregando/cambiando imagen
```bash
http --form PUT :8000/api/recetas/1 \
  "Authorization:Bearer $TOKEN" \
  imagen@/ruta/a/nueva_imagen.jpg
```

**Nota:** La imagen anterior se elimina automáticamente.

#### Intentar subir archivo no válido (falla con 422)
```bash
http --form POST :8000/api/recetas \
  "Authorization:Bearer $TOKEN" \
  titulo="Test" \
  descripcion="Test" \
  instrucciones="Test" \
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

### Filtros Avanzados

#### Filtrar recetas por ingrediente
```bash
http GET :8000/api/recetas?ingrediente=arroz \
  "Authorization:Bearer $TOKEN"
```

**Resultado:** Recetas que contienen "arroz" como ingrediente

#### Ordenar por popularidad (más likes primero)
```bash
http GET :8000/api/recetas?sort=popular \
  "Authorization:Bearer $TOKEN"
```

#### Combinar filtro por ingrediente + popularidad
```bash
http GET :8000/api/recetas?ingrediente=pollo&sort=popular \
  "Authorization:Bearer $TOKEN"
```

**Resultado:** Recetas con pollo, ordenadas por más likes

#### Búsqueda de texto + ingrediente + popularidad
```bash
http GET :8000/api/recetas?q=española&ingrediente=huevo&sort=popular \
  "Authorization:Bearer $TOKEN"
```

**Resultado:** Recetas que contengan "española" en título/descripción, tengan huevo, ordenadas por popularidad

#### Paginación personalizada
```bash
http GET :8000/api/recetas?per_page=20&page=2 \
  "Authorization:Bearer $TOKEN"
```

---

### Ejemplo completo: Crear receta con todo

```bash
# 1. Crear receta con imagen
http --form POST :8000/api/recetas \
  "Authorization:Bearer $TOKEN" \
  titulo="Paella Valenciana" \
  descripcion="La auténtica paella" \
  instrucciones="Paso 1, Paso 2, Paso 3" \
  imagen@paella.jpg

# Respuesta: { "id": 1, ... }

# 2. Agregar ingredientes
http POST :8000/api/recetas/1/ingredientes \
  "Authorization:Bearer $TOKEN" \
  nombre="Arroz" cantidad="400" unidad="g"

http POST :8000/api/recetas/1/ingredientes \
  "Authorization:Bearer $TOKEN" \
  nombre="Pollo" cantidad="500" unidad="g"

http POST :8000/api/recetas/1/ingredientes \
  "Authorization:Bearer $TOKEN" \
  nombre="Azafrán" cantidad="1" unidad="pizca"

# 3. Dar like
http POST :8000/api/recetas/1/like \
  "Authorization:Bearer $TOKEN"

# 4. Comentar
http POST :8000/api/recetas/1/comentarios \
  "Authorization:Bearer $TOKEN" \
  texto="¡La mejor paella que he probado!"

# 5. Ver receta completa
http GET :8000/api/recetas/1 \
  "Authorization:Bearer $TOKEN"
```

**Respuesta final:**
```json
{
  "id": 1,
  "titulo": "Paella Valenciana",
  "descripcion": "La auténtica paella",
  "imagen_url": "http://localhost/storage/recetas/xyz.jpg",
  "ingredientes": [
    {"nombre": "Arroz", "cantidad": "400", "unidad": "g"},
    {"nombre": "Pollo", "cantidad": "500", "unidad": "g"},
    {"nombre": "Azafrán", "cantidad": "1", "unidad": "pizca"}
  ],
  "likes_count": 1,
  "liked_by_user": true,
  "comentarios": [
    {
      "user_name": "Admin User",
      "texto": "¡La mejor paella que he probado!"
    }
  ],
  "comentarios_count": 1
}
```

---

## Acceso a la UI de Swagger

### ⚠️ NOTA IMPORTANTE - Swagger se sirve desde `storage/api-docs/api-docs.json`

El archivo JSON de la especificación OpenAPI ya está **pre-generado** en `storage/api-docs/api-docs.json`. 

**No necesitas ejecutar** `php artisan l5-swagger:generate` (puede fallar en algunos entornos).

---

### Paso 1: Acceder a la interfaz Swagger UI

Abre tu navegador y ve directamente a:

```
http://localhost/api/documentation
```

O si tu servidor corre en puerto 8000:

```
http://localhost:8000/api/documentation
```

La ruta `/api/documentation` está configurada para:
1. Buscar el JSON en `storage/api-docs/api-docs.json` ✅ (ya existe)
2. Si está disponible, redirige a Swagger UI

**Verás:** Una interfaz interactiva con todos los endpoints documentados.

---

### Alternativa: Acceder al JSON directamente

Si Swagger UI no está disponible (assets no publicados), puedes acceder al JSON directamente:

```
http://localhost/storage/api-docs/api-docs.json
```

---

### Paso 2: Autenticarse en Swagger

Para probar endpoints protegidos, necesitas autenticarte:

#### Opción A: Obtener token desde HTTPie

```bash
http POST :8000/api/auth/login \
  email=admin@demo.local \
  password=password
```

**Copia el token de la respuesta:**
```json
{
  "token": "1|abcdefghijklmnopqrstuvwxyz..."
}
```

#### Opción B: Obtener token desde Swagger

1. En Swagger UI, busca el endpoint `POST /api/auth/login`
2. Click en **"Try it out"**
3. Ingresa:
   ```json
   {
     "email": "admin@demo.local",
     "password": "password"
   }
   ```
4. Click en **"Execute"**
5. Copia el `token` de la respuesta

---

### Paso 4: Configurar el token en Swagger

1. Click en el botón **"Authorize"** (icono de candado verde en la esquina superior derecha)
2. En el campo "Value", pega el token **completo**:
   ```
   1|abcdefghijklmnopqrstuvwxyz...
   ```
3. Click en **"Authorize"**
4. Click en **"Close"**

**¡Listo!** Ahora todos tus requests incluirán automáticamente el header:
```
Authorization: Bearer 1|abcdefghijklmnopqrstuvwxyz...
```

---

### Paso 5: Probar endpoints

Ahora puedes probar cualquier endpoint directamente desde el navegador:

#### Ejemplo: Listar recetas con filtros

1. Ve a `GET /api/recetas`
2. Click **"Try it out"**
3. Modifica los parámetros:
   - **ingrediente**: `arroz`
   - **sort**: `popular`
4. Click **"Execute"**
5. Ve la respuesta abajo con las recetas filtradas

#### Ejemplo: Crear receta

1. Ve a `POST /api/recetas`
2. Click **"Try it out"**
3. Modifica el JSON:
   ```json
   {
     "titulo": "Mi Receta de Prueba",
     "descripcion": "Una receta deliciosa",
     "instrucciones": "Paso 1, Paso 2, Paso 3"
   }
   ```
4. Click **"Execute"**
5. Verás **201 Created** con el ID de la receta creada

#### Ejemplo: Agregar ingrediente

1. Ve a `POST /api/recetas/{receta}/ingredientes`
2. Click **"Try it out"**
3. En **receta**, ingresa: `1`
4. En el body:
   ```json
   {
     "nombre": "Arroz",
     "cantidad": "400",
     "unidad": "g"
   }
   ```
5. Click **"Execute"**

---

### Ventajas de Swagger UI

✅ **Testing sin Postman:** Probar endpoints directamente desde el navegador  
✅ **Documentación visual:** Ver estructura de request/response  
✅ **Ejemplos pre-llenados:** Valores de ejemplo en cada endpoint  
✅ **Descubrimiento de API:** Explorar todos los endpoints disponibles  
✅ **Validación inmediata:** Ver errores de validación en tiempo real  
✅ **Exportable:** Descargar la especificación OpenAPI para importar en Postman/Insomnia  

---

### URLs importantes

- **Swagger UI:** http://localhost/api/documentation
- **JSON OpenAPI:** http://localhost/storage/api-docs/api-docs.json
- **API Base:** http://localhost/api

---

### Troubleshooting Swagger

#### "Swagger UI no aparece" o "404 not found"

**Solución:**

1. Verifica que el archivo JSON existe:
```bash
ls -la storage/api-docs/api-docs.json
```

2. Si no existe, créalo manualmente (ya está en el repositorio)

3. Accede directamente al JSON:
```
http://localhost/storage/api-docs/api-docs.json
```

4. Si el JSON está vacío o corrupto, puedes regenerarlo:
```bash
php artisan l5-swagger:generate
# O simplemente copiar el api-docs.json del repositorio
```

#### "El botón Authorize no aparece"

**Verificar:** Que en el JSON esté definida la seguridad:
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

#### "Los cambios en controladores no se reflejan en Swagger"

**Solución:** El JSON es estático. Si cambias código de los controladores:
```bash
# Opción 1: Regenerar (si swagger-php funciona)
php artisan l5-swagger:generate

# Opción 2: Actualizar JSON manualmente en storage/api-docs/api-docs.json
```

#### "swagger-php genera error 'Required @OA\PathItem() not found'"

**Solución:** Este es un problema conocido. La forma más práctica es:
1. Usar el JSON pre-generado (ya está disponible)
2. No confiar en `l5-swagger:generate` para entornos educativos
3. El JSON funciona igual de bien para documentación y testing desde el navegador

---

## Comandos de verificación

```bash
# 1. Ejecutar migraciones
php artisan migrate

# 2. Crear enlace simbólico para imágenes (una sola vez)
php artisan storage:link

# 3. Ejecutar todos los tests
php artisan test

# Resultado esperado: ~85+ tests passed ✅

# 4. Swagger ya está pre-generado, accede a:
# http://localhost/api/documentation
# (NO necesitas ejecutar: php artisan l5-swagger:generate)

# 5. Ver rutas de la API
php artisan route:list --path=api

# 6. (Opcional) Si necesitas regenerar Swagger
php artisan l5-swagger:generate
```

---

## Documentación adicional

Para más información, consultar:

- **Índice de documentación:** `docs/00_indice.md`

