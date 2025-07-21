<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Planilla extends Model
{
    use HasFactory;
    use SoftDeletes;
    
    protected $table = "planillas";
    protected $primaryKey = 'id';
    protected $fillable = [
        'descripcion', 'fecha', 'id_empleado', 'id_categoria', 
        'total', 'id_estado', 'id_usuario', 'created_at', 'updated_at'
    ];

    // ✅ CORREGIDO: BelongsTo en lugar de HasMany
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_usuario', 'id');
    }

    // ✅ CORREGIDO: BelongsTo en lugar de HasMany
    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class, 'id_empleado', 'id');
    }

    // ✅ CORREGIDO: BelongsTo en lugar de HasMany
    public function estado(): BelongsTo
    {
        return $this->belongsTo(Estado::class, 'id_estado', 'id');
    }

    // ✅ CORREGIDO: BelongsTo en lugar de HasMany
    public function categoria(): BelongsTo
    {
        return $this->belongsTo(PlanillaCategoria::class, 'id_categoria', 'id');
    }
}
