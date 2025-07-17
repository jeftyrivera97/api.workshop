<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ingreso extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $table="ingresos";
    protected $primaryKey = 'id';
    protected $fillable = ['descripcion','fecha','id_categoria','total','id_estado','id_usuario','created_at','updated_at'];

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(IngresoCategoria::class, 'id_categoria');
    }

    public function estado(): BelongsTo
    {
        return $this->belongsTo(Estado::class, 'id_estado');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_usuario');
    }

}
