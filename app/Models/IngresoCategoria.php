<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class IngresoCategoria extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $table = "ingreso_categorias";
    protected $primaryKey = 'id';
    protected $fillable = ['descripcion', 'id_tipo', 'id_estado', 'created_at', 'updated_at', 'id_usuario'];

    public function estado(): BelongsTo
    {
        return $this->belongsTo(Estado::class, 'id_estado', 'id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_usuario', 'id');
    }

    public function tipo(): BelongsTo
    {
        return $this->belongsTo(IngresoTipo::class, 'id_tipo', 'id');
    }
}
