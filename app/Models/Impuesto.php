<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Impuesto extends Model
{
    use SoftDeletes;
    protected $table="impuestos";
    protected $primaryKey = 'id';
    protected $fillable = ['descripcion','valor','id_estado','created_at','updated_at'];

    public function estados(): HasMany
    {
        return $this->hasMany(Estado::class, 'id', 'id_estado');
    }

}
