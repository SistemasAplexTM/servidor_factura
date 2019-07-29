<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class IndexController extends Controller
{
 public function TestDetail()
 {
  DB::connection()->enableQueryLog();
  $data = DocumentDetail::select(
    'producto_id','bodega_id',
    DB::raw('FORMAT(Sum(cant_final), 2) AS saldo'),
    DB::raw('FORMAT(sum(detalle.total_costo) / sum(detalle.cant_final), 2) AS costo')
    )->with(['product', 'branch'])
    ->whereNull('detalle.deleted_at')
    ->where([['bodega_id', '<>', 0], ['producto_id', '<>', 0]])
    ->groupBy('producto_id', 'bodega_id')->havingRaw('saldo > 0')->get();
  return DB::getQueryLog();
  // return $data;
  return (new FastExcel($data))->download('InformexBodega.xlsx', function($data){
   return [
    'Bodega' => $data->branch['razon_social'],
    'Código' => $data->product['codigo'],
    'Referencia' => $data->product['referencia'],
    'Descripcion' => $data->product['descripcion'],
    'Talla' => $data->product['size']['descripcion'],
    'Categoría' => $data->product['category']['descripcion'],
    'Saldo' => $data->saldo,
    'Costo' => $data->costo,
    'Venta' => $data->product['precio_venta'],
    'Pormayor' => $data->product['precio_pormayor']
   ];
  });
 }
}
