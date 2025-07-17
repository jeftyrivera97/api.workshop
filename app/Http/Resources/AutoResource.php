<?php

namespace App\Http\Resources;

use App\Models\AutoMarca;
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
            'id_marca' => AutoMarcaResource::collection($this->marcas),
            'modelo' => $this->modelo,
            'year' => $this->modelo,
            'base' => $this->base,
            'traccion' => $this->traccion,
            'cilindraje' => $this->cilindraje,
            'combustion' => $this->combustion,
            'id_categoria' => AutoCategoriaResource::collection($this->categorias),
            'id_estado' => EstadoResource::collection($this->estados),
            'id_usuario' => UserResource::collection($this->users),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
