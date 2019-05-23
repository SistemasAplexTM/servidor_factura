<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
 protected $table = 'setup';
 protected $fillable = [
  'id',
  'usuario_id',
  'nit',
  'razon_social',
  'direccion',
  'telefono',
  'email',
  'celular',
  'ciudad',
  'pais',
  'created_at',
  'updated_at',
  'deleted_at',
  'orden_pedido',
  'representante',
  'responsabilidad_sociedad',
  'sucursal',
  'usado',
  'color'
 ];
}
