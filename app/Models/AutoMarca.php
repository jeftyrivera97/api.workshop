<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AutoMarca extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $table="auto_marcas";
    protected $primaryKey = 'id';
    protected $fillable = ['descripcion','id_estado','id_usuario','created_at','updated_at'];

    public function estado(): BelongsTo
    {
        return $this->belongsTo(Estado::class, 'id_estado', 'id');
    }
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_usuario', 'id');
    }
    public function autos(): HasMany
    {
        return $this->hasMany(Auto::class, 'id_marca', 'id');
    }
}
