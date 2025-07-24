<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class Servicio extends Model
{
    use SoftDeletes;
    use HasFactory;
    protected $table="servicios";
    protected $primaryKey = 'id';
    protected $fillable = ['fecha','descripcion','id_cliente','id_auto','id_categoria','color','placa','id_pago_categoria','total','id_estado','id_usuario','created_at','updated_at'];

    public function estados(): HasMany
    {
        return $this->hasMany(Estado::class, 'id', 'id_estado');
    }
    public function autos(): HasMany
    {
        return $this->hasMany(Auto::class, 'id', 'id_auto');
    }
    public function clientes(): HasMany
    {
        return $this->hasMany(Cliente::class, 'id', 'id_cliente');
    }
    public function pagoCategorias(): HasMany
    {
        return $this->hasMany(PagoCategoria::class, 'id', 'id_pago_categoria');
    }
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'id', 'id_usuario');
    }
    public function estado(): BelongsTo
    {
        return $this->belongsTo(Estado::class, 'id_estado', 'id');
    }
    public function auto(): BelongsTo
    {
        return $this->belongsTo(Auto::class, 'id_auto', 'id');
    }
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'id_cliente', 'id');
    }
    public function categoria(): BelongsTo
    {
        return $this->belongsTo(ServicioCategoria::class, 'id_categoria', 'id');
    }
    public function pagoCategoria(): BelongsTo
    {
        return $this->belongsTo(PagoCategoria::class, 'id_pago_categoria', 'id');
    }
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_usuario', 'id');
    }
}

