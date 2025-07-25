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
            'marca' => $this->whenLoaded('marca', function () {
                return new AutoMarcaResource($this->marca);
            }),
            'modelo' => $this->modelo,
            'year' => $this->year,
            'base' => $this->base,
            'traccion' => $this->traccion,
            'cilindraje' => $this->cilindraje,
            'combustion' => $this->combustion,
            'categoria' => $this->whenLoaded('categoria', function () {
                return new AutoCategoriaResource($this->categoria);
            }),
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
