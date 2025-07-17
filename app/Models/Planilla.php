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
    protected $table="planillas";
    protected $primaryKey = 'id';
    protected $fillable = ['descripcion','fecha','id_empleado','id_categoria','total','id_estado','id_usuario','created_at','updated_at'];

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'id', 'id_usuario');
    }

    public function empleados(): HasMany
    {
        return $this->hasMany(Empleado::class, 'id', 'id_empleado');
    }
    public function estados(): HasMany
    {
        return $this->hasMany(Estado::class, 'id', 'id_estado');
    }

    public function categorias(): HasMany
    {
        return $this->hasMany(PlanillaCategoria::class, 'id', 'id_categoria');
    }


}
