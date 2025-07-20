<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Compra extends Model
{
    use HasFactory;
    use SoftDeletes;
    
    protected $table = "compras";
    protected $primaryKey = 'id';
    protected $fillable = [
        'codigo_compra', 'fecha', 'descripcion', 'id_categoria', 'id_proveedor', 
        'id_tipo_cuenta', 'id_estado_cuenta', 'fecha_pago', 'gravado15', 'gravado18', 
        'impuesto15', 'impuesto18', 'exento', 'exonerado', 'total', 'id_usuario', 
        'id_estado', 'created_at', 'updated_at'
    ];

    // ✅ Corrección: BelongsTo con parámetros correctos
    public function categoria(): BelongsTo
    {
        return $this->belongsTo(CompraCategoria::class, 'id_categoria');
    }

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class, 'id_proveedor');
    }

    public function tipoCuenta(): BelongsTo
    {
        return $this->belongsTo(TipoCuenta::class, 'id_tipo_cuenta');
    }

    public function estadoCuenta(): BelongsTo
    {
        return $this->belongsTo(EstadoCuenta::class, 'id_estado_cuenta');
    }

    public function estado(): BelongsTo
    {
        return $this->belongsTo(Estado::class, 'id_estado');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_usuario');
    }
}
