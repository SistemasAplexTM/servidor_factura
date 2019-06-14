<?php

namespace App\Http\Controllers\Document;

use App\Traits\Type as TypeTrait;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\DocumentDetail;
use App\Document;
use App\Product;
use App\Type;
use App\PaymentDetail;
use App\Cupons;
use App\People;

class BillController extends Controller
{
 use TypeTrait;

 public function save(Request $request)
 {
   $type = $this->getType(Auth::user()->sucursal_id);
   DB::beginTransaction();
   try {
     $document = $this->saveHeadboard($request, $type);
     $detail = $this->saveDetail($request['table_detail'], $document['id'], $type, $request['wholesale']);
     $table_detail = $request['table_detail'];
     foreach ($detail as $key => $value) {
       $table_detail[$key]['id'] = $value->id;
     }
     $answer = array(
       "code"   => 200,
       "consecutive" => $document['consecutive'],
       "id" => $document['id'],
       "table_detail" => $table_detail
     );
     DB::commit();
     return $answer;
   } catch (Exception $e) {
     DB::rollback();
     $answer = array(
         "error" => $e,
         "code"  => 600,
     );
     return $answer;
   }
 }

 public function saveHeadboard($request, $type)
 {
   try {
     $data = Document::create($this->dataHeadboard($request, $type));
     $consecutive = DB::select("CALL sp_getConsecutivo(?,?,?)", array($type->id, $data->id, date('Y-m-d')));
     Document::where('id', $data->id)->update(['consecutivo' => $consecutive[0]->consecutivo]);
     return [
       'id' => $data->id,
       'consecutive' => $consecutive[0]->consecutivo,
     ];
   } catch (Exception $e) {
       $answer = array(
           "error" => $e,
           "code"  => 600,
       );
       return $answer;
   }
 }

 public function dataHeadboard($request, $type)
 {
  return [
   'usuario_id' => Auth::user()->usuario_id,
   'tipo_id' => $type->id,
   'fecha' => date('Y-m-d', strtotime($request['form_document']['date'])),
   'fecha_recibido' => date('Y-m-d', strtotime($request['form_document']['date_receip'])),
   'terceros_id' => $request['form_document']['client_id'],
   'dias' => $request['form_document']['days'],
   'descuento' => $request['totals']['descuento_2'],
   'valor_iva' => $request['totals']['iva'],
   'anticipo' => $request['totals']['anticipo'],
   'vendedor_id' => $request['form_document']['seller_id'],
   'estatus' => 1,
   'retefuente' => $request['totals']['retefuente'],
   'reteica' => $request['totals']['reteica'],
   'impresion' => 0,
   'observacion' => $request['form_document']['observation'],
   'descuento_valor' => $request['totals']['descuento_1'],
   'pormayor' => $request['wholesale'],
   'sucursal_id' => Auth::user()->sucursal_id
  ];
 }

 public function dataDetail($document_id, $key, $type, $wholesale)
 {
  $key['precio'] = ($wholesale) ?  $key['precio_pormayor'] : $key['precio_venta'];
  return [
    'documento_id' => $document_id,
    'producto_id' => $key['product_id'],
    'descripcion' => $key['producto'],
    'transaccion' => $type->id_transaccion,
    'bodega_id' => Auth::user()->sucursal_id,
    'cantidad' => $key['cantidad'],
    'cant_final' => $this->getQuantityFinal($key['cantidad'], $type->id_transaccion, 0),
    'precio' => $key['precio'],
    'costo' => $this->getCost($type->genera_utilidad, $key['precio']),
    'venta' => $this->getPrice($key['precio'],$type),
    'total_desto' => $key['descuento'] * $key['cantidad'],
    'total_costo' => $this->getTotalCost($type, $key),
    'total_venta' => ($type->genera_utilidad == 0) ? 0 : $key['cantidad'] * $key['precio'],
    'descuento' => $key['descuento'],
    'iva' => $key['porcentaje_iva']
  ];
 }

 public function saveDetail($detail, $document_id, $type, $wholesale)
 {
  try {
    foreach ($detail as $key) {
      $data[] = DocumentDetail::create($this->dataDetail($document_id, $key, $type, $wholesale));
    }
    return $data;
  } catch (Exception $e) {
    $answer = array(
     "error" => $e,
     "code"  => 600,
    );
    return $answer;
  }
 }

 public function getQuantityFinal($quantity, $transaction, $return)
 {
   $answer = 0;
   if ($return != '1') {
     if($transaction != '0'){
       $answer = ($transaction == 1) ? $quantity : -$quantity;
     }
   }
   return $answer;
 }

 public function getPrice($price, $type)
 {
   $answer = $price;
   if ($type->genera_utilidad != 0) {
     if ($type->id == 5) { // El id 5 es Nota crédito
       $answer = $price * (-1);
     }
   }else {
     $answer = 0;
   }
   return $answer;
 }

 public function getCost($utility, $price)
 {
   return ($utility == 0) ? $price : 0;
 }

