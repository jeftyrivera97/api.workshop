<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GastoCategoriaResource extends JsonResource
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
            'tipo' => new TipoGastoResource($this->tipo),
            'estado' => new EstadoResource($this->estado),
            'usuario' => new UserResource($this->usuario),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
