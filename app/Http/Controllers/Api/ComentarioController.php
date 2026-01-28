<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ComentarioResource;
use App\Models\Comentario;
use App\Models\Receta;
use Illuminate\Http\Request;

class ComentarioController extends Controller
{
    /**
     * @OA\Get(
     *     path="/recetas/{receta}/comentarios",
     *     tags={"Comentarios"},
     *     summary="Listar comentarios de una receta",
     *     description="Obtiene todos los comentarios de una receta ordenados por más reciente primero",
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
     *         description="Lista de comentarios obtenida correctamente",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="receta_id", type="integer", example=1),
     *                 @OA\Property(property="user_id", type="integer", example=2),
     *                 @OA\Property(property="user_name", type="string", example="Juan Pérez"),
     *                 @OA\Property(property="texto", type="string", example="¡Excelente receta!"),
     *                 @OA\Property(property="created_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=404, description="Receta no encontrada")
     * )
     */
    public function index(Receta $receta)
    {
        // Cargar comentarios con información del usuario
        $comentarios = $receta->comentarios()->with('user')->latest()->get();

        return ComentarioResource::collection($comentarios);
    }

    /**
     * @OA\Post(
     *     path="/recetas/{receta}/comentarios",
     *     tags={"Comentarios"},
     *     summary="Crear un comentario",
     *     description="Crea un nuevo comentario en una receta. Cualquier usuario autenticado puede comentar",
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
     *             required={"texto"},
     *             @OA\Property(property="texto", type="string", example="¡Esta receta me encantó!", maxLength=1000)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Comentario creado correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="receta_id", type="integer", example=1),
     *             @OA\Property(property="user_id", type="integer", example=1),
     *             @OA\Property(property="user_name", type="string", example="Admin User"),
     *             @OA\Property(property="texto", type="string", example="¡Esta receta me encantó!"),
     *             @OA\Property(property="created_at", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=404, description="Receta no encontrada"),
     *     @OA\Response(response=422, description="Error de validación")
     * )
     */
    public function store(Request $request, Receta $receta)
    {
        // Cualquier usuario autenticado puede comentar
        $data = $request->validate([
            'texto' => 'required|string|max:1000',
        ]);

        $comentario = $receta->comentarios()->create([
            'user_id' => $request->user()->id,
            'texto' => $data['texto'],
        ]);

        // Cargar la relación user para incluirla en la respuesta
        $comentario->load('user');

        return (new ComentarioResource($comentario))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * @OA\Get(
     *     path="/comentarios/{comentario}",
     *     tags={"Comentarios"},
     *     summary="Ver un comentario específico",
     *     description="Obtiene los detalles de un comentario",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="comentario",
     *         in="path",
     *         description="ID del comentario",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Comentario obtenido correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="receta_id", type="integer"),
     *             @OA\Property(property="user_id", type="integer"),
     *             @OA\Property(property="user_name", type="string"),
     *             @OA\Property(property="texto", type="string"),
     *             @OA\Property(property="created_at", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=404, description="Comentario no encontrado")
     * )
     */
    public function show(Comentario $comentario)
    {
        $comentario->load('user');
        return new ComentarioResource($comentario);
    }

    /**
     * @OA\Put(
     *     path="/comentarios/{comentario}",
     *     tags={"Comentarios"},
     *     summary="Actualizar un comentario",
     *     description="Actualiza un comentario. Solo el autor o admin pueden hacerlo",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="comentario",
     *         in="path",
     *         description="ID del comentario",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"texto"},
     *             @OA\Property(property="texto", type="string", example="Comentario actualizado")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Comentario actualizado correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="texto", type="string")
     *         )
     *     ),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=403, description="No autorizado"),
     *     @OA\Response(response=404, description="Comentario no encontrado"),
     *     @OA\Response(response=422, description="Error de validación")
     * )
     */
    public function update(Request $request, Comentario $comentario)
    {
        // Autorización: solo el autor o admin
        $this->authorize('update', $comentario);

        $data = $request->validate([
            'texto' => 'required|string|max:1000',
        ]);

        $comentario->update($data);
        $comentario->load('user');

        return new ComentarioResource($comentario);
    }

    /**
     * @OA\Delete(
     *     path="/comentarios/{comentario}",
     *     tags={"Comentarios"},
     *     summary="Eliminar un comentario",
     *     description="Elimina un comentario. Solo el autor o admin pueden hacerlo",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="comentario",
     *         in="path",
     *         description="ID del comentario",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Comentario eliminado correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Comentario eliminado correctamente")
     *         )
     *     ),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=403, description="No autorizado"),
     *     @OA\Response(response=404, description="Comentario no encontrado")
     * )
     */
    public function destroy(Comentario $comentario)
    {
        // Autorización: solo el autor o admin
        $this->authorize('delete', $comentario);

        $comentario->delete();

        return response()->json([
            'message' => 'Comentario eliminado correctamente'
        ]);
    }
}