 public function getDataDetailByIdDocument($id_document)
 {
   return DocumentDetail::select(
    'detalle.id',
    'detalle.producto_id AS product_id',
    'detalle.cantidad',
    'detalle.costo',
    'detalle.precio AS precio_venta',
    'detalle.descuento',
    'detalle.descuento AS descuento_venta',
    'detalle.iva AS porcentaje_iva',
    DB::raw('0 AS precio_con_iva'),
    DB::raw('0 AS precio_pormayor'),
    'detalle.descripcion AS producto',
    DB::raw('ROUND((detalle.precio * detalle.cantidad) * detalle.iva / 100) AS iva'),
    DB::raw('(ROUND((detalle.precio * detalle.cantidad) * detalle.iva / 100) + (detalle.precio * detalle.cantidad))  AS monto_total')
    )->where('documento_id', $id_document)->get();
 }

 public function documentById($id)
 {
   DB::beginTransaction();
   try {
     $document   = Document::findOrFail($id);
     $client     = People::findOrFail($document->terceros_id);
     $seller     = People::findOrFail($document->vendedor_id);
     $detail     = $this->getDataDetailByIdDocument($id);
     DB::commit();
     $answer = array(
        "code"      => 200,
        "document"  => $document,
        "detail"    => $detail,
        "client"    => $client,
        "seller"    => $seller,
        "totals"    => ''
       );
     return $answer;
   } catch (Exception $e) {
       DB::rollback();
       $answer = array(
           "error" => $e,
           "code"  => 600,
       );
       return $answer;
   }
 }

 public function getTotalCost($type, $data)
 {
   $cost = $this->getCost($type->genera_utilidad, $data['precio']);
   $quantityFinal = $this->getQuantityFinal($data['cantidad'], $type->id_transaccion, 0);
   $answer = $quantityFinal *  $cost;
   if ($type->genera_utilidad == 0) {
     if($type->usa_cuadre == 1){
       $answer = $data['cantidad'] * $cost;
       if($type->ingreso_egreso != 0){
         $answer = -($data['cantidad'] * $cost);
       }
     }
   }
   return $answer;
 }

 public function savePaymentMethod(Request $request)
 {
    DB::beginTransaction();
    try {
      PaymentDetail::where('documento_id', $request->id_document)->delete();
      foreach ($request->all() as $key => $value) {
        if($value['valor'] != 0){
          PaymentDetail::create($value);
        }
      }
      Document::where('id', $request->id_document)->update(['estatus' => 2]);
      $answer = array(
          "code"   => 200
      );
      DB::commit();
      return $answer;
    } catch (Exception $e) {
       DB::rollback();
       $answer = array(
           "error" => $e,
           "code"  => 600,
       );
       return $answer;
    }
  }

  public function getCupon($data){
   $datos = Cupons::where('codigo_barras', $data)->first();
   if($datos != '' or $datos != false){
     $code = 200;
     $fecha_actual = strtotime(date("d-m-Y",time()));
     $fecha_fin = strtotime($datos->fecha_fin);
     if($fecha_actual > $fecha_fin){
       $code = 600;
       $msg = 'El cupón caduco el ' . $datos->fecha_fin;
     }else{
       $msg = '<strong>' . $datos->descripcion . '</strong> <br> Se aplicará a esta factura';
     }
   }else{
     $code = 600;
     $msg = 'No hay datos con ese código';
   }
   $answer = array(
     'code'  => $code,
     'data' => $datos,
     'msg' => $msg,
     'user' => Auth::user()
   );

   return \Response::json($answer);
 }

 public function update(Request $request, $id)
  {
    $type = $this->getType(Auth::user()->sucursal_id);
    DB::beginTransaction();
    try {
      $document = $this->updateHeadboard($id, $request, $type);
      $this->updateDetail($request['table_detail'], $id, $type,  $request['wholesale']);
      $answer = array(
          "code"   => 200
      );
      DB::commit();
      return $answer;
    } catch (Exception $e) {
      DB::rollback();
      $answer = array(
          "error" => $e,
          "code"  => 600,
      );
      return $answer;
    }
  }

  public function updateHeadboard($id, $request, $type)
  {
    try {
      return Document::where('id', $id)->update($this->dataHeadboard($request, $type));
    } catch (Exception $e) {
      $answer = array(
          "error" => $e,
          "code"  => 600,
      );
      return $answer;
    }
  }

  public function updateDetail($detail, $document_id, $type, $wholesale)
 {
   try {
     foreach ($detail as $key) {
       if(isset($key['id'])){
         $data[] = DocumentDetail::where('id', $key['id'])->update($this->dataDetail($document_id, $key, $type, $wholesale));
       }else{
         $this->saveDetail($detail, $document_id, $type);
       }
     }
     return $data;
   } catch (Exception $e) {
     $answer = array(
         "error" => $e,
         "code"  => 600,
     );
     return $answer;
   }

 }

}
