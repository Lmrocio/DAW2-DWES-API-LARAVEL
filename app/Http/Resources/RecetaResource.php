<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RecetaResource extends JsonResource
{
    // Guía docente: ver docs/04_modelos_policies_servicios.md.
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'titulo' => $this->titulo,
            'descripcion' => $this->descripcion,
            'instrucciones' => $this->instrucciones,
            'publicada' => $this->publicada,
            'user_id' => $this->user_id,
            'created_at' => $this->created_at,
            'ingredientes' => IngredienteResource::collection($this->whenLoaded('ingredientes')),
        ];
    }
}
