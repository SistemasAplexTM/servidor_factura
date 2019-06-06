<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Cupons extends Model
{
    public $table = "descuentos";
    protected $dates = ['deleted_at'];
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 
        'codigo_barras', 
        'descuento', 
        'descripcion', 
        'fecha_inicio', 
        'fecha_fin',
        'cantidad',
        'cant_restante',
        'usado',
        'tipo_descuento'
    ];
}
