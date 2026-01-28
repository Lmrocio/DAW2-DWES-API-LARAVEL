<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Receta;
use Illuminate\Http\Request;
use App\Services\RecetaService;
use App\Http\Resources\RecetaResource;
use OpenApi\Attributes as OA;

class RecetaController extends Controller
{
    #[OA\Get(
        path: "/recetas",
        summary: "Listado de recetas",
        tags: ["Recetas"],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: "OK")
        ]
    )]
    /**
     * @OA\Get(
     *     path="/recetas",
     *     tags={"Recetas"},
     *     summary="Listar todas las recetas",
     *     description="Obtiene un listado paginado de recetas con filtros opcionales",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="q",
     *         in="query",
     *         description="Búsqueda en título y descripción",
     *         required=false,
     *         @OA\Schema(type="string", example="paella")
     *     ),
     *     @OA\Parameter(
     *         name="ingrediente",
     *         in="query",
     *         description="Filtrar por ingrediente",
     *         required=false,
     *         @OA\Schema(type="string", example="arroz")
     *     ),
     *     @OA\Parameter(
     *         name="min_likes",
     *         in="query",
     *         description="Filtrar por número mínimo de likes",
     *         required=false,
     *         @OA\Schema(type="integer", example=5)
     *     ),
     *     @OA\Parameter(
     *         name="sort",
     *         in="query",
     *         description="Ordenar por campo (popular, titulo, created_at). Prefijo '-' para descendente",
     *         required=false,
     *         @OA\Schema(type="string", enum={"popular", "-popular", "titulo", "-titulo", "created_at", "-created_at"})
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Número de página",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Resultados por página (máximo 50)",
     *         required=false,
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de recetas obtenida correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="titulo", type="string", example="Paella Valenciana"),
     *                 @OA\Property(property="descripcion", type="string", example="Tradicional paella española"),
     *                 @OA\Property(property="user_id", type="integer", example=1),
     *                 @OA\Property(property="imagen_url", type="string", example="http://localhost/storage/recetas/abc.jpg"),
     *                 @OA\Property(property="likes_count", type="integer", example=25),
     *                 @OA\Property(property="liked_by_user", type="boolean", example=true)
     *             ))
     *         )
     *     ),
     *     @OA\Response(response=401, description="No autenticado")
     * )
     */
     public function index(Request $request, RecetaService $recetaService)
     {
         $query = Receta::query();

         // Aplicar filtros usando el Service (mantiene controlador limpio)
         $recetaService->applyFilters($query, [
             'q' => $request->query('q'),
             'ingrediente' => $request->query('ingrediente'),
             'min_likes' => $request->query('min_likes'),
         ]);

         // Aplicar ordenación usando el Service
         $recetaService->applySort($query, $request->query('sort'));

         // Siempre cargar el contador de likes
         $query->withCount('likes');

         // Paginación
         $perPage = min((int) $request->query('per_page', 10), 50);
         $recetas = $query->paginate($perPage);

         return RecetaResource::collection($recetas);
     }

    #[OA\Post(
        path: "/recetas",
        summary: "Crear una nueva receta",
        tags: ["Recetas"],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true),
        responses: [
            new OA\Response(response: 201, description: "Receta creada correctamente")
        ]
    )]
    /**
     * @OA\Post(
     *     path="/recetas",
     *     tags={"Recetas"},
     *     summary="Crear una nueva receta",
     *     description="Crea una nueva receta. Puede incluir imagen del plato",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"titulo", "descripcion", "instrucciones"},
     *                 @OA\Property(property="titulo", type="string", example="Tortilla de patatas", maxLength=200),
     *                 @OA\Property(property="descripcion", type="string", example="Clásica tortilla española"),
     *                 @OA\Property(property="instrucciones", type="string", example="1. Pelar patatas 2. Freír 3. Batir huevos..."),
     *                 @OA\Property(property="imagen", type="string", format="binary", description="Imagen del plato (jpeg, png, jpg, max 2MB)")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Receta creada correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="titulo", type="string", example="Tortilla de patatas"),
     *             @OA\Property(property="user_id", type="integer", example=1),
     *             @OA\Property(property="imagen_url", type="string", example="recetas/abc123.jpg")
     *         )
     *     ),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=422, description="Error de validación")
     * )
     */
    public function store(Request $request, RecetaService $recetaService): \Illuminate\Http\JsonResponse
    {
        $data = $request->validate([
            'titulo' => 'required|string|max:200',
            'descripcion' => 'required|string',
            'instrucciones' => 'required|string',
            'imagen' => 'nullable|image|mimes:jpeg,png,jpg|max:2048', // max 2MB
        ]);

        // Procesar la imagen si fue enviada usando el Service
        $imagenUrl = null;
        if ($request->hasFile('imagen')) {
            $imagenUrl = $recetaService->handleImageUpload($request->file('imagen'));
        }

        $receta = Receta::create([
            'user_id' => $request->user()->id,
            'titulo' => $data['titulo'],
            'descripcion' => $data['descripcion'],
            'instrucciones' => $data['instrucciones'],
            'imagen_url' => $imagenUrl,
        ]);

        return response()->json($receta, 201);
    }

    #[OA\Get(
        path: "/recetas/{id}",
        summary: "Ver una receta específica",
        tags: ["Recetas"],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true)],
        responses: [
            new OA\Response(response: 200, description: "Receta obtenida correctamente")
        ]
    )]
    /**
     * @OA\Get(
     *     path="/recetas/{id}",
     *     tags={"Recetas"},
     *     summary="Ver una receta específica",
     *     description="Obtiene los detalles completos de una receta incluyendo ingredientes, likes y comentarios",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID de la receta",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Receta obtenida correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="titulo", type="string", example="Paella Valenciana"),
     *             @OA\Property(property="descripcion", type="string"),
     *             @OA\Property(property="instrucciones", type="string"),
     *             @OA\Property(property="imagen_url", type="string"),
     *             @OA\Property(property="likes_count", type="integer", example=25),
     *             @OA\Property(property="liked_by_user", type="boolean"),
     *             @OA\Property(property="ingredientes", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="nombre", type="string"),
     *                 @OA\Property(property="cantidad", type="string"),
     *                 @OA\Property(property="unidad", type="string")
     *             )),
     *             @OA\Property(property="comentarios", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="texto", type="string"),
     *                 @OA\Property(property="user_name", type="string")
     *             )),
     *             @OA\Property(property="comentarios_count", type="integer")
     *         )
     *     ),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=404, description="Receta no encontrada")
     * )
     */
    public function show(Receta $receta) //: \Illuminate\Http\JsonResponse
    {
        // Cargar la relación de ingredientes, comentarios y el contador de likes
        $receta->load(['ingredientes', 'comentarios.user'])
            ->loadCount(['likes', 'comentarios']);

        return new RecetaResource($receta);
    }

    #[OA\Put(
        path: "/recetas/{id}",
        summary: "Actualizar una receta",
        tags: ["Recetas"],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true)],
        requestBody: new OA\RequestBody(),
        responses: [
            new OA\Response(response: 200, description: "Receta actualizada"),
            new OA\Response(response: 401, description: "No autenticado"),
            new OA\Response(response: 403, description: "No autorizado"),
            new OA\Response(response: 404, description: "Receta no encontrada")
        ]
    )]
    /**
     * @OA\Put(
     *     path="/recetas/{id}",
     *     tags={"Recetas"},
     *     summary="Actualizar una receta",
     *     description="Actualiza los datos de una receta. Solo el propietario o admin pueden hacerlo",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID de la receta",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(property="titulo", type="string"),
     *                 @OA\Property(property="descripcion", type="string"),
     *                 @OA\Property(property="instrucciones", type="string"),
     *                 @OA\Property(property="imagen", type="string", format="binary")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Receta actualizada"),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=403, description="No autorizado"),
     *     @OA\Response(response=404, description="Receta no encontrada"),
     *     @OA\Response(response=422, description="Error de validación")
     * )
     */
    public function update(Request $request, Receta $receta, RecetaService $recetaService)
    {
        // Forma clásica (Laravel <=10, muy común en empresa)
        $this->authorize('update', $receta);

        /*
         * Alternativa recomendada en Laravel 11/12:
         *
         * use Illuminate\Support\Facades\Gate;
         * Gate::authorize('update', $receta);
         */
        // Política de negocio (si se puede)
        $recetaService->assertCanBeModified($receta);

        $data = $request->validate([
            'titulo' => 'sometimes|required|string|max:200',
            'descripcion' => 'sometimes|required|string',
            'instrucciones' => 'sometimes|required|string',
            'imagen' => 'nullable|image|mimes:jpeg,png,jpg|max:2048', // max 2MB
        ]);

        // Procesar la imagen si fue enviada usando el Service
        if ($request->hasFile('imagen')) {
            // Eliminar la imagen anterior
            $recetaService->deleteImage($receta->imagen_url);

            // Guardar la nueva imagen
            $data['imagen_url'] = $recetaService->handleImageUpload($request->file('imagen'));
        }

        $receta->update($data);

        return response()->json($receta);
    }

    #[OA\Delete(
        path: "/recetas/{id}",
        summary: "Eliminar una receta",
        tags: ["Recetas"],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true)],
        responses: [new OA\Response(response: 200, description: "Receta eliminada"), new OA\Response(response: 401, description: "No autenticado"), new OA\Response(response: 403, description: "No autorizado")]
    )]
    /**
     * @OA\Delete(
     *     path="/recetas/{id}",
     *     tags={"Recetas"},
     *     summary="Eliminar una receta",
     *     description="Elimina una receta. Solo el propietario o admin pueden hacerlo",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID de la receta",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Receta eliminada correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Receta eliminada")
     *         )
     *     ),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=403, description="No autorizado"),
     *     @OA\Response(response=404, description="Receta no encontrada")
     * )
     */
    public function destroy(Receta $receta, RecetaService $recetaService)
    {
        // 1. Autorización (403 si falla)
        $this->authorize('delete', $receta);

        /*
         * Alternativa Laravel 11/12:
         * Gate::authorize('delete', $receta);
         */

        // 2. Eliminar la imagen si existe usando el Service
        $recetaService->deleteImage($receta->imagen_url);

        // 3. Eliminar la receta
        $receta->delete();

        return response()->json(['message' => 'Receta eliminada']);
    }
}
