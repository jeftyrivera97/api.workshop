<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Auto extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $table="autos";
    protected $primaryKey = 'id';
    protected $fillable = ['id_marca','modelo','year','base','traccion','cilindraje','combustion','id_categoria','id_estado','id_usuario','created_at','updated_at'];

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(AutoCategoria::class, 'id_categoria', 'id');
    }
    public function marca(): BelongsTo
    {
        return $this->belongsTo(AutoMarca::class, 'id_marca', 'id');
    }
    public function estado(): BelongsTo
    {
        return $this->belongsTo(Estado::class, 'id_estado', 'id');
    }
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_usuario', 'id');
    }
}
