<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TipoGastoResource extends JsonResource
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
            // ✅ CORREGIDO: Usar relación singular sin collection
            'estado' => new EstadoResource($this->estado),
            'usuario' => new UserResource($this->usuario),
            // ✅ CORREGIDO: Usar campos correctos
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
