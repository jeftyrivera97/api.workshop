<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlanillaResource extends JsonResource
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
            'id_categoria' => PlanillaCategoriaResource::collection($this->categorias),
            'fecha' => $this->fecha,
            'id_empleado' => EmpleadoResource::collection($this->empleados),
            'total' =>  $this->total,
            'id_estado' => EstadoResource::collection($this->estados),
            'id_usuario' => UserResource::collection($this->users),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
