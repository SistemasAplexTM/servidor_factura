<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PaymentDetail extends Model
{
  public $table = "detalle_pago";
  protected $dates = ['deleted_at'];

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
      'id',
      'valor',
      'descripcion',
      'descripcion1',
      'descripcion2',
      'forma_pago_id',
      'documento_id',
      'id_documento',
      'nota_credito'
  ];

  public function payment_form()
  {
   return $this->belongsTo('App\Payment', 'forma_pago_id')
   ->select(['id', 'descripcion']);
  }
}
