<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CompraCategoria extends Model
{
    use SoftDeletes;
    protected $table="compra_categorias";
    protected $primaryKey = 'id';
    protected $fillable = ['descripcion','id_tipo','id_estado','id_usuario','created_at','updated_at'];

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
        return $this->hasMany(CompraTipo::class, 'id', 'id_tipo');
    }
}
