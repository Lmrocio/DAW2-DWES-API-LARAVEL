<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\IngredienteResource;
use App\Models\Ingrediente;
use App\Models\Receta;
use Illuminate\Http\Request;

class IngredienteController extends Controller
{
    /**
     * Listar ingredientes de una receta
     */
    public function index(Receta $receta)
    {
        $ingredientes = $receta->ingredientes;
        return IngredienteResource::collection($ingredientes);
    }

    /**
     * Añadir un ingrediente a una receta
     */
    public function store(Request $request, Receta $receta)
    {
        // Verificar que el usuario puede modificar la receta
        $this->authorize('update', $receta);

        $data = $request->validate([
            'nombre' => 'required|string|max:100',
            'cantidad' => 'required|string|max:50',
            'unidad' => 'required|string|max:50',
        ]);

        $ingrediente = $receta->ingredientes()->create($data);

        return (new IngredienteResource($ingrediente))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Mostrar un ingrediente específico
     */
    public function show(Ingrediente $ingrediente)
    {
        return new IngredienteResource($ingrediente);
    }

    /**
     * Actualizar un ingrediente
     */
    public function update(Request $request, Ingrediente $ingrediente)
    {
        // Autorización: solo el dueño de la receta o admin
        $this->authorize('update', $ingrediente);

        $data = $request->validate([
            'nombre' => 'sometimes|required|string|max:100',
            'cantidad' => 'sometimes|required|string|max:50',
            'unidad' => 'sometimes|required|string|max:50',
        ]);

        $ingrediente->update($data);

        return new IngredienteResource($ingrediente);
    }

    /**
     * Eliminar un ingrediente
     */
    public function destroy(Ingrediente $ingrediente)
    {
        // Autorización: solo el dueño de la receta o admin
        $this->authorize('delete', $ingrediente);

        $ingrediente->delete();

        return response()->json(['message' => 'Ingrediente eliminado correctamente']);
    }
}
