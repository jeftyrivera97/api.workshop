<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IngresoResource extends JsonResource
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
            'fecha' => $this->fecha,
            'descripcion' => $this->descripcion,
            'categoria' => $this->whenLoaded('categoria', function () {
                return new IngresoCategoriaResource($this->categoria);
            }),
            'total' => $this->total,
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
