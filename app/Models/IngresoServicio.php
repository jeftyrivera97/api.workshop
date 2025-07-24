<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class IngresoServicio extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $table = "ingreso_servicios";
    protected $primaryKey = 'id';
    protected $fillable = ['id_ingreso', 'id_servicio', 'id_estado', 'created_at', 'updated_at'];

    public function estado(): BelongsTo
    {
        return $this->belongsTo(Estado::class, 'id_estado', 'id');
    }
    public function ingreso(): BelongsTo
    {
        return $this->belongsTo(Ingreso::class, 'id_ingreso', 'id');
    }
    public function servicio(): BelongsTo
    {
        return $this->belongsTo(Servicio::class, 'id_servicio', 'id');
    }
}
