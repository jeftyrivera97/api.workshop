<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AutoMarcaResource extends JsonResource
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
            'id_estado' => $this->id_estado,
            'id_usuario' => $this->id_usuario,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
