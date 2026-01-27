<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Receta;
use App\Models\Like;
use Illuminate\Http\Request;

class LikeController extends Controller
{
    /**
     * Alternativa: toggleLike - si existe el registro lo elimina, si no existe lo crea
     *
     * POST /api/recetas/{receta}/like
     */
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
        Like::create([
            'user_id' => $user->id,
            'receta_id' => $receta->id,
        ]);

        // Recargar el contador de likes
        $receta->loadCount('likes');

        return response()->json([
            'message' => 'Like añadido correctamente',
            'liked' => true,
            'likes_count' => $receta->likes_count,
        ], 201);
    }

    /**
     * Obtener el número de likes de una receta
     *
     * GET /api/recetas/{receta}/likes/count
     */
    public function count(Receta $receta)
    {
        return response()->json([
            'likes_count' => $receta->likes()->count(),
        ]);
    }

    /**
     * Listar usuarios que han dado like a una receta
     *
     * GET /api/recetas/{receta}/likes
     */
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
