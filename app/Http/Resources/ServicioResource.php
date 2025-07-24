<?php

namespace App\Http\Resources;

use App\Models\ServicioCategoria;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServicioResource extends JsonResource
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
            'cliente' => $this->whenLoaded('cliente', function () {
                return new ClienteResource($this->cliente);
            }),
            'auto' => $this->whenLoaded('auto', function () {
                return new AutoResource($this->auto);
            }),
            'categoria' => $this->whenLoaded('categoria', function () {
                return new ServicioCategoriaResource($this->categoria); 
            }),
            'color' => $this->color,
            'placa' => $this->placa,
            'total' => $this->total,
            'id_pago_categoria' => $this->id_pago_categoria,
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
