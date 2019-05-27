<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AdminTable extends Model
{
  protected $table = 'grupo';
  protected $fillable = [
   'id',
   'descripcion',
   'tipo',
   'codigo',
   'usado'
  ];

   public function products()
   {
    return $this->hasMany('App\Product');
   }
}
