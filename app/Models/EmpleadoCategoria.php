<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmpleadoCategoria extends Model
{
    use HasFactory;
    use SoftDeletes;
    
    protected $table = "empleado_categorias";
    protected $primaryKey = 'id';
    protected $fillable = ['descripcion', 'area', 'rango', 'id_estado', 'id_usuario', 'created_at', 'updated_at'];

    // ✅ CORREGIDO: BelongsTo para relación con estado
    public function estado(): BelongsTo
    {
        return $this->belongsTo(Estado::class, 'id_estado', 'id');
    }

    // ✅ CORREGIDO: BelongsTo para relación con usuario
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_usuario', 'id');
    }

    // ✅ AGREGAR: HasMany para empleados
    public function empleados(): HasMany
    {
        return $this->hasMany(Empleado::class, 'id_categoria', 'id');
    }
}
