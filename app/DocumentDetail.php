<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DocumentDetail extends Model
{
    protected $table = 'detalle';
    protected $fillable  = [
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
     return $this->belongsTo('App\Product', 'producto_id')
     ->select(['id', 'descripcion', 'categoria_id', 'talla_id', 'codigo', 'referencia', 'precio_venta', 'precio_pormayor'])
     ->with(['category', 'size'])->whereNull('deleted_at')->where('categoria_id', '<>', 0);
    }

    public function document()
    {
      return $this->belongsTo('App\Document', 'documento_id')
      ->select(['id', 'fecha', 'sucursal_id', 'tipo_id'])
      ->with(['branch', 'type'])
      ->whereHas('type', function ($q){
        $q->where('type_pivot_id', 1);
      })
      ->whereNull('deleted_at')->where('sucursal_id', '<>', 0);
    }

}
