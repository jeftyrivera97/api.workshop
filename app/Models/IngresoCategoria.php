<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;


class IngresoCategoria extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $table="ingreso_categorias";
    protected $primaryKey = 'id';
    protected $fillable = ['descripcion','id_tipo','id_estado','created_at','updated_at','id_usuario'];

    public function estados(): HasMany
    {
        return $this->hasMany(Estado::class, 'id', 'id_estado');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'id', 'id_usuario');
    }

    public function tipos(): HasMany
    {
        return $this->hasMany(IngresoTipo::class, 'id', 'id_tipo');
    }
}
