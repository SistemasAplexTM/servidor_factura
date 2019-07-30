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

  public function moreSales($range_date, $branch_id = false, $category = false)
  {
        $dateArray = explode('.', $range_date);
        $start = date("Y-m-d", strtotime(trim($dateArray[0])));
        $end = date("Y-m-d", strtotime(trim($dateArray[1])));
    // DB::connection()->enableQueryLog();
        $data = DocumentDetail::select(
        'detalle.id',
        'd.consecutivo',
        'd.fecha',
        'e.razon_social',
        'b.codigo',
        'b.descripcion AS producto',
        'c.descripcion AS categoria',
        'detalle.cantidad',
        'detalle.costo',
        'detalle.venta',
        'detalle.iva',
        'detalle.bodega_id',
        'detalle.producto_id'
        )
        ->join('producto AS b', 'detalle.producto_id', 'b.id')
        ->join('unidad_medida AS c', 'b.categoria_id', 'c.id')
        ->join('documento AS d', 'detalle.documento_id', 'd.id')
        ->join('setup AS e', 'd.sucursal_id', 'e.id')
        ->join('tipo AS f', 'd.tipo_id', 'f.id')
        ->whereNull('detalle.deleted_at')
        ->whereNull('d.deleted_at')
        ->whereBetween('d.fecha', [$start, $end])
        ->where('f.type_pivot_id', 1)
        ->when($branch_id, function ($query, $branch_id) {
            return $query->where('d.sucursal_id', $branch_id);
        })
        ->when($category, function ($query, $category) {
            return $query->where('b.categoria_id', $category);
        })
        ->get();

      // return DB::getQueryLog();

      return (new FastExcel($data))->download('informec.xlsx', function($data){
       return [
        'Consecutivo'   => $data->consecutivo,
        'Fecha'         => $data->fecha,
        'Razon social'  => $data->razon_social,
        'Código'        => $data->codigo,
        'Producto'      => $data->producto,
        'Categoría'     => $data->categoria,
        'Cantidad'      => $data->cantidad,
        'Costo'         => $data->costo,
        'Venta'         => $data->venta,
        'Iva'           => $data->iva
       ];
      });
  }
}
