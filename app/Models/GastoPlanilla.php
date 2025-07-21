<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class GastoPlanilla extends Model
{
    use HasFactory;
    use SoftDeletes;
    
    protected $table = "gasto_planillas";
    protected $primaryKey = 'id';
    protected $fillable = ['id_gasto', 'id_planilla', 'id_estado', 'created_at', 'updated_at'];

    // ✅ CORREGIDO: BelongsTo en lugar de HasMany
    public function estado(): BelongsTo
    {
        return $this->belongsTo(Estado::class, 'id_estado', 'id');
    }

    // ✅ CORREGIDO: BelongsTo en lugar de HasMany y nombre singular
    public function gasto(): BelongsTo
    {
        return $this->belongsTo(Gasto::class, 'id_gasto', 'id');
    }

    // ✅ CORREGIDO: BelongsTo en lugar de HasMany y nombre singular
    public function planilla(): BelongsTo
    {
        return $this->belongsTo(Planilla::class, 'id_planilla', 'id');
    }
}
