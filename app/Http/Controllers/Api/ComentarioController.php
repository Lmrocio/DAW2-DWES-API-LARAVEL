<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ComentarioResource;
use App\Models\Comentario;
use App\Models\Receta;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class ComentarioController extends Controller
{
    #[OA\Get(
        path: "/recetas/{receta}/comentarios",
        summary: "Listar comentarios de una receta",
        tags: ["Comentarios"],
        security: [['bearerAuth' => []]],
        responses: [new OA\Response(response: 200, description: "OK")]
    )]
    public function index(Receta $receta)
    {
        // Cargar comentarios con información del usuario
        $comentarios = $receta->comentarios()->with('user')->latest()->get();

        return ComentarioResource::collection($comentarios);
    }

    #[OA\Post(
        path: "/recetas/{receta}/comentarios",
        summary: "Crear un comentario",
        tags: ["Comentarios"],
        security: [['bearerAuth' => []]],
        responses: [new OA\Response(response: 201, description: "Comentario creado")]
    )]
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

    #[OA\Get(
        path: "/comentarios/{comentario}",
        summary: "Ver un comentario específico",
        tags: ["Comentarios"],
        security: [['bearerAuth' => []]],
        responses: [new OA\Response(response: 200, description: "OK")]
    )]
    public function show(Comentario $comentario)
    {
        $comentario->load('user');
        return new ComentarioResource($comentario);
    }

    #[OA\Put(
        path: "/comentarios/{comentario}",
        summary: "Actualizar un comentario",
        tags: ["Comentarios"],
        security: [['bearerAuth' => []]],
        responses: [new OA\Response(response: 200, description: "Comentario actualizado")]
    )]
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

    #[OA\Delete(
        path: "/comentarios/{comentario}",
        summary: "Eliminar un comentario",
        tags: ["Comentarios"],
        security: [['bearerAuth' => []]],
        responses: [new OA\Response(response: 200, description: "Comentario eliminado")]
    )]
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
