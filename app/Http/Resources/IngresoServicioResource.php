<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IngresoServicioResource extends JsonResource
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
            'ingreso' => $this->whenLoaded('ingreso', function () {
                return new IngresoResource($this->ingreso);
            }),
            'servicio' => $this->whenLoaded('servicio', function () {
                return new ServicioResource($this->servicio);
            }),
            'estado' => $this->whenLoaded('estado', function () {
                return new EstadoResource($this->estado);
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
