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
     * Listar comentarios de una receta
     *
     * GET /api/recetas/{receta}/comentarios
     */
    public function index(Receta $receta)
    {
        // Cargar comentarios con información del usuario
        $comentarios = $receta->comentarios()->with('user')->latest()->get();

        return ComentarioResource::collection($comentarios);
    }

    /**
     * Crear un comentario en una receta
     *
     * POST /api/recetas/{receta}/comentarios
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
     * Mostrar un comentario específico
     *
     * GET /api/comentarios/{comentario}
     */
    public function show(Comentario $comentario)
    {
        $comentario->load('user');
        return new ComentarioResource($comentario);
    }

    /**
     * Actualizar un comentario
     *
     * PUT/PATCH /api/comentarios/{comentario}
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
     * Eliminar un comentario
     *
     * DELETE /api/comentarios/{comentario}
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
