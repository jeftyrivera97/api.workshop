<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GastoResource extends JsonResource
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
            'codigo_gasto' => $this->codigo_gasto,
            'fecha' => $this->fecha,
            'descripcion' => $this->descripcion,
            'categoria' => new GastoCategoriaResource($this->categoria),
            'total' => $this->total,
            'estado' => new EstadoResource($this->estado),
            'usuario' => new UserResource($this->usuario),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
