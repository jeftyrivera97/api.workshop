<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CompraDetalle extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $table="compra_detalles";
    protected $primaryKey = 'id';
    protected $fillable = ['id_compra','linea','id_producto','cantidad','costo','total_linea','id_estado','id_usuario','created_at','updated_at'];

    public function compras(): HasMany
    {
        return $this->hasMany(Compra::class, 'id', 'id_compra');
    }
    public function productos(): HasMany
    {
        return $this->hasMany(Producto::class, 'id', 'id_producto');
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
