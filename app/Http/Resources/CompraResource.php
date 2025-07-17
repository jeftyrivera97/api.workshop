<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\ProveedorResource;

class CompraResource extends JsonResource
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
            'codigo_compra' => $this->codigo_compra,
            'fecha' => $this->fecha,
            'descripcion' => $this->descripcion,
            'id_categoria' => CompraCategoriaResource::collection($this->categorias),
            'id_proveedor' => ProveedorResource::collection($this->proveedores),
            'id_tipo_cuenta' =>  TipoCuentaResource::collection($this->tipoCuentas),
            'id_estado_cuenta' => EstadoCuentaResource::collection($this->estadoCuentas),
            'fecha_pago' => $this->fecha_pago,
            'gravado15' => $this->gravado15,
            'gravado18' => $this->gravado18,
            'impuesto15' => $this->impuesto15,
            'impuesto18' => $this->impuesto18,
            'exento' => $this->exento,
            'exonerado' => $this->exonerado,
            'total' => $this->total,
            'id_estado' => EstadoResource::collection($this->estados),
            'id_usuario' => UserResource::collection($this->users),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
