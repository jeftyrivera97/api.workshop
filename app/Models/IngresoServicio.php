<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;


class IngresoServicio extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $table="ingreso_servicios";
    protected $primaryKey = 'id';
    protected $fillable = ['id_ingreso','id_servicio','id_estado','created_at','updated_at'];

    public function estados(): HasMany
    {
        return $this->hasMany(Estado::class, 'id', 'id_estado');
    }
    public function ingresos(): HasMany
    {
        return $this->hasMany(Ingreso::class, 'id', 'id_ingreso');
    }
    public function servicios(): HasMany
    {
        return $this->hasMany(Servicio::class, 'id', 'id_servicio');
    }
}
