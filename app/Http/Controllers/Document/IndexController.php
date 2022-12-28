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
use App\PaymentDetail;
use App\Type;
use App\TypePivotDocument;
use App\DetalleSaldos;

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

  public function informeInventario($date = false, $hour = null)
  {
    $data = $this->sqlInventario($date, $hour);
     //return $data;
    return (new FastExcel($data))->download('InformexBodega.csv', function ($dat) use ($date) {
      return [
        'Fecha' => $date,
        'Bodega' => $dat->bodega,
        'Zona' => $dat->zona,
        'Codigo' => $dat->codigo,
        'Referencia' => $dat->referencia,
        'Producto' => $dat->producto,
        'Talla' => $dat->talla,
        'Tema' => $dat->tema,
        'Subtema1' => $dat->subtema1,
        'Subtema2' => $dat->subtema2,
        'Tela' => $dat->tela,
        'Categoria' => $dat->categoria,
        'Saldo' => $dat->saldo,
        'Costo' => $dat->costo,
        'Precio Venta' => $dat->precio_venta,
        'Precio Por Mayor' => $dat->precio_pormayor,
        'Tendencia' => $dat->tendencia,
        'Reprogramado' => $dat->reprogramado
      ];
    });
  }
  public function informeInventarioJson($date = false, $hour = false)
  {
    $data = $this->sqlInventario($date, $hour);
    return response()->json($data);
  }

  public function sqlInventario($date = false, $hour = null, $negatives = false)
  {
    $havingSign = '<>'; 
    if ($negatives) {
      $havingSign = '<';
    }
    // DB::connection()->enableQueryLog();
    $data = $this->getUnionQueriesSaldosDetail($date);
    
    $b = DB::table(DB::raw("({$data->toSql()}) as b"))
      ->mergeBindings($data->getQuery())
      ->join('setup AS s', 's.id', 'b.bodega_id')
      ->join('producto AS p', 'p.id', 'b.producto_id')
      ->join('unidad_medida AS ca', 'ca.id', 'p.categoria_id')
      ->leftJoin('zones AS z', 'z.id', 's.zone_id')
      ->leftJoin('grupo AS talla', 'talla.id', 'p.talla_id')
      ->leftJoin('grupo AS tema', 'tema.id', 'p.tema_id')
      ->leftJoin('grupo AS tela', 'tela.id', 'p.tela_id')
      ->leftJoin('grupo AS subtema1', 'subtema1.id', 'p.subtema1_id')
      ->leftJoin('grupo AS subtema2', 'subtema1.id', 'p.subtema2_id')
      ->selectRaw("
        b.producto_id,
        b.bodega_id,
        Sum( b.saldo ) AS saldo,
        " . date('Y') . " AS fecha,
        s.razon_social AS bodega,
        z.name AS zona,
        p.descripcion AS producto,
        p.codigo,
        p.referencia,
        p.precio_venta,
        p.precio_pormayor,
        p.costo,
        p.tendencia,
        p.reprogramado,
        subtema1.descripcion AS subtema1,
        subtema2.descripcion AS subtema2,
        ca.descripcion AS categoria,
        talla.descripcion AS talla,
        tema.descripcion AS tema,
        tela.descripcion AS tela
      ")
      ->groupBy(
        'b.producto_id',
        'b.bodega_id',
        's.razon_social',
        'p.descripcion',
        'p.codigo',
        'p.referencia',
        'p.precio_venta',
        'p.precio_pormayor',
        'p.costo',
        'ca.descripcion',
        'talla.descripcion',
        'tema.descripcion',
        'tela.descripcion',
        'z.name',
        'subtema1.descripcion',
        'subtema2.descripcion'
      )
      ->havingRaw('saldo ' . $havingSign . ' 0')
      ->get();
    //  return DB::getQueryLog();
    return $b;
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

  public function informeInventarioNegativo($date = false)
  {
    //DB::connection()->enableQueryLog();
    $data = $this->sqlInventario($date, false, true);
    //return DB::getQueryLog();
    if ($data) {
      return (new FastExcel($data))->download('InformexBodegaNegativos.csv', function ($dat) use ($date) {
        return [
          'Fecha' => $date,
          'Bodega' => $dat->bodega,
          'Zona' => $dat->zona,
          'Codigo' => $dat->codigo,
          'Categoria' => $dat->categoria,
          'Saldo' => $dat->saldo
        ];
      });
    }else{
      echo 'No hay registros.';
    }
  }
  
  public function informeCategorias()
  {
    $data = $this->sqlInformeCategorias();
    return (new FastExcel($data))->download('InformexBodega.csv', function ($dat){
      return [
        'Fecha' => $dat->fecha,
        'Bodega' => $dat->bodega,
        'Categoria' => $dat->categoria,
        'Saldo' => $dat->saldo
      ];
    });
  }

  public function sqlInformeCategorias()
  {
    $data = $this->getUnionQueriesSaldosDetail(date('Y-m-d'));
    $b = DB::table(DB::raw("({$data->toSql()}) as b"))
    ->mergeBindings($data->getQuery())
    ->join('setup AS s', 's.id', 'b.bodega_id')
    ->join('producto AS p', 'p.id', 'b.producto_id')
    ->join('unidad_medida AS ca', 'ca.id', 'p.categoria_id')
    ->selectRaw("
    b.bodega_id,
    " . date('Y-m-d') . " AS fecha,
    s.razon_social AS bodega,
    ca.descripcion AS categoria,
    Sum( b.saldo ) AS saldo
    ")
    ->groupBy(
      'b.bodega_id',
      'fecha',
      'ca.descripcion',
      's.razon_social'
    )
    ->havingRaw('saldo > 0')->get();
    return $b;
  }
  
  // URLS REPORTS
  public function reportWarranty()
  {
    try {
      $data = DB::select(DB::raw("SELECT
      c.razon_social AS tienda,
      b.fecha,
      b.consecutivo,
      d.codigo,
      d.descripcion,
      Count(a.cantidad) AS cantidad
      FROM
      detalle AS a
      INNER JOIN documento AS b ON a.documento_id = b.id
      INNER JOIN setup AS c ON b.sucursal_id = c.id
      INNER JOIN producto AS d ON a.producto_id = d.id
      WHERE
      a.deleted_at IS NULL AND
      b.tipo_id = 31 AND
      a.transaccion = 1
      GROUP BY
      c.razon_social,
      b.fecha,
      b.consecutivo,
      d.descripcion,
      d.codigo,
      a.transaccion
      "));

      if ($data) {
        return (new FastExcel($data))->download('InformeGarantias.csv', function ($dat) {
          return [
            'Fecha' => $dat->fecha,
            'Consecutivo' => $dat->consecutivo,
            'Bodega' => $dat->tienda,
            'Codigo' => $dat->codigo,
            'Producto' => $dat->descripcion,
            'Cantidad' => $dat->cantidad
          ];
        });
      } else {
        echo 'No hay registros.';
      }
    } catch (\Throwable $th) {
      throw $th;
    }
  }
  
  public function detailedNegativeInventory($date = false)
  {
    if(!$date){
      $date = date('Y-m-d');
    }
    // DB::connection()->enableQueryLog();
    // $data = DocumentDetail::with('product', 'branch', 'documentall')
    //   ->select(
    //     'id',
    //     'documento_id',
    //     'producto_id',
    //     'bodega_id',
    //     'cantidad',
    //     'created_at'
    //   )
    //   ->whereNull('deleted_at')
    //   ->where([
    //     ['id', 4956796],
    //     ['negative_balance', '1'],
    //     ['created_at', '>=', $date . ' 00:00:00']
    //   ])
    //   ->get();
    $data = DB::select(DB::raw("SELECT
    a.created_at AS fecha,
    d.razon_social AS bodega,
    c.descripcion AS producto,
    c.codigo,
    a.cantidad,
    b.consecutivo,
    e.descripcion AS tipo_documento
    FROM
    detalle AS a
    INNER JOIN documento AS b ON a.documento_id = b.id
    INNER JOIN producto AS c ON a.producto_id = c.id
    INNER JOIN setup AS d ON a.bodega_id = d.id
    INNER JOIN tipo AS e ON b.tipo_id = e.id
    WHERE
    a.deleted_at IS NULL AND
    a.negative_balance = '1' AND
    a.created_at >= '$date 00:00:00'"));
    // return $data;
    if (!empty($data)) {
      return (new FastExcel($data))->download('InformeNegativosFechaHora.csv', function ($dat) {
        return [
          'Fecha' => $dat->fecha,
          'Bodega' => $dat->bodega,
          'Producto' => $dat->producto,
          'Codigo' => $dat->codigo,
          'Cantidad' => $dat->cantidad,
          'Consecutivo' => $dat->consecutivo,
          'Tipo de documento' => $dat->tipo_documento,
        ];
      });
    } else {
      echo 'No hay registros. ';
    }
  }
  
  public function reportCuadre($date)
  {
    try {
      // OBTENER GASTOS
      $costs = DocumentDetail::select(
        'b.sucursal_id',
        'c.razon_social',
        DB::raw('\'gasto\' as forma_pago'),
        DB::raw('SUM(detalle.total_costo) as valor')
      )->join('documento AS b', 'detalle.documento_id', 'b.id')
        ->join('setup AS c', 'b.sucursal_id', 'c.id')
        ->whereNull('detalle.deleted_at')
        ->where([
          ['b.fecha', $date],
          ['detalle.producto_id', 0]
        ])
        ->groupBy('b.sucursal_id');

      // OBTENER DATOS
      $payments = PaymentDetail::select(
        'd.id AS sucursal_id',
        'd.razon_social',
        'c.descripcion AS forma_pago',
        DB::raw('SUM(detalle_pago.valor - detalle_pago.devolucion) as valor')
      )
        ->join('documento AS b', 'detalle_pago.documento_id', 'b.id')
        ->join('forma_pago AS c', 'detalle_pago.forma_pago_id', 'c.id')
        ->join('setup AS d', 'b.sucursal_id', 'd.id')
        ->where([
          ['b.fecha', $date]
        ])
        ->groupBy(['forma_pago', 'b.sucursal_id'])
        ->orderBy('b.sucursal_id', 'ASC')
        ->union($costs)
        ->get();

      return $payments;
    } catch (\Throwable $th) {
      throw $th;
    }
  }
}
