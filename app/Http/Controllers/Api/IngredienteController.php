<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\IngredienteResource;
use App\Models\Ingrediente;
use App\Models\Receta;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class IngredienteController extends Controller
{
    #[OA\Get(
        path: "/recetas/{receta}/ingredientes",
        summary: "Listar ingredientes de una receta",
        tags: ["Ingredientes"],
        security: [['bearerAuth' => []]],
        responses: [new OA\Response(response: 200, description: "OK")]
    )]
    /**
     * @OA\Get(
     *     path="/recetas/{receta}/ingredientes",
     *     tags={"Ingredientes"},
     *     summary="Listar ingredientes de una receta",
     *     description="Obtiene todos los ingredientes de una receta específica",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="receta",
     *         in="path",
     *         description="ID de la receta",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de ingredientes obtenida correctamente",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="nombre", type="string", example="Arroz"),
     *                 @OA\Property(property="cantidad", type="string", example="400"),
     *                 @OA\Property(property="unidad", type="string", example="g"),
     *                 @OA\Property(property="receta_id", type="integer", example=1)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=404, description="Receta no encontrada")
     * )
     */
    public function index(Receta $receta)
    {
        $ingredientes = $receta->ingredientes;
        return IngredienteResource::collection($ingredientes);
    }

    #[OA\Post(
        path: "/recetas/{receta}/ingredientes",
        summary: "Agregar un ingrediente a una receta",
        tags: ["Ingredientes"],
        security: [['bearerAuth' => []]],
        responses: [new OA\Response(response: 201, description: "Ingrediente creado")]
    )]
    /**
     * @OA\Post(
     *     path="/recetas/{receta}/ingredientes",
     *     tags={"Ingredientes"},
     *     summary="Agregar un ingrediente a una receta",
     *     description="Crea un nuevo ingrediente para una receta. Solo el propietario o admin pueden hacerlo",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="receta",
     *         in="path",
     *         description="ID de la receta",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"nombre", "cantidad", "unidad"},
     *             @OA\Property(property="nombre", type="string", example="Huevo", maxLength=255),
     *             @OA\Property(property="cantidad", type="string", example="4", maxLength=50),
     *             @OA\Property(property="unidad", type="string", example="ud", maxLength=50)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Ingrediente creado correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="nombre", type="string", example="Huevo"),
     *             @OA\Property(property="cantidad", type="string", example="4"),
     *             @OA\Property(property="unidad", type="string", example="ud"),
     *             @OA\Property(property="receta_id", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=403, description="No autorizado - Solo el propietario o admin pueden agregar ingredientes"),
     *     @OA\Response(response=404, description="Receta no encontrada"),
     *     @OA\Response(response=422, description="Error de validación")
     * )
     */
    public function store(Request $request, Receta $receta)
    {
        // Verificar que el usuario puede modificar la receta
        $this->authorize('update', $receta);

        $data = $request->validate([
            'nombre' => 'required|string|max:100',
            'cantidad' => 'required|numeric|min:0.01',
            'unidad' => 'required|string|in:g,kg,ml,l,ud,cucharada,cucharadita,taza,pizca',
        ]);

        $ingrediente = $receta->ingredientes()->create($data);

        return (new IngredienteResource($ingrediente))
            ->response()
            ->setStatusCode(201);
    }

    #[OA\Get(
        path: "/ingredientes/{ingrediente}",
        summary: "Ver un ingrediente específico",
        tags: ["Ingredientes"],
        security: [['bearerAuth' => []]],
        responses: [new OA\Response(response: 200, description: "OK")]
    )]
    /**
     * @OA\Get(
     *     path="/ingredientes/{ingrediente}",
     *     tags={"Ingredientes"},
     *     summary="Ver un ingrediente específico",
     *     description="Obtiene los detalles de un ingrediente",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="ingrediente",
     *         in="path",
     *         description="ID del ingrediente",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Ingrediente obtenido correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="nombre", type="string"),
     *             @OA\Property(property="cantidad", type="string"),
     *             @OA\Property(property="unidad", type="string"),
     *             @OA\Property(property="receta_id", type="integer")
     *         )
     *     ),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=404, description="Ingrediente no encontrado")
     * )
     */
    public function show(Ingrediente $ingrediente)
    {
        return new IngredienteResource($ingrediente);
    }

    #[OA\Put(
        path: "/ingredientes/{ingrediente}",
        summary: "Actualizar un ingrediente",
        tags: ["Ingredientes"],
        security: [['bearerAuth' => []]],
        responses: [new OA\Response(response: 200, description: "Ingrediente actualizado")]
    )]
    /**
     * @OA\Put(
     *     path="/ingredientes/{ingrediente}",
     *     tags={"Ingredientes"},
     *     summary="Actualizar un ingrediente",
     *     description="Modifica los datos de un ingrediente. Solo el propietario de la receta o admin pueden hacerlo",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="ingrediente",
     *         in="path",
     *         description="ID del ingrediente",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"nombre", "cantidad", "unidad"},
     *             @OA\Property(property="nombre", type="string", example="Huevo"),
     *             @OA\Property(property="cantidad", type="string", example="5"),
     *             @OA\Property(property="unidad", type="string", example="ud")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Ingrediente actualizado correctamente"),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=403, description="No autorizado"),
     *     @OA\Response(response=404, description="Ingrediente no encontrado"),
     *     @OA\Response(response=422, description="Error de validación")
     * )
     */
    public function update(Request $request, Ingrediente $ingrediente)
    {
        // Autorización: solo el dueño de la receta o admin
        $this->authorize('update', $ingrediente);

        $data = $request->validate([
            'nombre' => 'sometimes|required|string|max:100',
            'cantidad' => 'sometimes|required|numeric|min:0.01',
            'unidad' => 'sometimes|required|string|in:g,kg,ml,l,ud,cucharada,cucharadita,taza,pizca',
        ]);

        $ingrediente->update($data);

        return new IngredienteResource($ingrediente);
    }

    #[OA\Delete(
        path: "/ingredientes/{ingrediente}",
        summary: "Eliminar un ingrediente",
        tags: ["Ingredientes"],
        security: [['bearerAuth' => []]],
        responses: [new OA\Response(response: 200, description: "Ingrediente eliminado")]
    )]
    /**
     * @OA\Delete(
     *     path="/ingredientes/{ingrediente}",
     *     tags={"Ingredientes"},
     *     summary="Eliminar un ingrediente",
     *     description="Elimina un ingrediente de una receta. Solo el propietario o admin pueden hacerlo",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="ingrediente",
     *         in="path",
     *         description="ID del ingrediente",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Ingrediente eliminado correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Ingrediente eliminado correctamente")
     *         )
     *     ),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=403, description="No autorizado"),
     *     @OA\Response(response=404, description="Ingrediente no encontrado")
     * )
     */
    public function destroy(Ingrediente $ingrediente)
    {
        // Autorización: solo el dueño de la receta o admin
        $this->authorize('delete', $ingrediente);

        $ingrediente->delete();

        return response()->json(['message' => 'Ingrediente eliminado correctamente']);
    }
}
