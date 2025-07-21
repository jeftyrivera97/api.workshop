<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class GastoCategoria extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $table="gasto_categorias";
    protected $primaryKey = 'id';
    protected $fillable = ['descripcion','id_tipo','id_estado','created_at','updated_at','id_usuario'];

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

    // ✅ CORREGIDO: BelongsTo en lugar de HasMany y nombre singular
    public function tipo(): BelongsTo
    {
        return $this->belongsTo(GastoTipo::class, 'id_tipo', 'id');
    }

    // ✅ AGREGADO: Relación inversa con gastos
    public function gastos(): HasMany
    {
        return $this->hasMany(Gasto::class, 'id_categoria', 'id');
    }
}
