<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Receta;
use App\Models\Like;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class LikeController extends Controller
{
    #[OA\Post(
        path: "/recetas/{receta}/like",
        tags: ["Likes"],
        summary: "Dar/quitar like (toggle)",
        description: "Alterna el estado de like: si existe lo elimina, si no existe lo crea. Garantiza unicidad mediante restricción de base de datos.",
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'receta', in: 'path', required: true)],
        responses: [
            new OA\Response(response: 201, description: "Like añadido correctamente"),
            new OA\Response(response: 200, description: "Like eliminado correctamente"),
            new OA\Response(response: 401, description: "No autenticado"),
            new OA\Response(response: 404, description: "Receta no encontrada"),
            new OA\Response(response: 409, description: "El like ya existe (solo en modos no-toggle)")
        ]
    )]
    public function toggleLike(Request $request, Receta $receta)
    {
        $user = $request->user();

        // Buscar registro existente en la tabla likes por user_id y receta_id
        $existingLike = Like::where('user_id', $user->id)
            ->where('receta_id', $receta->id)
            ->first();

        if ($existingLike) {
            // Si existe, eliminarlo
            $existingLike->delete();

            // Recargar el contador de likes
            $receta->loadCount('likes');

            return response()->json([
                'message' => 'Like eliminado correctamente',
                'liked' => false,
                'likes_count' => $receta->likes_count,
            ]);
        }

        // Si no existe, crear instancia del modelo Like
        // La restricción unique en base de datos previene duplicados
        try {
            Like::create([
                'user_id' => $user->id,
                'receta_id' => $receta->id,
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            // Si hay violación de restricción única (unlikely con toggle, pero posible en race condition)
            if ($e->getCode() === '23000') {
                return response()->json([
                    'message' => 'Ya has dado like a esta receta',
                ], 409);
            }
            throw $e;
        }

        // Recargar el contador de likes
        $receta->loadCount('likes');

        return response()->json([
            'message' => 'Like añadido correctamente',
            'liked' => true,
            'likes_count' => $receta->likes_count,
        ], 201);
    }

    #[OA\Get(
        path: "/recetas/{receta}/likes/count",
        tags: ["Likes"],
        summary: "Obtener contador de likes",
        description: "Retorna el número total de likes de una receta",
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'receta', in: 'path', required: true)],
        responses: [new OA\Response(response: 200, description: "Contador de likes obtenido"), new OA\Response(response: 401, description: "No autenticado")]
    )]
    public function count(Receta $receta)
    {
        return response()->json([
            'likes_count' => $receta->likes()->count(),
        ]);
    }

    #[OA\Get(
        path: "/recetas/{receta}/likes",
        tags: ["Likes"],
        summary: "Listar usuarios que dieron like",
        description: "Obtiene la lista de usuarios que dieron like a una receta",
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'receta', in: 'path', required: true)],
        responses: [new OA\Response(response: 200, description: "Lista de usuarios que dieron like"), new OA\Response(response: 401, description: "No autenticado")]
    )]
    public function index(Receta $receta)
    {
        $likes = $receta->likes()->with('user')->get();

        return response()->json([
            'likes' => $likes->map(function ($like) {
                return [
                    'id' => $like->id,
                    'user_id' => $like->user_id,
                    'user_name' => $like->user->name,
                    'created_at' => $like->created_at,
                ];
            }),
            'likes_count' => $likes->count(),
        ]);
    }
}
