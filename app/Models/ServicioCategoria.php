<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class ServicioCategoria extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $table="servicio_categorias";
    protected $primaryKey = 'id';
    protected $fillable = ['descripcion','id_estado','created_at','updated_at','id_usuario'];

    public function estado(): BelongsTo
    {
        return $this->belongsTo(Estado::class, 'id_estado', 'id');
    }
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_usuario', 'id');
    }
    public function servicios(): HasMany
    {
        return $this->hasMany(Servicio::class, 'id_categoria', 'id');
    }
}
