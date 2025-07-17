<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductoCategoriaResource extends JsonResource
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
            'nombre_imagen' => $this->nombre_imagen,
            'ruta_imagen' => $this->ruta_imagen,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'id_estado' => $this->id_estado,
            'id_estado_web' => $this->id_estado_web,
            'id_usuario' => $this->id_usuario,
        ];
    }
}
