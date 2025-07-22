<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmpleadoCategoriaResource extends JsonResource
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
            'descripcion' => $this->descripcion,
            'area' => $this->area,
            'rango' => $this->rango,
            // ✅ CORREGIDO: estado es un objeto único, no colección
            'estado' => new EstadoResource($this->whenLoaded('estado')),
            // ✅ CORREGIDO: usuario es un objeto único, no colección
            'usuario' => new UserResource($this->whenLoaded('usuario')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
