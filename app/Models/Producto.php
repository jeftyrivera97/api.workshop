<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Producto extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $table="productos";
    protected $primaryKey = 'id';
    protected $fillable = ['codigo_producto','descripcion','id_categoria','marca','size','color','id_proveedor','peso','stock','id_impuesto','gravado15','gravado18','impuesto15',
    'impuesto18','exento','exonerado','costo','valor','id_estado','id_usuario','created_at','updated_at'];


    public function categorias(): HasMany
    {
        return $this->hasMany(ProductoCategoria::class, 'id', 'id_categoria');
    }
    public function proveedores(): HasMany
    {
        return $this->hasMany(Proveedor::class, 'id', 'id_proveedor');
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
