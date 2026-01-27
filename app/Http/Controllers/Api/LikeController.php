<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Receta;
use Illuminate\Http\Request;

class LikeController extends Controller
{
    /**
     * Toggle like: si existe lo elimina, si no existe lo crea
     *
     * POST /api/recetas/{receta}/like
     */
    public function toggle(Request $request, Receta $receta)
    {
        $user = $request->user();

        // Verificar si ya existe el like
        $existingLike = $receta->likes()
            ->where('user_id', $user->id)
            ->first();

        if ($existingLike) {
            // Si existe, lo eliminamos (unlike)
            $existingLike->delete();

            // Recargar el contador de likes
            $receta->loadCount('likes');

            return response()->json([
                'message' => 'Like eliminado correctamente',
                'liked' => false,
                'likes_count' => $receta->likes_count,
            ]);
        }

        // Si no existe, lo creamos (like)
        $receta->likes()->create([
            'user_id' => $user->id,
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
