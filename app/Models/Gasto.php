<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Gasto extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $table="gastos";
    protected $primaryKey = 'id';
    protected $fillable = ['codigo_gasto','fecha','id_categoria','descripcion','total','id_usuario','id_estado'];

    public function estados(): HasMany
    {
        return $this->hasMany(Estado::class, 'id', 'id_estado');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'id', 'id_usuario');
    }

    public function categorias(): HasMany
    {
        return $this->hasMany(GastoCategoria::class, 'id', 'id_categoria');
    }
}
