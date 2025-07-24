<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AutoResource extends JsonResource
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
            'marca' => new AutoMarcaResource($this->whenLoaded('marca')),
            'modelo' => $this->modelo,
            'year' => $this->year,
            'base' => $this->base,
            'traccion' => $this->traccion,
            'cilindraje' => $this->cilindraje,
            'combustion' => $this->combustion,
            'categoria' => new AutoCategoriaResource($this->whenLoaded('categoria')),
            'estado' => new EstadoResource($this->whenLoaded('estado')),
            'usuario' => new UserResource($this->whenLoaded('usuario')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
