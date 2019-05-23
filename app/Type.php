<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Type extends Model
{
 protected $table = 'tipo';
 protected $fillable = [
  'id',
  'descripcion',
  'grupo_id',
  'prefijo',
  'consecutivo',
  'id_transaccion',
  'usado',
  'usar_usuario',
  'docs_cruce',
  'inventario',
  'genera_utilidad',
  'funcionalidades',
  'icono',
  'usa_consecutivo',
  'usa_cuadre',
  'ingreso_egreso',
  'manejo_costo_promedio',
  'costo_venta',
  'credenciales',
  'tiendas',
  'documento_interno',
  'default_list'
 ];

 public function documents()
 {
     return $this->hasMany('App\Document', 'tipo_id');
 }
}
