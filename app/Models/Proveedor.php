<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Proveedor extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $table="proveedores";
    protected $primaryKey = 'id';
    protected $fillable = ['codigo_proveedor','descripcion','categoria','contacto','telefono','id_estado','id_usuario','created_at','updated_at'];

    public function estados(): HasMany
    {
        return $this->hasMany(Estado::class, 'id', 'id_estado');
    }
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'id', 'id_usuario');
    }
}
