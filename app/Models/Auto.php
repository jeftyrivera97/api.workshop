<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Auto extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $table="autos";
    protected $primaryKey = 'id';
    protected $fillable = ['id_marca','modelo','year','base','traccion','cilindraje','combustion','id_categoria','id_estado','id_usuario','created_at','updated_at'];

    public function categorias(): HasMany
    {
        return $this->hasMany(AutoCategoria::class, 'id', 'id_categoria');
    }
    public function marcas(): HasMany
    {
        return $this->hasMany(AutoMarca::class, 'id', 'id_marca');
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
