<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
 protected $table = 'unidad_medida';
 protected $fillable = [
  'id',
  'descripcion',
  'abreviatura',
  'created_at',
  'deleted_at',
  'usado'
 ];

 public function product(){
   return $this->hasMany('App\Product');
 }
}
