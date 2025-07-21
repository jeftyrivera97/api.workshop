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
    public function categoria(): BelongsTo
    {
        return $this->belongsTo(GastoCategoria::class, 'id_categoria', 'id');
    }
}
