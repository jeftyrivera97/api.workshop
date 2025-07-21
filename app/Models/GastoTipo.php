<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GastoTipo extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $table="gasto_tipos";
    protected $primaryKey = 'id';
    protected $fillable = ['descripcion','id_estado','id_usuario','created_at','updated_at'];

    // ✅ CORREGIDO: BelongsTo en lugar de HasMany
    public function estado(): BelongsTo
    {
        return $this->belongsTo(Estado::class, 'id_estado', 'id');
    }

    // ✅ CORREGIDO: BelongsTo en lugar de HasMany
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_usuario', 'id');
    }

    // ✅ AGREGADO: Relación con categorías
    public function categorias(): HasMany
    {
        return $this->hasMany(GastoCategoria::class, 'id_tipo', 'id');
    }
}
