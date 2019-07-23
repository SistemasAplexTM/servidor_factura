<?php

namespace App\Http\Controllers\Document;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Rap2hpoutre\FastExcel\FastExcel;
use App\DocumentDetail;
use App\Document;
use App\Product;
use App\Type;
use App\TypePivotDocument;

class IndexController extends Controller
{
  public function getTypes($rol, $branch)
  {
   return TypePivotDocument::select('id', 'tipo_id')
   ->with('type')
   ->whereRaw('FIND_IN_SET(?,credenciales)', [$rol])
   ->whereRaw("(FIND_IN_SET(?,tiendas) OR tiendas IS NULL OR tiendas = '')", [$branch])
   ->get();
  }

  public function getDocuments(Request $request, $id)
  {
   // DB::connection()->enableQueryLog();
   $count = Document::with('type', 'client', 'branch')->where('tipo_id', $id)->count();
   $data = Document::select(
    'id', 'consecutivo', 'fecha', 'observacion',
    'sucursal_id', 'tipo_id', 'terceros_id',
    DB::raw("(SELECT ROUND(
      	SUM(
      		a.total_venta + ((a.total_venta * a.iva) / 100)
      	)
      ) AS total_venta FROM detalle AS a WHERE a.documento_id = documento.id) AS total_venta")
    )
   ->with('type', 'client', 'branch')->where('tipo_id', $id)
   ->skip($request->page * $request->perPage)->take($request->perPage)
   ->orderBy($request->sort['field'], $request->sort['type'])->get();
   // return DB::getQueryLog();
   return response()->json(['totalRecords' => $count, 'rows' => $data]);
  }

  public function TestDetail()
  {
   // DB::connection()->enableQueryLog();
   $data = DocumentDetail::select(
     'producto_id','bodega_id',
     DB::raw('FORMAT(Sum(cant_final), 2) AS saldo'),
     DB::raw('FORMAT(sum(detalle.total_costo) / sum(detalle.cant_final), 2) AS costo')
     )->with(['product', 'branch'])
     ->whereNull('detalle.deleted_at')
     ->where([['bodega_id', '<>', 0], ['producto_id', '<>', 0]])
     ->groupBy('producto_id', 'bodega_id')->havingRaw('saldo > 0')->get();
   // return DB::getQueryLog();
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

  public function moreSales($range_date, $branch_id = null, $category = null)
  {
    // SELECT
    // a.id,
    // d.consecutivo,
    // d.fecha,
    // e.razon_social,
    // b.codigo,
    // b.descripcion AS producto,
    // c.descripcion AS categoria,
    // a.cantidad,
    // a.costo,
    // a.venta,
    // a.iva,
    // c.id
    // FROM
    // detalle AS a
    // INNER JOIN producto AS b ON a.producto_id = b.id
    // INNER JOIN unidad_medida AS c ON b.categoria_id = c.id
    // INNER JOIN documento AS d ON a.documento_id = d.id
    // INNER JOIN setup AS e ON d.sucursal_id = e.id
    // INNER JOIN tipo AS f ON d.tipo_id = f.id
    // WHERE
    // a.deleted_at IS NULL AND
    // d.deleted_at IS NULL AND
    // d.fecha BETWEEN '2019-07-01' AND '2019-07-23' AND
    // f.type_pivot_id = 1

    // return $start . ' / ' . $end;
    // exit();
    // DB::connection()->enableQueryLog();
    return $data = DocumentDetail::select(
      'id','documento_id','producto_id'
      )->with(['product', 'document'])
      ->whereHas('document', function ($q) use ($range_date){
        $dateArray = explode('.', $range_date);
        $start = date("Y-m-d", strtotime(trim($dateArray[0])));
        $end = date("Y-m-d", strtotime(trim($dateArray[1])));
        $q->whereBetween('fecha', [$start, $end]);
      })
      // ->whereBetween('created_at', ['2019-07-22', '2019-07-23'])
      ->whereNull('detalle.deleted_at')
      ->where([['bodega_id', '<>', 0]])->get();
      // return DB::getQueryLog();
  }

}
