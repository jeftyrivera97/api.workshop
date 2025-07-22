<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Empleado extends Model
{
    use HasFactory;
    use SoftDeletes;
    
    protected $table = "empleados";
    protected $primaryKey = 'id';
    protected $fillable = ['codigo_empleado', 'descripcion', 'id_categoria', 'telefono', 'id_estado', 'id_usuario', 'created_at', 'updated_at'];

    // ✅ CORREGIDO: BelongsTo para relación con categoría
    public function categoria(): BelongsTo
    {
        return $this->belongsTo(EmpleadoCategoria::class, 'id_categoria', 'id');
    }

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

    // ✅ AGREGAR: HasMany para planillas
    public function planillas(): HasMany
    {
        return $this->hasMany(Planilla::class, 'id_empleado', 'id');
    }
}
