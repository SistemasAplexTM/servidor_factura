<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DetalleSaldos extends Model
{
  /*
    Se creo la tabla detalle_saldos para acumular los saldos de los años anteriores
    que esta actualmente para optimizar la consultas de inventarios.
    En esta tabla se debe agregar los saldos finales de los productos para cada
    año que termina, agregando en el documento id el consecutivo del año acumulado.
  */ 
  protected $table = 'detalle_saldos';
  protected $fillable = [
    'id',
    'documento_id',
    'producto_id',
    'descripcion',
    'transaccion',
    'bodega_id',
    'cantidad',
    'cant_final',
    'precio',
    'costo',
    'venta',
    'total_desto',
    'total_costo',
    'total_venta',
    'descuento',
    'iva',
    'doc_cruce',
    'observacion',
    'fecha_recibido',
    'created_at',
    'updated_at',
    'deleted_at',
    'id_cruce',
    'devolucion',
    'descuentos_id',
    'descuento_tipo'
  ];

  public function branch()
    {
     return $this->belongsTo('App\Branch', 'bodega_id')
     ->select(['id', 'razon_social'])->where('id', '<>', 0);
    }
    
    public function product()
    {
     return $this->belongsTo('App\Product', 'producto_id', 'id')
     ->select(['id', 'descripcion', 'categoria_id', 'talla_id', 'tema_id', 'tela_id', 'codigo', 'referencia', 'precio_venta', 'precio_pormayor'])
     ->with('category', 'size', 'theme', 'cloth')->whereNull('deleted_at')->where('categoria_id', '<>', 0)->where('id', '<>', 0);
    }

}
