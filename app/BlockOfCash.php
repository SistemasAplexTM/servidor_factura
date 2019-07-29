<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BlockOfCash extends Model
{
 public $table = "cajero_cuadre";
 protected $dates = ['deleted_at'];

 /**
  * The attributes that are mass assignable.
  *
  * @var array
  */
 protected $fillable = [
     'id',
     'sucursal_id',
     'user_id',
     'cuadre',
     'valor_caja',
     'fecha'
 ];
}
