<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
            'categoria' => $this->whenLoaded('categoria', function() {
                return new CompraCategoriaResource($this->categoria);
            }),
            
            'proveedor' => $this->whenLoaded('proveedor', function() {
                return new ProveedorResource($this->proveedor);
            }),
            
            'tipo_cuenta' => $this->whenLoaded('tipoCuenta', function() {
                return new TipoCuentaResource($this->tipoCuenta);
            }),
            
            'estado_cuenta' => $this->whenLoaded('estadoCuenta', function() {
                return new EstadoCuentaResource($this->estadoCuenta);
            }),
            
            'fecha_pago' => $this->fecha_pago,
            'gravado15' => $this->gravado15,
            'gravado18' => $this->gravado18,
            'impuesto15' => $this->impuesto15,
            'impuesto18' => $this->impuesto18,
            'exento' => $this->exento,
            'exonerado' => $this->exonerado,
            'total' => $this->total,
            
            'estado' => $this->whenLoaded('estado', function() {
                return new EstadoResource($this->estado);
            }),
            
            'usuario' => $this->whenLoaded('usuario', function() {
                return new UserResource($this->usuario);
            }),
            
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
