<?php

namespace App\Http\Controllers\Document;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Rap2hpoutre\FastExcel\FastExcel;
use App\DocumentDetail;
use App\Document;
use App\Product;
use App\Type;

class IndexController extends Controller
{
  public function getTypes($rol = 1, $branch = 10)
  {
   return Type::select('id', 'descripcion', 'icono')
   ->whereRaw('FIND_IN_SET(?,credenciales)', [$rol])
   ->whereRaw('FIND_IN_SET(?,tiendas)', [$branch])
   ->orWhere('tiendas',  null)
   ->where('deleted_at', null)
   ->get();
  }

  public function getDocuments(Request $request, $id)
  {
   $count = Document::with('type', 'client', 'branch')->where('tipo_id', $id)->count();
   $data = Document::with('type', 'client', 'branch')->where('tipo_id', $id)
   ->skip($request->page * $request->perPage)->take($request->perPage)
   ->orderBy($request->sort['field'], $request->sort['type'])->get();
   return response()->json(['totalRecords' => $count, 'rows' => $data]);
  }

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
