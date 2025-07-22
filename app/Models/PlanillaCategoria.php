<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PlanillaCategoria extends Model
{
    use HasFactory;
    use SoftDeletes;
    
    protected $table="planilla_categorias";
    protected $primaryKey = 'id';
    protected $fillable = ['descripcion','id_tipo','id_estado','id_usuario','created_at','updated_at'];

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

    // ✅ CORREGIDO: BelongsTo en lugar de HasMany
    public function tipo(): BelongsTo
    {
        return $this->belongsTo(PlanillaTipo::class, 'id_tipo', 'id');
    }

    // ✅ AGREGADO: Relación inversa con planillas
    public function planillas(): HasMany
    {
        return $this->hasMany(Planilla::class, 'id_categoria', 'id');
    }
}
