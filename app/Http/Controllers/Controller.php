<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use App\DetalleSaldos;
use App\DocumentDetail;

class Controller extends BaseController
{
  use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

  public function getUnionQueriesSaldosDetail($date)
  {
    if (strlen($date) < 11) {
      $date = $date . '  23:59:59';
    } else {
      $date = $date . ':00';
    }
    $dataSaldos = DetalleSaldos::select(
      'producto_id',
      'bodega_id',
      DB::raw('Sum(cant_final) AS saldo')
    )
      ->groupBy('producto_id', 'bodega_id');

    $data = DocumentDetail::select(
      'producto_id',
      'bodega_id',
      DB::raw('Sum(cant_final) AS saldo')
    )
      ->whereNull('deleted_at')
      ->whereRaw('bodega_id <> 0 AND producto_id <> 0')
      ->whereRaw("fecha_recibido BETWEEN '2021-01-01 00:00:00' AND '$date'")
      // ->where('fecha_recibido', '>=', date('Y') . "-01-01 00:00:00")
      ->unionAll($dataSaldos)
      ->groupBy('producto_id', 'bodega_id');
    return $data;
  }
}
