<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    protected $table = 'documento';
    protected $fillable = [
     'id',
     'usuario_id',
     'tipo_id',
     'consecutivo',
     'fecha',
     'fecha_recibido',
     'terceros_id',
     'dias',
     'descuento',
     'valor_iva',
     'anticipo',
     'vendedor_id',
     'estatus',
     'cerrado',
     'anulado',
     'retefuente',
     'reteica',
     'revisado',
     'created_at',
     'updated_at',
     'deleted_at',
     'descuentos_id',
     'impresion',
     'observacion',
     'descuento_valor',
     'direccion_envio',
     'direccion_facturacion',
     'pormayor',
     'sucursal_id',
     'concepto_anulacion',
     'usuario_id_revision',
     'sin_consecutivo'
    ];

    public function paymentDetail()
    {
        return $this->hasMany('App\PaymentDetail', 'documento_id')
        ->selectRaw('SUM(valor) AS valor, documento_id')->groupBy('documento_id');
    }

    public function type()
    {
        return $this->belongsTo('App\Type', 'tipo_id')->select(['id', 'descripcion']);
    }

    public function client()
    {
        return $this->belongsTo('App\People', 'terceros_id')->select(['id', 'nombre']);
    }

    public function branch()
    {
        return $this->belongsTo('App\Branch', 'sucursal_id')->select(['id', 'razon_social']);
    }
}
