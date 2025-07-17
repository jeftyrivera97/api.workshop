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
    protected $table="compras";
    protected $primaryKey = 'id';
    protected $fillable = ['codigo_compra','fecha','descripcion','id_categoria','id_proveedor','id_tipo_cuenta','id_estado_cuenta','fecha_pago',
    'gravado15','gravado18','impuesto15','impuesto18','exento','exonerado','total','id_usuario','id_estado','created_at','updated_at'];

    public function categorias(): HasMany
    {
        return $this->hasMany(CompraCategoria::class, 'id', 'id_categoria');
    }

    public function proveedores(): HasMany
    {
        return $this->hasMany(Proveedor::class, 'id', 'id_proveedor');
    }
    public function tipoCuentas(): HasMany
    {
        return $this->hasMany(TipoCuenta::class, 'id', 'id_tipo_cuenta');
    }

    public function estadoCuentas(): HasMany
    {
        return $this->hasMany(TipoCuenta::class, 'id', 'id_estado_cuentas');
    }

    public function estados(): HasMany
    {
        return $this->hasMany(Estado::class, 'id', 'id_estado');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'id', 'id_usuario');
    }



}
