<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmpleadoResource extends JsonResource
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
            'codigo_empleado' => $this->codigo_empleado,
            'descripcion' => $this->descripcion,
            // ✅ CORREGIDO: categoria es un objeto único, no colección
            'categoria' => new EmpleadoCategoriaResource($this->whenLoaded('categoria')),
            'telefono' => $this->telefono,
            // ✅ CORREGIDO: estado es un objeto único, no colección
            'estado' => new EstadoResource($this->whenLoaded('estado')),
            // ✅ CORREGIDO: usuario es un objeto único, no colección
            'usuario' => new UserResource($this->whenLoaded('usuario')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
