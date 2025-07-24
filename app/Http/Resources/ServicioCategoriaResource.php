<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServicioCategoriaResource extends JsonResource
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
            'estado' => $this->whenLoaded('estado', function () {
                return new EstadoResource($this->estado);
            }),
            'usuario' => $this->whenLoaded('usuario', function () {
                return new UserResource($this->usuario);
             }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
