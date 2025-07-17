<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PagoCategoria extends Model
{

    use HasFactory;
    use SoftDeletes;
    protected $table="pago_categorias";
    protected $primaryKey = 'id';
    protected $fillable = ['descripcion','id_estado','created_at','updated_at'];

    public function estados(): HasMany
    {
        return $this->hasMany(Estado::class, 'id', 'id_estado');
    }
}
