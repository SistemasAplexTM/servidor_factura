<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $table = 'producto';
    protected $fillable = [
     'id',
     'codigo',
     'referencia',
     'descripcion',
     'fit',
     'color_id',
     'categoria_id',
     'grupo_id',
     'tipo_producto_id',
     'precio_venta',
     'precio_pormayor',
     'precio_con_iva',
     'costo',
     'talla_id',
     'iva_id',
     'unidad_medida_id',
     'created_at',
     'updated_at',
     'deleted_at',
     'usado'
    ];

    public function category()
    {
     return $this->belongsTo('App\Category', 'categoria_id', 'id')->select(['id', 'descripcion']);
    }
}
