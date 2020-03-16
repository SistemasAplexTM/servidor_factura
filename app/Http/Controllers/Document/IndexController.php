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
   ->where('tipo_id', 1)
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
   ->with('type', 'client', 'branch', 'paymentDetail')->where('tipo_id', $id)
   ->skip($request->page * $request->perPage)->take($request->perPage)
   ->orderBy($request->sort['field'], $request->sort['type'])->get();
   // return DB::getQueryLog();
   return response()->json(['totalRecords' => $count, 'rows' => $data]);
  }

  public function informeInventario($date = false, $hour = false)
  {
    $data = $this->sqlInventario($date, $hour);
   return (new FastExcel($data))->download('InformexBodega.csv', function($data){
    return [
     'Bodega' => $data->branch['razon_social'],
     'Codigo' => $data->product['codigo'],
     'Referencia' => $data->product['referencia'],
     'Descripcion' => $data->product['descripcion'],
     'Talla' => $data->product['size']['descripcion'],
     'Categoria' => $data->product['category']['descripcion'],
     'Saldo' => $data->saldo,
     'Costo' => $data->costo,
     'Venta' => $data->product['precio_venta'],
     'Pormayor' => $data->product['precio_pormayor']
    ];
   });
  }
  public function informeInventarioJson($date = false, $hour = false)
  {
    $data = $this->sqlInventario($date, $hour);
    return response()->json($data);
  }

  public function sqlInventario($date = false, $hour = false)
  {
    //  DB::connection()->enableQueryLog();
   $data = DocumentDetail::select(
     'producto_id','bodega_id',
     DB::raw('Sum(cant_final) AS saldo')
    //  DB::raw('FORMAT(sum(detalle.total_costo) / sum(detalle.cant_final), 2) AS costo')
     )->with(['product', 'branch'])
     ->whereNull('detalle.deleted_at')
     ->where([['bodega_id', '<>', 0], ['producto_id', '<>', 0]])
     ->when($date, function ($query, $data) {
        return $query->where('created_at', '<=', $data . ':00');
      })
     ->groupBy('producto_id', 'bodega_id')->havingRaw('saldo > 0')->limit(10)->get();
    //  return DB::getQueryLog();
     return $data;
  }

  public function moreSales($date_ini, $date_fin, $branch_id = false, $category = false, $group = false)
  {
    if ($group === 'true') {
      $group = true;
    }else{
      $group = false;
    }

    if ($branch_id == 'null') {
      $branch_id = false;
    }
    if ($category == 'null') {
      $category = false;
    }
      // $dateArray = explode('@', $range_date);
      $start = $date_ini;
      $end = $date_fin;
    // DB::connection()->enableQueryLog();

      $data = DocumentDetail::join('documento AS b', 'detalle.documento_id', 'b.id')
      ->join('producto AS c', 'detalle.producto_id', 'c.id')
      ->join('unidad_medida AS d', 'c.categoria_id', 'd.id')
      ->join('setup AS e', 'b.sucursal_id', 'e.id')
      ->join('tipo AS f', 'b.tipo_id', 'f.id')
      ->whereNull('detalle.deleted_at')
      ->whereNull('b.deleted_at')
      ->whereBetween('b.fecha', [$start, $end])
      ->where('f.type_pivot_id', 1)
      ->when($branch_id, function ($query, $branch_id) {
          return $query->where('b.sucursal_id', $branch_id);
      })
      ->when($category, function ($query, $category) {
          return $query->where('c.categoria_id', $category);
      })
      ->when($group, function ($query, $data) {
          return $query->select(
            'd.descripcion AS categoria',
            'c.descripcion AS producto',
            DB::raw('SUM(detalle.cantidad) AS cantidad'),
            DB::raw('SUM(detalle.cantidad * detalle.precio) AS venta')
            )
          ->groupBy(
            'c.descripcion',
            'd.descripcion'
          );
      }, function($query, $data){
        return $query->select(
          'd.descripcion AS categoria',
          'c.descripcion AS producto',
          'detalle.cantidad AS cantidad',
          'detalle.venta AS venta'
          );
      })
      ->get();
    // return DB::getQueryLog();
    if ($data->count() == 0){
     $data = [];
    }
    return (new FastExcel($data))->download('informe_comercial.csv', function($data){
      return [
        // 'Date'      => $data->fecha,
        // 'Name'      => $data->razon_social,
        // 'Code'      => $data->codigo,
        'Product'   => $data->producto,
        'Category'  => $data->categoria,
        'Quantity'  => $data->cantidad,
        'Sale Price'=> $data->venta
      ];
    });
  }
}
