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
    protected $table="gasto_planillas";
    protected $primaryKey = 'id';
    protected $fillable = ['id_gasto','id_planilla','id_estado','created_at','updated_at'];

    public function estados(): HasMany
    {
        return $this->hasMany(Estado::class, 'id', 'id_estado');
    }
    public function gastos(): HasMany
    {
        return $this->hasMany(Gasto::class, 'id', 'id_gasto');
    }
    public function planillas(): HasMany
    {
        return $this->hasMany(Planilla::class, 'id', 'id_planilla');
    }
}
