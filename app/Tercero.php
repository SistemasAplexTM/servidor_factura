<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Tercero extends Model
{
    protected $table = 'recibo_caja';
    protected $fillable = ['id', 'consecutivo', 'descripcion', 'fecha_pago', 'created_at'];
}
