<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TypePivotDocument extends Model
{
    protected $table = 'tipo_pivot_documento';
    protected $fillable = ['id', 'prefijo', 'consecutivo', 'credenciales', 'tiendas', 'tipo_id'];

    public function type()
    {
      return $this->belongsTo('App\Type', 'tipo_id', 'id')->select(['id', 'descripcion', 'icono'])->whereNull('deleted_at');
    }
}
