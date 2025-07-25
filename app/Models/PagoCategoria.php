<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PagoCategoria extends Model
{

    use HasFactory;
    use SoftDeletes;
    protected $table="pago_categorias";
    protected $primaryKey = 'id';
    protected $fillable = ['descripcion','id_estado'];

    public function estado(): BelongsTo
    {
        return $this->belongsTo(Estado::class, 'id_estado', 'id');
    }
}
