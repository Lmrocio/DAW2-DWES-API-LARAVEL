<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Receta;
use Illuminate\Http\Request;
use App\Services\RecetaService;
use App\Http\Resources\RecetaResource;

class RecetaController extends Controller
{
    // Guía docente: ver docs/03_controladores.md.
    // Listar todas las recetas
    public function index(Request $request)
    {
        $query = Receta::query();

        // Búsqueda
        if ($search = $request->query('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('titulo', 'ILIKE', "%{$search}%")
                    ->orWhere('descripcion', 'ILIKE', "%{$search}%");
            });
        } //PostgreSQL ✔ (ILIKE)

        // Ordenación
        $allowedSorts = ['titulo', 'created_at'];
        if ($sort = $request->query('sort')) {
            $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
            $field = ltrim($sort, '-');

            if (in_array($field, $allowedSorts)) {
                $query->orderBy($field, $direction);
            }
        }

        // Paginación
        $perPage = min((int) $request->query('per_page', 10), 50);
        $recetas = $query->withCount('likes')->paginate($perPage);

        return RecetaResource::collection($recetas);
    }

    // Crear una nueva receta
    public function store(Request $request): \Illuminate\Http\JsonResponse
    {
        $data = $request->validate([
            'titulo' => 'required|string|max:200',
            'descripcion' => 'required|string',
            'instrucciones' => 'required|string',
        ]);

        $receta = Receta::create([
            'user_id' => $request->user()->id,
            'titulo' => $data['titulo'],
            'descripcion' => $data['descripcion'],
            'instrucciones' => $data['instrucciones'],
        ]);

        return response()->json($receta, 201);
    }

    // Mostrar una receta específica
    public function show(Receta $receta) //: \Illuminate\Http\JsonResponse
    {
        // Cargar la relación de ingredientes y el contador de likes
        $receta->load('ingredientes')->loadCount('likes');

        return new RecetaResource($receta);
    }

    // Actualizar una receta existente
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
        ]);

        $receta->update($data);

        return response()->json($receta);
    }


    // Eliminar una receta
    public function destroy(Receta $receta)
    {
        // 1. Autorización (403 si falla)
        $this->authorize('delete', $receta);

        /*
         * Alternativa Laravel 11/12:
         * Gate::authorize('delete', $receta);
         */



        // 2. Acción
        $receta->delete();

        return response()->json(['message' => 'Receta eliminada']);
    }
}
