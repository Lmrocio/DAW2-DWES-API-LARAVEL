<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ComentarioResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'receta_id' => $this->receta_id,
            'user_id' => $this->user_id,
            'user_name' => $this->user->name,
            'texto' => $this->texto,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
